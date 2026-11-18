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

namespace mod_wolvy\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Records how much of a Wolvy video the current user watched.
 *
 * Called from the activity page as the player reports progress. Only the
 * best value is kept, so a later, shorter visit never lowers it. Like
 * anything a browser reports, a determined student can fake it.
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_progress extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'percent' => new external_value(PARAM_FLOAT, 'Share of the video played in this visit, 0 to 100'),
        ]);
    }

    /**
     * Store the progress and update completion.
     *
     * @param int $cmid Course module id.
     * @param float $percent Share played.
     * @return array percent (the best so far) and complete.
     */
    public static function execute(int $cmid, float $percent): array {
        global $CFG, $DB, $USER;
        require_once($CFG->libdir . '/completionlib.php');

        ['cmid' => $cmid, 'percent' => $percent] = self::validate_parameters(
            self::execute_parameters(),
            ['cmid' => $cmid, 'percent' => $percent]
        );

        $cm = get_coursemodule_from_id('wolvy', $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/wolvy:view', $context);

        $percent = (int) floor(max(0.0, min(100.0, $percent)));
        $wolvy = $DB->get_record('wolvy', ['id' => $cm->instance], 'id, course, completionpercent', MUST_EXIST);

        // The guest account is shared by everyone who uses it; nothing is stored for it.
        if (isguestuser()) {
            return ['percent' => $percent, 'complete' => false];
        }

        $record = $DB->get_record('wolvy_progress', ['wolvyid' => $wolvy->id, 'userid' => $USER->id]);
        if (!$record) {
            $record = (object) ['wolvyid' => $wolvy->id, 'userid' => $USER->id, 'percent' => $percent, 'timemodified' => time()];
            $record->id = $DB->insert_record('wolvy_progress', $record);
        } else if ($percent > (int) $record->percent) {
            $record->percent = $percent;
            $record->timemodified = time();
            $DB->update_record('wolvy_progress', $record);
        }

        $complete = false;
        $needed = (int) $wolvy->completionpercent;
        if ($needed > 0) {
            // A cm_info, so the custom rule sees its customdata.
            $cminfo = get_fast_modinfo($cm->course)->get_cm($cm->id);
            $completion = new \completion_info($cminfo->get_course());
            if ($completion->is_enabled($cminfo) == COMPLETION_TRACKING_AUTOMATIC) {
                $completion->update_state($cminfo, COMPLETION_COMPLETE);
            }
            $complete = (int) $record->percent >= $needed;
        }

        return ['percent' => (int) $record->percent, 'complete' => $complete];
    }

    /**
     * What is returned.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'percent' => new external_value(PARAM_INT, 'The best share watched in one visit so far'),
            'complete' => new external_value(PARAM_BOOL, 'Whether the watched rule is met'),
        ]);
    }
}
