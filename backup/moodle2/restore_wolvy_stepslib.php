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
 * Restore structure of mod_wolvy.
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore structure of mod_wolvy.
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_wolvy_activity_structure_step extends restore_activity_structure_step {

    /**
     * Paths to restore.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [new restore_path_element('wolvy', '/activity/wolvy')];

        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('wolvy_progress', '/activity/wolvy/progresses/progress');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * The activity.
     *
     * @param array $data Data.
     */
    protected function process_wolvy($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();
        $newitemid = $DB->insert_record('wolvy', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * One user's progress.
     *
     * @param array $data Data.
     */
    protected function process_wolvy_progress($data) {
        global $DB;

        $data = (object) $data;
        $data->wolvyid = $this->get_new_parentid('wolvy');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!$data->userid) {
            return;
        }
        $DB->insert_record('wolvy_progress', $data);
    }

    /**
     * Files of the description.
     */
    protected function after_execute() {
        $this->add_related_files('mod_wolvy', 'intro', null);
    }
}
