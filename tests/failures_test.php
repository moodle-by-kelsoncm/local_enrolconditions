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

use local_enrolconditions\output\failures;

/**
 * Tests of the output that lists the conditions not met.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(failures::class)]
final class failures_test extends \advanced_testcase {
    /**
     * Result with one failure and a group of two.
     *
     * @return tree_result
     */
    protected static function sample_result(): tree_result {
        return new tree_result(false, [
            ['message' => 'First <b>bold</b>', 'children' => []],
            ['message' => 'One of:', 'children' => [
                ['message' => 'Child A', 'children' => []],
                ['message' => 'Child B', 'children' => []],
            ]],
        ]);
    }

    public function test_render(): void {
        global $PAGE;
        $html = $PAGE->get_renderer('core')->render(new failures(self::sample_result(), 'Intro <script>'));

        $this->assertStringContainsString('Intro &lt;script&gt;', $html);
        $this->assertStringContainsString('First <b>bold</b>', $html);
        $this->assertSame(2, substr_count($html, '<ul'));
        $this->assertStringContainsString('Child B', $html);
    }

    public function test_to_text(): void {
        $text = (new failures(self::sample_result(), 'Intro'))->to_text();
        $this->assertStringNotContainsString('<', $text);
        $this->assertStringContainsString("Intro\n- First", $text);
        $this->assertStringContainsString("\n  - Child A", $text);
    }

    public function test_met_result_has_no_items(): void {
        global $PAGE;
        $result = new tree_result(true, [['message' => 'x', 'children' => []]]);
        $html = $PAGE->get_renderer('core')->render(new failures($result, 'I'));
        $this->assertStringNotContainsString('<li>', $html);
    }
}
