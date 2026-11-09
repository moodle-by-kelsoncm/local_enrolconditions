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

namespace enrolcond_sqlquery;

use enrolcond_sqlquery\local\queries;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Tests of the list from a query condition.
 *
 * @package    enrolcond_sqlquery
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(condition::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(queries::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(admin\setting_query::class)]
final class condition_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // Queries do not run inside transactions, and a failing query would abort the test transaction.
        $this->preventResetByRollback();
        queries::reset_caches();
    }

    public function test_not_run_inside_transaction(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->define(1, 'All', 'SELECT username FROM {user}');
        $transaction = $DB->start_delegated_transaction();
        try {
            (new condition())->is_met((int) $user->id, (object) ['courseid' => $course->id], ['query' => '1']);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertSame('errortransaction', $e->errorcode);
        }
        $transaction->allow_commit();
    }

    /**
     * Defines a query in a slot.
     *
     * @param int $slot
     * @param string $name
     * @param string $sql
     */
    protected function define(int $slot, string $name, string $sql): void {
        set_config('query' . $slot . 'name', $name, queries::COMPONENT);
        set_config('query' . $slot . 'sql', $sql, queries::COMPONENT);
    }

    /**
     * Texts that check_text() accepts or refuses.
     *
     * @return array
     */
    public static function check_text_provider(): array {
        return [
            'select' => ["SELECT username FROM {user}", true],
            'with' => ["WITH u AS (SELECT username FROM {user}) SELECT username FROM u", true],
            'lower case and params' => ["select u.username from {user} u where u.id > :courseid", true],
            'not a select' => ["DELETE FROM {user}", false],
            'two statements' => ["SELECT username FROM {user}; DELETE FROM {user}", false],
            'comment' => ["SELECT username FROM {user} -- x", false],
            'block comment' => ["SELECT username /* x */ FROM {user}", false],
            'select into' => ["SELECT username INTO x FROM {user}", false],
            'sleep' => ["SELECT username FROM {user} WHERE pg_sleep(10) IS NULL", false],
            'update in a cte' => ["WITH x AS (UPDATE {user} SET deleted = 1 RETURNING username) SELECT username FROM x", false],
        ];
    }

    /**
     * Test check_text.
     *
     * @param string $sql
     * @param bool $accepted
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('check_text_provider')]
    public function test_check_text(string $sql, bool $accepted): void {
        $this->assertSame($accepted, queries::check_text($sql) === null);
    }

    public function test_is_met(): void {
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user(['username' => 'student']);
        $outsider = $this->getDataGenerator()->create_user(['username' => 'outsider']);
        $this->getDataGenerator()->enrol_user($student->id, $other->id);
        // Users enrolled in any course other than this one.
        $this->define(2, 'Enrolled elsewhere', "SELECT u.username FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id JOIN {enrol} e ON e.id = ue.enrolid
            WHERE e.courseid <> :courseid");
        $condition = new condition();
        $instance = (object) ['courseid' => $course->id];

        $this->assertTrue($condition->is_met((int) $student->id, $instance, ['query' => '2']));
        $this->assertFalse($condition->is_met((int) $outsider->id, $instance, ['query' => '2']));
        $this->assertFalse($condition->is_met((int) $student->id, (object) ['courseid' => $other->id], ['query' => '2']));
        $this->assertSame('You are in the list "Enrolled elsewhere"', $condition->get_summary(['query' => '2']));
        $this->assertSame([], $condition->validate_config(['query' => '2']));
    }

    public function test_cache(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user(['username' => 'late']);
        $this->define(1, 'Late', "SELECT username FROM {user} WHERE city = 'Late'");
        $condition = new condition();
        $instance = (object) ['courseid' => $course->id];

        $this->assertFalse($condition->is_met((int) $user->id, $instance, ['query' => '1']));
        $DB->set_field('user', 'city', 'Late', ['id' => $user->id]);
        // Cached across requests while the time to keep results lasts.
        queries::reset_caches();
        set_config('cachettl', HOURSECS, queries::COMPONENT);
        $this->assertTrue($condition->is_met((int) $user->id, $instance, ['query' => '1']));
        $DB->set_field('user', 'city', '', ['id' => $user->id]);
        $this->assertTrue($condition->is_met((int) $user->id, $instance, ['query' => '1']));
        set_config('cachettl', 0, queries::COMPONENT);
        queries::reset_caches();
        $this->assertFalse($condition->is_met((int) $user->id, $instance, ['query' => '1']));
    }

    public function test_missing_or_failing_query_is_unknown(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/enrolconditions/tests/fixtures/fake_condition.php');
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $instance = (object) ['id' => 1, 'courseid' => $course->id];
        $this->define(1, 'Broken', "SELECT nothing FROM {user}");

        $tree = \local_enrolconditions\tree::from_array(['op' => 'and', 'children' => [
            ['type' => 'sqlquery', 'config' => ['query' => '1']],
        ]]);
        $this->assertTrue($tree->evaluate((int) $user->id, $instance)->is_unknown());
        $this->assertDebuggingCalled();

        $tree = \local_enrolconditions\tree::from_array(['op' => 'and', 'children' => [
            ['type' => 'sqlquery', 'config' => ['query' => '9']],
        ]]);
        $this->assertTrue($tree->evaluate((int) $user->id, $instance)->is_unknown());
        $this->assertDebuggingCalled();
        $this->assertSame('You are in a list that no longer exists', (new condition())->get_summary(['query' => '9']));
        $this->assertArrayHasKey('query', (new condition())->validate_config(['query' => '9']));
    }

    public function test_setting_validation(): void {
        $setting = new admin\setting_query('enrolcond_sqlquery/query1sql', 'Query', '');
        $this->assertTrue($setting->validate(''));
        $this->assertTrue($setting->validate('SELECT username FROM {user} WHERE id = :courseid'));
        $this->assertSame(get_string('errorselect', queries::COMPONENT), $setting->validate('DELETE FROM {user}'));
        $this->assertStringStartsWith('The query failed', $setting->validate('SELECT nothing FROM {user}'));
    }

    public function test_slots_and_form(): void {
        $this->assertSame(queries::DEFAULT_SLOTS, queries::get_slots());
        set_config('slots', 100, queries::COMPONENT);
        $this->assertSame(queries::MAX_SLOTS, queries::get_slots());
        $this->define(1, 'Without SQL', '');
        $this->define(3, 'Teachers', 'SELECT username FROM {user}');
        $this->assertSame(['3'], array_map('strval', array_keys(queries::get_queries())));

        $form = new \MoodleQuickForm('test', 'post', '');
        (new condition())->config_form_definition($form);
        $this->assertTrue($form->elementExists('query'));
        $this->assertFalse($form->elementExists('noqueries'));
        $this->assertSame(['query' => '3'], (new condition())->config_from_form_data((object) ['query' => 3]));
    }
}
