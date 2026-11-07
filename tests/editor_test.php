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

namespace local_enrolconditions;

use local_enrolconditions\condition\manager;
use local_enrolconditions\form\condition_config;
use local_enrolconditions\output\tree_editor;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/fixtures/fake_condition.php');

/**
 * Tests of the condition editor: the modal configuration form and the editor output.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(condition_config::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(tree_editor::class)]
final class editor_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        manager::reset_caches();
        manager::register_for_testing('fake', new \enrolcond_fake\condition());
    }

    #[\Override]
    protected function tearDown(): void {
        manager::reset_caches();
        parent::tearDown();
    }

    public function test_form_returns_config_and_summary(): void {
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $this->setAdminUser();

        $formdata = condition_config::mock_ajax_submit([
            'enrolcondcontextid' => $context->id,
            'enrolcondtype' => 'fake',
            'enrolcondconfig' => '',
            'userids' => '4,5',
        ]);
        $form = new condition_config(null, null, 'post', '', [], true, $formdata, true);
        $form->set_data_for_dynamic_submission();
        $this->assertTrue($form->is_validated());
        $result = $form->process_dynamic_submission();

        $this->assertSame(['userids' => [4, 5]], json_decode($result['config'], true));
        $this->assertSame('Not a listed user', $result['summary']);
    }

    public function test_form_validation_uses_condition(): void {
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $this->setAdminUser();

        $formdata = condition_config::mock_ajax_submit([
            'enrolcondcontextid' => $context->id,
            'enrolcondtype' => 'fake',
            'enrolcondconfig' => '',
            'userids' => '',
        ]);
        $form = new condition_config(null, null, 'post', '', [], true, $formdata, true);
        $this->assertFalse($form->is_validated());
    }

    public function test_form_requires_enrolconfig(): void {
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $formdata = condition_config::mock_ajax_submit([
            'enrolcondcontextid' => \context_course::instance($course->id)->id,
            'enrolcondtype' => 'fake',
        ]);
        $this->expectException(\required_capability_exception::class);
        new condition_config(null, null, 'post', '', [], true, $formdata, true);
    }

    public function test_form_refuses_unknown_type(): void {
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();

        $formdata = condition_config::mock_ajax_submit([
            'enrolcondcontextid' => \context_course::instance($course->id)->id,
            'enrolcondtype' => 'missing',
        ]);
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('treeunknowntype', 'local_enrolconditions', 'missing'));
        new condition_config(null, null, 'post', '', [], true, $formdata, true);
    }

    public function test_editor_export(): void {
        global $PAGE;
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $PAGE->set_url('/enrol/instances.php', ['id' => $course->id]);

        $json = json_encode(['op' => 'or', 'negate' => false, 'children' => [
            ['type' => 'fake', 'negate' => true, 'config' => ['userids' => [3]]],
            ['type' => 'missing', 'negate' => false, 'config' => []],
        ]]);
        $editor = new tree_editor(tree::from_json($json), 'customtext2', $context);
        $data = $editor->export_for_template($PAGE->get_renderer('core'));
        $config = json_decode($data['config'], true);

        $this->assertSame('customtext2', $config['fieldname']);
        $this->assertSame($context->id, $config['contextid']);
        $this->assertSame([['type' => 'fake', 'name' => 'Fake condition']], $config['types']);
        $this->assertSame('Not a listed user', $config['tree']['children'][0]['summary']);
        $this->assertTrue($config['tree']['children'][0]['known']);
        $this->assertFalse($config['tree']['children'][1]['known']);
        $this->assertStringNotContainsString('<', $data['config']);
    }
}
