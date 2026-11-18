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

namespace mod_wolvy\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for mod_wolvy.
 *
 * Stored: the best share of each video a user watched in one visit. Sent out:
 * the viewer ID inside the signed token in the player's address (built by
 * filter_wolvy, which declares it too).
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {
    /**
     * Describe what is stored and sent.
     *
     * @param collection $collection The collection to add to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('wolvy_progress', [
            'userid' => 'privacy:metadata:wolvy_progress:userid',
            'percent' => 'privacy:metadata:wolvy_progress:percent',
            'timemodified' => 'privacy:metadata:wolvy_progress:timemodified',
        ], 'privacy:metadata:wolvy_progress');

        $collection->add_external_location_link('wolvy', [
            'viewerid' => 'privacy:metadata:wolvy:viewerid',
        ], 'privacy:metadata:wolvy');

        return $collection;
    }

    /**
     * Contexts holding data for a user.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {wolvy_progress} p ON p.wolvyid = cm.instance
                 WHERE p.userid = :userid";

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'wolvy',
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Users with data in a context.
     *
     * @param userlist $userlist The list to fill.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT p.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {wolvy_progress} p ON p.wolvyid = cm.instance
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, ['modname' => 'wolvy', 'cmid' => $context->instanceid]);
    }

    /**
     * Export a user's progress.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('wolvy', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $progress = $DB->get_record('wolvy_progress', ['wolvyid' => $cm->instance, 'userid' => $user->id]);
            if (!$progress) {
                continue;
            }

            $data = helper::get_context_data($context, $user);
            $data->percentwatched = (int) $progress->percent;
            $data->timemodified = transform::datetime($progress->timemodified);
            writer::with_context($context)->export_data([], $data);
            helper::export_context_files($context, $user);
        }
    }

    /**
     * Delete everyone's progress in a context.
     *
     * @param \context $context The context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }
        if ($cm = get_coursemodule_from_id('wolvy', $context->instanceid)) {
            $DB->delete_records('wolvy_progress', ['wolvyid' => $cm->instance]);
        }
    }

    /**
     * Delete one user's progress in the approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            if ($cm = get_coursemodule_from_id('wolvy', $context->instanceid)) {
                $DB->delete_records('wolvy_progress', ['wolvyid' => $cm->instance, 'userid' => $userid]);
            }
        }
    }

    /**
     * Delete several users' progress in a context.
     *
     * @param approved_userlist $userlist Approved users.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module || !$userlist->get_userids()) {
            return;
        }
        $cm = get_coursemodule_from_id('wolvy', $context->instanceid);
        if (!$cm) {
            return;
        }

        [$usersql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params['wolvyid'] = $cm->instance;
        $DB->delete_records_select('wolvy_progress', "wolvyid = :wolvyid AND userid $usersql", $params);
    }
}
