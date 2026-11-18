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
 * Restore task of mod_wolvy.
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/wolvy/backup/moodle2/restore_wolvy_stepslib.php');

/**
 * Restore task of mod_wolvy.
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_wolvy_activity_task extends restore_activity_task {
    /**
     * No particular settings.
     */
    protected function define_my_settings() {
    }

    /**
     * One structure step.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_wolvy_activity_structure_step('wolvy_structure', 'wolvy.xml'));
    }

    /**
     * Fields whose links are decoded.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents() {
        return [new restore_decode_content('wolvy', ['intro'], 'wolvy')];
    }

    /**
     * Link decoding rules.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('WOLVYINDEX', '/mod/wolvy/index.php?id=$1', 'course'),
            new restore_decode_rule('WOLVYVIEWBYID', '/mod/wolvy/view.php?id=$1', 'course_module'),
        ];
    }

    /**
     * Log rules for the activity.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules() {
        return [
            new restore_log_rule('wolvy', 'add', 'view.php?id={course_module}', '{wolvy}'),
            new restore_log_rule('wolvy', 'update', 'view.php?id={course_module}', '{wolvy}'),
            new restore_log_rule('wolvy', 'view', 'view.php?id={course_module}', '{wolvy}'),
        ];
    }

    /**
     * Log rules for the course.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules_for_course() {
        return [new restore_log_rule('wolvy', 'view all', 'index.php?id={course}', null)];
    }
}
