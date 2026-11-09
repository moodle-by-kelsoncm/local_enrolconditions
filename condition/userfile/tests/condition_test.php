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

namespace enrolcond_userfile;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use enrolcond_userfile\privacy\provider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Tests of the file of usernames condition, its cleanup task and its privacy provider.
 *
 * @package    enrolcond_userfile
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(condition::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(task\cleanup_files::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class condition_test extends \core_privacy\tests\provider_testcase {
    /**
     * Puts a file in a new draft area of the current user.
     *
     * @param string $content
     * @param string $filename
     * @return int Draft item id.
     */
    protected function draft(string $content, string $filename = 'students.csv'): int {
        global $USER;
        $draftitemid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => $filename,
        ], $content);
        return $draftitemid;
    }

    /**
     * Saves a condition as its form would.
     *
     * @param \stdClass $course
     * @param string $content
     * @param int $fileid Existing file id, 0 for a new condition.
     * @return array Configuration.
     */
    protected function save(\stdClass $course, string $content, int $fileid = 0): array {
        $condition = new condition();
        $data = (object) ['fileid' => $fileid, 'usernamesfile' => $this->draft($content)];
        $this->assertSame([], $condition->config_form_validation((array) $data, []));
        $config = $condition->config_after_submission(
            $condition->config_from_form_data($data),
            $data,
            \context_course::instance($course->id)
        );
        $this->assertSame([], $condition->validate_config($config));
        return $config;
    }

    public function test_save_and_evaluate(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $ana = $this->getDataGenerator()->create_user(['username' => 'ana']);
        $bia = $this->getDataGenerator()->create_user(['username' => 'bia']);
        $instance = (object) ['courseid' => $course->id];
        $condition = new condition();

        $config = $this->save($course, "username,name\nANA,Ana\ncarla,Carla\n");
        $this->assertGreaterThan(0, $config['fileid']);
        $this->assertSame('students.csv', $config['filename']);
        $this->assertSame(2, $config['count']);
        $this->assertTrue($condition->is_met((int) $ana->id, $instance, $config));
        $this->assertFalse($condition->is_met((int) $bia->id, $instance, $config));
        $this->assertSame('Your username is in the file students.csv (2 users)', $condition->get_summary($config));

        // Replacing the file keeps the id and changes the list.
        $config2 = $this->save($course, "bia\n", $config['fileid']);
        $this->assertSame($config['fileid'], $config2['fileid']);
        $this->assertTrue($condition->is_met((int) $bia->id, $instance, $config2));
        $this->assertFalse($condition->is_met((int) $ana->id, $instance, $config2));

        // The form shows the current file.
        $data = $condition->config_to_form_data_in_context($config2, \context_course::instance($course->id));
        $this->assertSame($config['fileid'], $data['fileid']);
        global $USER;
        $draftfiles = get_file_storage()->get_area_files(
            \context_user::instance($USER->id)->id,
            'user',
            'draft',
            $data['usernamesfile'],
            'id',
            false
        );
        $this->assertCount(1, $draftfiles);
        $this->assertSame("bia
", reset($draftfiles)->get_content());
    }

    public function test_missing_file_is_unknown(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->expectException(\moodle_exception::class);
        (new condition())->is_met((int) $user->id, (object) ['courseid' => $course->id], ['fileid' => 5, 'count' => 1]);
    }

    public function test_form_validation(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $condition = new condition();
        $this->assertArrayHasKey('usernamesfile', $condition->config_form_validation(['usernamesfile' => 0], []));
        $this->assertArrayHasKey('usernamesfile', $condition->config_form_validation(
            ['usernamesfile' => $this->draft("username\n\n")],
            []
        ));
        $errors = $condition->config_form_validation(['usernamesfile' => $this->draft("ana\nbad user<\n")], []);
        $this->assertStringContainsString('bad user&lt;', $errors['usernamesfile']);
        $this->assertArrayHasKey('usernamesfile', $condition->validate_config(['fileid' => 0, 'count' => 3]));

        $form = new \MoodleQuickForm('test', 'post', '');
        $condition->config_form_definition($form);
        $this->assertTrue($form->elementExists('usernamesfile'));
        $this->assertTrue($form->elementExists('fileid'));
    }

    public function test_cleanup_task(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $used = $this->save($course, "ana\n");
        $unused = $this->save($course, "bia\n");
        $recent = $this->save($course, "carla\n");
        $DB->set_field('enrol', 'customtext2', json_encode(['op' => 'and', 'children' => [
            ['type' => 'userfile', 'config' => $used],
        ]]), ['courseid' => $course->id, 'enrol' => 'manual']);
        $DB->set_field('enrol', 'enrol', 'conditional', ['courseid' => $course->id, 'enrol' => 'manual']);
        $DB->set_field_select(
            'files',
            'timemodified',
            time() - 2 * DAYSECS,
            'component = ? AND itemid <> ?',
            [condition::COMPONENT, $recent['fileid']]
        );

        $this->expectOutputRegex('/1 files of usernames deleted/');
        (new task\cleanup_files())->execute();
        $context = \context_course::instance($course->id);
        $this->assertNotNull(condition::get_file($used, $context));
        $this->assertNull(condition::get_file($unused, $context));
        $this->assertNotNull(condition::get_file($recent, $context));
    }

    public function test_privacy(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $ana = $this->getDataGenerator()->create_user(['username' => 'ana']);
        $bia = $this->getDataGenerator()->create_user(['username' => 'bia']);
        $other = $this->getDataGenerator()->create_user(['username' => 'other']);
        $config = $this->save($course, "username\nana\nbia,B\n");

        $this->assertEquals([$context->id], provider::get_contexts_for_userid($ana->id)->get_contextids());
        $this->assertSame([], provider::get_contexts_for_userid($other->id)->get_contextids());
        $userlist = new userlist($context, condition::COMPONENT);
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([$ana->id, $bia->id], $userlist->get_userids());

        provider::export_user_data(new approved_contextlist($ana, condition::COMPONENT, [$context->id]));
        $data = writer::with_context($context)->get_data([get_string('pluginname', condition::COMPONENT)]);
        $this->assertSame(['students.csv'], $data->files);

        provider::delete_data_for_user(new approved_contextlist($ana, condition::COMPONENT, [$context->id]));
        $this->assertSame("username\nbia,B\n", condition::get_file($config, $context)->get_content());

        provider::delete_data_for_users(new approved_userlist($context, condition::COMPONENT, [$bia->id]));
        $this->assertSame("username\n", condition::get_file($config, $context)->get_content());

        provider::delete_data_for_all_users_in_context($context);
        $this->assertNull(condition::get_file($config, $context));
    }
}
