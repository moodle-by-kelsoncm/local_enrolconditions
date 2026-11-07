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

namespace local_enrolconditions\fixtures;

use local_enrolconditions\hook\collect_conditions;
use MoodleQuickForm;
use stdClass;

/**
 * Condition provided through the hook, for unit tests: met by every user.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hooked_condition extends \local_enrolconditions\condition\base {
    /**
     * Constructor.
     *
     * @param string $type Type returned by get_type().
     */
    public function __construct(
        /** @var string Type returned by get_type(). */
        protected string $type = 'local_enrolconditions_hooked',
    ) {
    }

    /**
     * Hook callback that adds this condition.
     *
     * @param collect_conditions $hook
     */
    public static function callback(collect_conditions $hook): void {
        $hook->add_condition(new self());
    }

    #[\Override]
    public function get_type(): string {
        return $this->type;
    }

    #[\Override]
    public function get_name(): string {
        return 'Hooked condition';
    }

    #[\Override]
    public function is_met(int $userid, stdClass $instance, array $config): bool {
        return true;
    }

    #[\Override]
    public function get_description(array $config, bool $met): string {
        return 'Always met';
    }

    #[\Override]
    public function config_form_definition(MoodleQuickForm $mform): void {
    }

    #[\Override]
    public function config_from_form_data(stdClass $data): array {
        return [];
    }

    #[\Override]
    public function validate_config(array $config): array {
        return [];
    }
}
