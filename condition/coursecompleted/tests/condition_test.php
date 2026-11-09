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

namespace enrolcond_coursecompleted;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Tests of the course completed condition.
 *
 * @package    enrolcond_coursecompleted
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(condition::class)]
final class condition_test extends \advanced_testcase {
    public function test_is_met(): void {
        global $CFG;
        require_once($CFG->dirroot . '/completion/completion_completion.php');
        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $completed = $this->getDataGenerator()->create_user();
        $started = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($completed->id, $course->id);
        $this->getDataGenerator()->enrol_user($started->id, $course->id);

        $sink = $this->redirectEvents();
        (new \completion_completion(['userid' => $completed->id, 'course' => $course->id]))->mark_complete();
        (new \completion_completion(['userid' => $started->id, 'course' => $course->id]))->mark_inprogress();
        $events = $sink->get_events();
        $sink->close();

        $condition = new condition();
        $config = ['courseid' => (int) $course->id];
        $this->assertTrue($condition->is_met((int) $completed->id, new \stdClass(), $config));
        $this->assertFalse($condition->is_met((int) $started->id, new \stdClass(), $config));
        $this->assertFalse($condition->is_met((int) $completed->id, new \stdClass(), []));

        // The event observed by the condition concerns the user who completed.
        $observed = array_filter($events, fn($e) => in_array(get_class($e), $condition->get_observed_events(), true));
        $this->assertCount(1, $observed);
        $this->assertEquals($completed->id, reset($observed)->relateduserid);
    }

    public function test_description(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Python <i>101</i>']);
        $condition = new condition();
        $this->assertSame('You have completed the course Python 101', $condition->get_summary(['courseid' => $course->id]));
        $this->assertSame(
            'You have completed a course that no longer exists (id 999999)',
            $condition->get_description(['courseid' => 999999], false)
        );
    }

    public function test_validation_and_form(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $condition = new condition();
        $this->assertSame([], $condition->validate_config(['courseid' => $course->id]));
        $this->assertArrayHasKey('courseid', $condition->validate_config(['courseid' => SITEID]));
        $this->assertArrayHasKey('courseid', $condition->validate_config(['courseid' => 999999]));
        $this->assertArrayHasKey('courseid', $condition->validate_config([]));

        $this->assertSame(['courseid' => 7], $condition->config_from_form_data((object) ['courseid' => '7']));
        $this->assertSame(['courseid' => 8], $condition->config_from_form_data((object) ['courseid' => ['8']]));
        $form = new \MoodleQuickForm('test', 'post', '');
        $condition->config_form_definition($form);
        $this->assertTrue($form->elementExists('courseid'));
        $this->assertSame('coursecompleted', $condition->get_type());
    }
}
