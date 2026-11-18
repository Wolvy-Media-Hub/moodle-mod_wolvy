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
 * Backup structure of mod_wolvy.
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backup structure of mod_wolvy.
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_wolvy_activity_structure_step extends backup_activity_structure_step {

    /**
     * The activity and, with user data, each user's progress.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $wolvy = new backup_nested_element('wolvy', ['id'], [
            'name', 'intro', 'introformat', 'videoid', 'aspect', 'completionpercent', 'timecreated', 'timemodified',
        ]);
        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', ['id'], ['userid', 'percent', 'timemodified']);

        $wolvy->add_child($progresses);
        $progresses->add_child($progress);

        $wolvy->set_source_table('wolvy', ['id' => backup::VAR_ACTIVITYID]);
        if ($userinfo) {
            $progress->set_source_table('wolvy_progress', ['wolvyid' => backup::VAR_PARENTID]);
        }

        $progress->annotate_ids('user', 'userid');
        $wolvy->annotate_files('mod_wolvy', 'intro', null);

        return $this->prepare_activity_structure($wolvy);
    }
}
