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

/**
 * Result of the evaluation of a condition tree for a user.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tree_result {
    /**
     * Constructor.
     *
     * @param bool|null $value True when met, false when not met, null when unknown.
     * @param array $failures Explanations of what is not met: ['message' => string, 'children' => array].
     */
    public function __construct(
        /** @var bool|null True when met, false when not met, null when unknown. */
        protected ?bool $value,
        /** @var array Explanations of what is not met. */
        protected array $failures,
    ) {
    }

    /**
     * Whether the tree is met for sure.
     *
     * @return bool
     */
    public function is_met(): bool {
        return $this->value === true;
    }

    /**
     * Whether the tree is not met for sure (false, not unknown).
     *
     * @return bool
     */
    public function is_not_met(): bool {
        return $this->value === false;
    }

    /**
     * Whether the result depends on a condition whose type is not installed.
     *
     * @return bool
     */
    public function is_unknown(): bool {
        return $this->value === null;
    }

    /**
     * Raw value: true, false or null (unknown).
     *
     * @return bool|null
     */
    public function get_value(): ?bool {
        return $this->value;
    }

    /**
     * Explanations of what is not met, as a nested list.
     *
     * @return array List of ['message' => string, 'children' => array].
     */
    public function get_failures(): array {
        return $this->value === true ? [] : $this->failures;
    }
}
