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

namespace enrolcond_profile;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Tests of the user profile condition.
 *
 * @package    enrolcond_profile
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(condition::class)]
final class condition_test extends \advanced_testcase {
    /**
     * Cases of is_met for a user with email "Ana.Silva@IFRN.edu.br" and an empty department.
     *
     * @return array
     */
    public static function is_met_provider(): array {
        return [
            'equals, ignoring case and spaces' => ['email', 'isequalto', ' ana.silva@ifrn.edu.br ', true],
            'equals, different' => ['email', 'isequalto', 'ana@ifrn.edu.br', false],
            'contains' => ['email', 'contains', '@IFRN', true],
            'contains, missing' => ['email', 'contains', '@ufrn', false],
            'does not contain' => ['email', 'doesnotcontain', '@ufrn', true],
            'does not contain, present' => ['email', 'doesnotcontain', 'silva', false],
            'starts with' => ['email', 'startswith', 'ana.', true],
            'starts with, no' => ['email', 'startswith', 'silva', false],
            'ends with' => ['email', 'endswith', '.edu.br', true],
            'ends with, no' => ['email', 'endswith', '.com', false],
            'is empty' => ['department', 'isempty', '', true],
            'is empty, filled' => ['email', 'isempty', '', false],
            'is not empty' => ['email', 'isnotempty', '', true],
            'is not empty, empty' => ['department', 'isnotempty', '', false],
            'unknown operator' => ['email', 'xor', 'x', false],
            'field not allowed' => ['password', 'isnotempty', '', false],
            'unknown custom field' => ['profile_field_missing', 'isempty', '', false],
        ];
    }

    /**
     * Test is_met with standard fields.
     *
     * @param string $field
     * @param string $operator
     * @param string $value
     * @param bool $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('is_met_provider')]
    public function test_is_met(string $field, string $operator, string $value, bool $expected): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['email' => 'Ana.Silva@IFRN.edu.br', 'department' => '']);
        $config = ['field' => $field, 'operator' => $operator, 'value' => $value];
        $this->assertSame($expected, (new condition())->is_met((int) $user->id, new \stdClass(), $config));
    }

    public function test_custom_field(): void {
        $this->resetAfterTest();
        $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text', 'shortname' => 'campus', 'name' => 'Campus <b>name</b>', 'defaultdata' => 'Natal',
        ]);
        $user = $this->getDataGenerator()->create_user(['profile_field_campus' => 'Mossoró']);
        $other = $this->getDataGenerator()->create_user();
        $condition = new condition();
        $config = ['field' => 'profile_field_campus', 'operator' => 'isequalto', 'value' => 'mossoró'];

        $this->assertTrue($condition->is_met((int) $user->id, new \stdClass(), $config));
        $this->assertFalse($condition->is_met((int) $other->id, new \stdClass(), $config));
        // Users without a value get the default.
        $config['value'] = 'Natal';
        $this->assertTrue($condition->is_met((int) $other->id, new \stdClass(), $config));

        $this->assertArrayHasKey('profile_field_campus', $condition->get_fields());
        $this->assertSame([], $condition->validate_config($config));
        $this->assertSame('Your Campus name is "Natal"', $condition->get_description($config, false));
    }

    public function test_description_is_escaped(): void {
        $this->resetAfterTest();
        $condition = new condition();
        $this->assertSame(
            'Your Email address contains "&lt;script&gt;"',
            $condition->get_description(['field' => 'email', 'operator' => 'contains', 'value' => '<script>'], false)
        );
        $this->assertSame(
            'Your Department is empty',
            $condition->get_summary(['field' => 'department', 'operator' => 'isempty', 'value' => ''])
        );
    }

    public function test_validate_config(): void {
        $this->resetAfterTest();
        $condition = new condition();
        $this->assertSame([], $condition->validate_config(['field' => 'email', 'operator' => 'contains', 'value' => 'x']));
        $this->assertSame([], $condition->validate_config(['field' => 'email', 'operator' => 'isempty', 'value' => '']));
        $errors = $condition->validate_config(['field' => 'password', 'operator' => 'xor', 'value' => '']);
        $this->assertArrayHasKey('field', $errors);
        $this->assertArrayHasKey('operator', $errors);
        $this->assertArrayHasKey('value', $condition->validate_config(['field' => 'email', 'operator' => 'contains']));
    }

    public function test_form(): void {
        $this->resetAfterTest();
        $condition = new condition();
        $this->assertSame(
            ['field' => 'email', 'operator' => 'isempty', 'value' => ''],
            $condition->config_from_form_data((object) ['field' => 'email', 'operator' => 'isempty', 'value' => 'ignored'])
        );
        $this->assertSame(
            ['field' => 'city', 'operator' => 'contains', 'value' => 'Natal'],
            $condition->config_from_form_data((object) ['field' => 'city', 'operator' => 'contains', 'value' => ' Natal '])
        );
        $form = new \MoodleQuickForm('test', 'post', '');
        $condition->config_form_definition($form);
        $this->assertTrue($form->elementExists('field'));
        $this->assertTrue($form->elementExists('operator'));
        $this->assertTrue($form->elementExists('value'));
        $this->assertSame([\core\event\user_updated::class], $condition->get_observed_events());
        $this->assertSame('profile', $condition->get_type());
    }
}
