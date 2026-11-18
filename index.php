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
 * Lists the Wolvy video activities of a course.
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course id.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

$event = \mod_wolvy\event\course_module_instance_list_viewed::create(['context' => context_course::instance($course->id)]);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strplural = get_string('modulenameplural', 'wolvy');

$PAGE->set_url('/mod/wolvy/index.php', ['id' => $course->id]);
$PAGE->set_title(format_string($course->shortname) . ': ' . $strplural);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add($strplural);

echo $OUTPUT->header();
if (!$PAGE->has_secondary_navigation()) {
    echo $OUTPUT->heading($strplural);
}

if (!$instances = get_all_instances_in_course('wolvy', $course)) {
    notice(get_string('thereareno', 'moodle', $strplural), new moodle_url('/course/view.php', ['id' => $course->id]));
    exit;
}

$usesections = course_format_uses_sections($course->format);
$modinfo = get_fast_modinfo($course);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
$table->head = [
    $usesections ? get_string('sectionname', 'format_' . $course->format) : get_string('lastmodified'),
    get_string('name'),
    get_string('moduleintro'),
];

foreach ($instances as $instance) {
    $cm = $modinfo->cms[$instance->coursemodule];
    $first = $usesections
        ? ($instance->section ? get_section_name($course, $instance->section) : '')
        : userdate($instance->timemodified);
    $link = html_writer::link(
        new moodle_url('/mod/wolvy/view.php', ['id' => $cm->id]),
        format_string($instance->name),
        $instance->visible ? [] : ['class' => 'dimmed']
    );
    $table->data[] = [$first, $link, format_module_intro('wolvy', $instance, $cm->id)];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
