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

namespace local_enrolconditions\output;

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use local_enrolconditions\tree_result;

/**
 * List of the conditions a user does not meet, shown when an action is refused.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class failures implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param tree_result $result Evaluation result.
     * @param string $intro Text shown before the list (plain text).
     */
    public function __construct(
        /** @var tree_result Evaluation result. */
        protected tree_result $result,
        /** @var string Text shown before the list. */
        protected string $intro,
    ) {
    }

    /**
     * Data for the template.
     *
     * The messages come from the conditions, which return text already formatted for display.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'intro' => $this->intro,
            'items' => $this->export_items($this->result->get_failures()),
        ];
    }

    /**
     * Converts the nested failures into template items.
     *
     * @param array $failures
     * @return array
     */
    protected function export_items(array $failures): array {
        $items = [];
        foreach ($failures as $failure) {
            $children = $this->export_items($failure['children']);
            $items[] = [
                'message' => $failure['message'],
                'haschildren' => !empty($children),
                'items' => $children,
            ];
        }
        return $items;
    }

    /**
     * The same list as plain text, for web services.
     *
     * @return string
     */
    public function to_text(): string {
        $lines = [$this->intro];
        $this->append_lines($this->result->get_failures(), 1, $lines);
        return implode("\n", $lines);
    }

    /**
     * Appends one line per failure, indented by level.
     *
     * @param array $failures
     * @param int $level
     * @param string[] $lines
     */
    protected function append_lines(array $failures, int $level, array &$lines): void {
        foreach ($failures as $failure) {
            $lines[] = str_repeat('  ', $level - 1) . '- ' . html_to_text($failure['message'], 0, false);
            $this->append_lines($failure['children'], $level + 1, $lines);
        }
    }
}
