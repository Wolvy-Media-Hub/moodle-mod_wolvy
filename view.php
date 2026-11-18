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
 * Shows one Wolvy video activity.
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$w = optional_param('w', 0, PARAM_INT); // Instance id.

if ($w) {
    $wolvy = $DB->get_record('wolvy', ['id' => $w], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('wolvy', $wolvy->id, $wolvy->course, false, MUST_EXIST);
} else {
    $cm = get_coursemodule_from_id('wolvy', $id, 0, false, MUST_EXIST);
    $wolvy = $DB->get_record('wolvy', ['id' => $cm->instance], '*', MUST_EXIST);
}
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/wolvy:view', $context);

wolvy_view($wolvy, $course, $cm, $context);

$PAGE->set_url('/mod/wolvy/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($course->shortname) . ': ' . format_string($wolvy->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_activity_record($wolvy);

$threshold = (int) $wolvy->completionpercent;
$completion = new completion_info($course);
$tracking = $threshold > 0 && $completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC && !isguestuser();

echo $OUTPUT->header();

// The player escapes the title itself, so it is formatted unescaped here.
$title = format_string($wolvy->name, true, ['context' => $context, 'escape' => false]);
$player = \filter_wolvy\player::render($wolvy->videoid, ['aspect' => $wolvy->aspect, 'title' => $title]);
echo html_writer::div($player, 'mod-wolvy-player', ['id' => 'mod-wolvy-player-' . $cm->id]);

if ($tracking) {
    $best = (int) $DB->get_field('wolvy_progress', 'percent', ['wolvyid' => $wolvy->id, 'userid' => $USER->id]);
    echo html_writer::div(
        get_string('yourprogress', 'wolvy', ['best' => $best, 'needed' => $threshold]),
        'mod-wolvy-progress mt-2 text-muted',
        ['id' => 'mod-wolvy-progress-' . $cm->id]
    );

    // Shown by the script only if the player never answers: the Player API is off for the account.
    if (has_capability('moodle/course:manageactivities', $context)) {
        echo html_writer::div(
            get_string('playerapioff', 'wolvy'),
            'mod-wolvy-apioff alert alert-warning mt-2',
            ['id' => 'mod-wolvy-apioff-' . $cm->id, 'hidden' => 'hidden']
        );
    }

    $PAGE->requires->js_call_amd('mod_wolvy/progress', 'init', [$cm->id, $threshold, $best]);
}

echo $OUTPUT->footer();
