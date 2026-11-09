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

namespace enrolcond_cohort;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Tests of the cohort condition.
 *
 * @package    enrolcond_cohort
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(condition::class)]
final class condition_test extends \advanced_testcase {
    public function test_is_met(): void {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
        $this->resetAfterTest();
        $cohort = $this->getDataGenerator()->create_cohort();
        $member = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        cohort_add_member($cohort->id, $member->id);
        $condition = new condition();
        $config = ['cohortid' => (int) $cohort->id];

        $this->assertTrue($condition->is_met((int) $member->id, new \stdClass(), $config));
        $this->assertFalse($condition->is_met((int) $other->id, new \stdClass(), $config));
        $this->assertFalse($condition->is_met((int) $member->id, new \stdClass(), []));
    }

    public function test_description(): void {
        $this->resetAfterTest();
        $cohort = $this->getDataGenerator()->create_cohort(['name' => 'Students <b>2026</b>']);
        $condition = new condition();
        $this->assertSame('You are a member of the cohort Students 2026', $condition->get_summary(['cohortid' => $cohort->id]));
        $this->assertSame(
            'You are a member of a cohort that no longer exists (id 999999)',
            $condition->get_description(['cohortid' => 999999], false)
        );
    }

    public function test_validation(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $category = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $systemcohort = $this->getDataGenerator()->create_cohort();
        $othercategory = $this->getDataGenerator()->create_category();
        $hidden = $this->getDataGenerator()->create_cohort([
            'contextid' => \context_coursecat::instance($othercategory->id)->id,
        ]);
        $PAGE->set_context(\context_course::instance($course->id));
        $condition = new condition();

        $this->assertSame([], $condition->validate_config(['cohortid' => $systemcohort->id]));
        $this->assertArrayHasKey('cohortid', $condition->validate_config(['cohortid' => 0]));
        $this->assertArrayHasKey('cohortid', $condition->validate_config(['cohortid' => 999999]));

        $options = $condition->get_cohort_options();
        $this->assertArrayHasKey($systemcohort->id, $options);
        $this->assertArrayNotHasKey($hidden->id, $options);
        $this->assertSame([], $condition->config_form_validation(['cohortid' => $systemcohort->id], []));
        $this->assertArrayHasKey('cohortid', $condition->config_form_validation(['cohortid' => $hidden->id], []));
    }

    public function test_form(): void {
        $this->resetAfterTest();
        $condition = new condition();
        $this->assertSame(['cohortid' => 5], $condition->config_from_form_data((object) ['cohortid' => '5']));
        $form = new \MoodleQuickForm('test', 'post', '');
        $condition->config_form_definition($form);
        $this->assertTrue($form->elementExists('cohortid'));
        $this->assertTrue($form->elementExists('nocohorts'));
        $this->assertContains(\core\event\cohort_member_added::class, $condition->get_observed_events());
        $this->assertSame('cohort', $condition->get_type());
    }
}
