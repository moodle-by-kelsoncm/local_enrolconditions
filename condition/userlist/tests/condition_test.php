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

namespace enrolcond_userlist;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Tests of the list of usernames condition.
 *
 * @package    enrolcond_userlist
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(condition::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_enrolconditions\local\usernames::class)]
final class condition_test extends \advanced_testcase {
    public function test_is_met(): void {
        $this->resetAfterTest();
        $ana = $this->getDataGenerator()->create_user(['username' => 'ana']);
        $bia = $this->getDataGenerator()->create_user(['username' => 'bia']);
        $condition = new condition();
        $config = $condition->config_from_form_data((object) ['usernames' => " ANA, carla;\n\ndavi  ana"]);

        $this->assertSame(['usernames' => ['ana', 'carla', 'davi']], $config);
        $this->assertTrue($condition->is_met((int) $ana->id, new \stdClass(), $config));
        $this->assertFalse($condition->is_met((int) $bia->id, new \stdClass(), $config));
        $this->assertFalse($condition->is_met((int) $ana->id, new \stdClass(), []));
        $this->assertFalse($condition->is_met(999999, new \stdClass(), $config));
    }

    public function test_description_and_form(): void {
        $condition = new condition();
        $this->assertSame('Your username is in a list of 2 users', $condition->get_summary(['usernames' => ['a', 'b']]));
        $this->assertSame(['usernames' => "a\nb"], $condition->config_to_form_data(['usernames' => ['a', 'b']]));
        $form = new \MoodleQuickForm('test', 'post', '');
        $condition->config_form_definition($form);
        $this->assertTrue($form->elementExists('usernames'));
        $this->assertSame([], $condition->get_observed_events());
    }

    public function test_validate_config(): void {
        $condition = new condition();
        $this->assertSame([], $condition->validate_config(['usernames' => ['ana', 'joao.silva', '2080882']]));
        $this->assertArrayHasKey('usernames', $condition->validate_config(['usernames' => []]));
        $this->assertArrayHasKey('usernames', $condition->validate_config([]));
        $errors = $condition->validate_config(['usernames' => ['ana', 'bad<user>']]);
        $this->assertStringContainsString('bad&lt;user&gt;', $errors['usernames']);
        $many = array_map(fn($i) => 'u' . $i, range(1, condition::MAX_USERNAMES + 1));
        $this->assertArrayHasKey('usernames', $condition->validate_config(['usernames' => $many]));
    }

    public function test_parse_csv(): void {
        $content = "\xEF\xBB\xBFusername,firstname\r\n\"Ana\",Ana\r\nbia;x\n\n carla\tC\nana\n";
        $this->assertSame(['ana', 'bia', 'carla'], \local_enrolconditions\local\usernames::parse_csv($content));
        $this->assertSame(['x'], \local_enrolconditions\local\usernames::parse_csv("x\n"));
    }
}
