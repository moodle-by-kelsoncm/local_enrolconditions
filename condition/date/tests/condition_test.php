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

namespace enrolcond_date;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Tests of the date condition.
 *
 * @package    enrolcond_date
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(condition::class)]
final class condition_test extends \advanced_testcase {
    /**
     * Cases of is_met with the current time fixed at 1000.
     *
     * @return array
     */
    public static function is_met_provider(): array {
        return [
            'from, before' => [['from' => 1001, 'to' => 0], false],
            'from, at' => [['from' => 1000, 'to' => 0], true],
            'until, before' => [['from' => 0, 'to' => 1001], true],
            'until, at' => [['from' => 0, 'to' => 1000], false],
            'period, inside' => [['from' => 900, 'to' => 1100], true],
            'period, after' => [['from' => 800, 'to' => 900], false],
            'nothing set' => [[], true],
        ];
    }

    /**
     * Test is_met.
     *
     * @param array $config
     * @param bool $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('is_met_provider')]
    public function test_is_met(array $config, bool $expected): void {
        $condition = new condition();
        $condition->set_now(1000);
        $this->assertSame($expected, $condition->is_met(2, new \stdClass(), $config));
    }

    public function test_is_met_with_real_time(): void {
        $condition = new condition();
        $this->assertTrue($condition->is_met(2, new \stdClass(), ['from' => time() - 10, 'to' => time() + HOURSECS]));
    }

    public function test_description(): void {
        $condition = new condition();
        $format = get_string('strftimedatetime', 'langconfig');
        $this->assertSame('From ' . userdate(1000, $format), $condition->get_description(['from' => 1000], false));
        $this->assertSame('Until ' . userdate(2000, $format), $condition->get_description(['to' => 2000], false));
        $this->assertSame(
            'From ' . userdate(1000, $format) . ' until ' . userdate(2000, $format),
            $condition->get_summary(['from' => 1000, 'to' => 2000])
        );
    }

    public function test_validate_config(): void {
        $condition = new condition();
        $this->assertArrayHasKey('from', $condition->validate_config(['from' => 0, 'to' => 0]));
        $this->assertArrayHasKey('to', $condition->validate_config(['from' => 2000, 'to' => 1000]));
        $this->assertArrayHasKey('to', $condition->validate_config(['from' => 1000, 'to' => 1000]));
        $this->assertSame([], $condition->validate_config(['from' => 1000, 'to' => 0]));
        $this->assertSame([], $condition->validate_config(['from' => 1000, 'to' => 2000]));
    }

    public function test_form_data(): void {
        $condition = new condition();
        $this->assertSame(['from' => 1000, 'to' => 0], $condition->config_from_form_data((object) ['from' => '1000']));
        $this->assertSame(['from' => 0, 'to' => 0], $condition->config_from_form_data((object) ['from' => -5]));
        $this->assertSame(['from' => 1, 'to' => 2], $condition->config_to_form_data(['from' => 1, 'to' => 2]));
        $this->assertSame([], $condition->get_observed_events());
        $this->assertSame('date', $condition->get_type());
        $this->assertSame('Date', $condition->get_name());
    }

    public function test_form_definition(): void {
        $form = new \MoodleQuickForm('test', 'post', '');
        (new condition())->config_form_definition($form);
        $this->assertTrue($form->elementExists('from'));
        $this->assertTrue($form->elementExists('to'));
    }
}
