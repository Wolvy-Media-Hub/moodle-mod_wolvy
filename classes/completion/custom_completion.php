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

declare(strict_types=1);

namespace mod_wolvy\completion;

use core_completion\activity_custom_completion;

/**
 * The "watched at least N %" completion rule.
 *
 * The player reports the share of the video actually played in one visit
 * (skipping ahead does not count). The best visit is stored per user.
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * Completion state of a rule for the user.
     *
     * @param string $rule Rule name.
     * @return int COMPLETION_COMPLETE or COMPLETION_INCOMPLETE.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $needed = (int) ($this->cm->customdata['customcompletionrules']['completionpercent'] ?? 0);
        if ($needed <= 0) {
            return COMPLETION_COMPLETE;
        }

        $best = (int) $DB->get_field('wolvy_progress', 'percent', ['wolvyid' => $this->cm->instance, 'userid' => $this->userid]);

        return $best >= $needed ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * The rules this activity defines.
     *
     * @return string[]
     */
    public static function get_defined_custom_rules(): array {
        return ['completionpercent'];
    }

    /**
     * How each rule is described to the user.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $needed = (int) ($this->cm->customdata['customcompletionrules']['completionpercent'] ?? 0);

        return ['completionpercent' => get_string('completiondetail:percent', 'wolvy', $needed)];
    }

    /**
     * Display order of the rules.
     *
     * @return string[]
     */
    public function get_sort_order(): array {
        return ['completionview', 'completionpercent'];
    }
}
