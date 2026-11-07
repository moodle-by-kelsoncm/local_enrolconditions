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

namespace local_enrolconditions\hook;

use coding_exception;
use local_enrolconditions\condition\base;

/**
 * Lets any plugin provide conditions, in addition to the enrolcond subplugins.
 *
 * A condition added here must override base::get_type() with an identifier that starts with the
 * frankenstyle name of the plugin that provides it (for example local_myplugin_grade), so it cannot
 * clash with a subplugin or with a condition of another plugin.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\tags('enrol')]
#[\core\attribute\label('Allows plugins to provide conditions for conditional self enrolment')]
final class collect_conditions {
    /** @var array<string, base> Conditions added by the callbacks, keyed by type. */
    private array $conditions = [];

    /**
     * Adds a condition.
     *
     * @param base $condition Condition whose get_type() starts with the component of its class.
     * @throws coding_exception When the type is invalid, not prefixed with the component or already used.
     */
    public function add_condition(base $condition): void {
        $type = $condition->get_type();
        $component = explode('\\', get_class($condition))[0];
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $type) || !str_starts_with($type, $component . '_')) {
            throw new coding_exception("Condition type '{$type}' must start with '{$component}_'");
        }
        if (isset($this->conditions[$type])) {
            throw new coding_exception("Condition type '{$type}' was added twice");
        }
        $this->conditions[$type] = $condition;
    }

    /**
     * Conditions added by the callbacks.
     *
     * @return array<string, base> Keyed by type.
     */
    public function get_conditions(): array {
        return $this->conditions;
    }
}
