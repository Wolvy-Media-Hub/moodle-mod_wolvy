<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library of functions for mod_wolvy.
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Which Moodle features the activity supports.
 *
 * @param string $feature FEATURE_xx constant.
 * @return mixed True, false, a value, or null when unknown.
 */
function wolvy_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_OTHER;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
            return false;
        default:
            return null;
    }
}

/**
 * Add an activity.
 *
 * @param stdClass $data Form data.
 * @param mod_wolvy_mod_form|null $mform The form.
 * @return int New instance id.
 */
function wolvy_add_instance($data, $mform = null) {
    global $DB;

    $data->completionpercent = (int) ($data->completionpercent ?? 0);
    $data->aspect = (string) ($data->aspect ?? '');
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->id = $DB->insert_record('wolvy', $data);

    $expected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'wolvy', $data->id, $expected);

    return $data->id;
}

/**
 * Update an activity.
 *
 * @param stdClass $data Form data.
 * @param mod_wolvy_mod_form|null $mform The form.
 * @return bool
 */
function wolvy_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    // Absent when completion is locked (students already completed it): keep the stored rule.
    if (isset($data->completionpercent)) {
        $data->completionpercent = (int) $data->completionpercent;
    }
    $data->aspect = (string) ($data->aspect ?? '');
    $data->timemodified = time();
    $DB->update_record('wolvy', $data);

    $expected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'wolvy', $data->id, $expected);

    return true;
}

/**
 * Delete an activity and everything recorded for it.
 *
 * @param int $id Instance id.
 * @return bool
 */
function wolvy_delete_instance($id) {
    global $DB;

    if (!$wolvy = $DB->get_record('wolvy', ['id' => $id])) {
        return false;
    }

    $cm = get_coursemodule_from_instance('wolvy', $id);
    if ($cm) {
        \core_completion\api::update_completion_date_event($cm->id, 'wolvy', $id, null);
    }

    $DB->delete_records('wolvy_progress', ['wolvyid' => $wolvy->id]);
    $DB->delete_records('wolvy', ['id' => $wolvy->id]);

    return true;
}

/**
 * What the course page needs, cached by Moodle.
 *
 * @param stdClass $coursemodule Course module record.
 * @return cached_cm_info|false
 */
function wolvy_get_coursemodule_info($coursemodule) {
    global $DB;

    $fields = 'id, name, intro, introformat, completionpercent';
    if (!$wolvy = $DB->get_record('wolvy', ['id' => $coursemodule->instance], $fields)) {
        return false;
    }

    $info = new cached_cm_info();
    $info->name = $wolvy->name;

    if ($coursemodule->showdescription) {
        // Filters run at display time, not here.
        $info->content = format_module_intro('wolvy', $wolvy, $coursemodule->id, false);
    }

    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules']['completionpercent'] = (int) $wolvy->completionpercent;
    }

    return $info;
}

/**
 * Human-readable descriptions of the active custom completion rules.
 *
 * @param cm_info|stdClass $cm With ->completion and ->customdata['customcompletionrules'].
 * @return string[]
 */
function mod_wolvy_get_completion_active_rule_descriptions($cm) {
    if (empty($cm->customdata['customcompletionrules']) || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    $percent = (int) ($cm->customdata['customcompletionrules']['completionpercent'] ?? 0);
    if ($percent > 0) {
        $descriptions[] = get_string('completiondetail:percent', 'wolvy', $percent);
    }

    return $descriptions;
}

/**
 * Record a view: the event and the "viewed" completion rule.
 *
 * @param stdClass $wolvy Instance record.
 * @param stdClass $course Course record.
 * @param stdClass|cm_info $cm Course module.
 * @param context_module $context Module context.
 */
function wolvy_view($wolvy, $course, $cm, $context) {
    global $CFG;
    require_once($CFG->libdir . '/completionlib.php');

    $event = \mod_wolvy\event\course_module_viewed::create([
        'context' => $context,
        'objectid' => $wolvy->id,
    ]);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('wolvy', $wolvy);
    $event->trigger();

    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Course reset: the checkbox.
 *
 * @param MoodleQuickForm $mform The reset form.
 */
function wolvy_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'wolvyheader', get_string('modulenameplural', 'wolvy'));
    $mform->addElement('advcheckbox', 'reset_wolvy_progress', get_string('resetprogress', 'wolvy'));
}

/**
 * Course reset: the default.
 *
 * @param stdClass $course Course record.
 * @return array
 */
function wolvy_reset_course_form_defaults($course) {
    return ['reset_wolvy_progress' => 1];
}

/**
 * Course reset: delete what was watched.
 *
 * @param stdClass $data Reset form data.
 * @return array Status lines.
 */
function wolvy_reset_userdata($data) {
    global $DB;

    $status = [];

    if (!empty($data->reset_wolvy_progress)) {
        $DB->delete_records_select(
            'wolvy_progress',
            'wolvyid IN (SELECT id FROM {wolvy} WHERE course = :courseid)',
            ['courseid' => $data->courseid]
        );
        $status[] = [
            'component' => get_string('modulenameplural', 'wolvy'),
            'item' => get_string('resetprogress', 'wolvy'),
            'error' => false,
        ];
    }

    return $status;
}
