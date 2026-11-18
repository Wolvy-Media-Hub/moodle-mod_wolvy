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

namespace mod_wolvy;

use mod_wolvy\external\report_progress;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Tests for progress reporting and the "watch at least" completion rule.
 *
 * @package   mod_wolvy
 * @category  test
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \mod_wolvy\external\report_progress
 * @covers    \mod_wolvy\completion\custom_completion
 */
final class report_progress_test extends \externallib_advanced_testcase {

    /**
     * Call the web service as the current user and clean its answer.
     *
     * @param int $cmid Course module id.
     * @param float $percent Percent watched.
     * @return array
     */
    private function report(int $cmid, float $percent): array {
        return \core_external\external_api::clean_returnvalue(
            report_progress::execute_returns(),
            report_progress::execute($cmid, $percent)
        );
    }

    /**
     * Only the best value is kept, and completion follows the threshold.
     */
    public function test_best_value_and_completion(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $wolvy = $this->getDataGenerator()->create_module('wolvy', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionpercent' => 80,
        ]);
        $cm = get_coursemodule_from_instance('wolvy', $wolvy->id);
        $this->setUser($student);

        $result = $this->report($cm->id, 42.7);
        $this->assertSame(42, $result['percent']);
        $this->assertFalse($result['complete']);

        // A shorter later visit does not lower it.
        $result = $this->report($cm->id, 10);
        $this->assertSame(42, $result['percent']);

        $result = $this->report($cm->id, 85);
        $this->assertSame(85, $result['percent']);
        $this->assertTrue($result['complete']);

        $cminfo = get_fast_modinfo($course)->get_cm($cm->id);
        $completion = new \completion_info($course);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_data($cminfo, false, $student->id)->completionstate);
        $this->assertEquals(1, $DB->count_records('wolvy_progress', ['wolvyid' => $wolvy->id]));
    }

    /**
     * Values outside 0 to 100 are clamped.
     */
    public function test_clamped(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $wolvy = $this->getDataGenerator()->create_module('wolvy', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('wolvy', $wolvy->id);
        $this->setUser($student);

        $result = $this->report($cm->id, 500);
        $this->assertSame(100, $result['percent']);
    }

    /**
     * Someone who cannot view the activity cannot report.
     */
    public function test_not_enrolled(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $wolvy = $this->getDataGenerator()->create_module('wolvy', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('wolvy', $wolvy->id);
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\require_login_exception::class);
        report_progress::execute($cm->id, 50);
    }
}
