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

use core_text;
use MoodleQuickForm;
use stdClass;

/**
 * Condition on a field of the user profile, standard or custom.
 *
 * Configuration: ['field' => standard field name or 'profile_field_' . custom field shortname,
 * 'operator' => one of the OP_ constants, 'value' => text]. Comparisons ignore case and surrounding spaces.
 *
 * @package    enrolcond_profile
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \local_enrolconditions\condition\base {
    /** @var string Prefix of custom profile fields. */
    public const CUSTOM_PREFIX = 'profile_field_';

    /** @var string Is equal to. */
    public const OP_EQUALS = 'isequalto';
    /** @var string Contains. */
    public const OP_CONTAINS = 'contains';
    /** @var string Does not contain. */
    public const OP_NOT_CONTAINS = 'doesnotcontain';
    /** @var string Starts with. */
    public const OP_STARTS = 'startswith';
    /** @var string Ends with. */
    public const OP_ENDS = 'endswith';
    /** @var string Is empty. */
    public const OP_EMPTY = 'isempty';
    /** @var string Is not empty. */
    public const OP_NOT_EMPTY = 'isnotempty';

    /** @var string[] Operators that do not use a value. */
    public const OPS_WITHOUT_VALUE = [self::OP_EMPTY, self::OP_NOT_EMPTY];

    /** @var string[] Standard fields that can be used. */
    public const STANDARD_FIELDS = [
        'firstname', 'lastname', 'email', 'idnumber', 'institution', 'department',
        'city', 'country', 'phone1', 'phone2', 'address', 'auth',
    ];

    /**
     * Operators, with their names.
     *
     * @return string[]
     */
    public function get_operators(): array {
        $operators = [];
        foreach (
            [self::OP_EQUALS, self::OP_CONTAINS, self::OP_NOT_CONTAINS, self::OP_STARTS, self::OP_ENDS,
                self::OP_EMPTY, self::OP_NOT_EMPTY] as $operator
        ) {
            $operators[$operator] = get_string('op_' . $operator, 'enrolcond_profile');
        }
        return $operators;
    }

    /**
     * Fields that can be used, with their names.
     *
     * @return string[]
     */
    public function get_fields(): array {
        global $DB;
        $fields = [];
        foreach (self::STANDARD_FIELDS as $field) {
            $fields[$field] = $field === 'auth'
                ? get_string('authentication')
                : \core_user\fields::get_display_name($field);
        }
        foreach ($DB->get_records('user_info_field', null, 'sortorder ASC', 'id, shortname, name') as $field) {
            $fields[self::CUSTOM_PREFIX . $field->shortname] = format_string(
                $field->name,
                true,
                ['context' => \context_system::instance()]
            );
        }
        return $fields;
    }

    /**
     * Value of a field of a user.
     *
     * @param int $userid
     * @param string $field
     * @return string|null Null when the field does not exist.
     */
    protected function get_user_value(int $userid, string $field): ?string {
        global $CFG;
        if (str_starts_with($field, self::CUSTOM_PREFIX)) {
            require_once($CFG->dirroot . '/user/profile/lib.php');
            $shortname = substr($field, strlen(self::CUSTOM_PREFIX));
            $values = (array) profile_user_record($userid, false);
            return array_key_exists($shortname, $values) ? (string) $values[$shortname] : null;
        }
        if (!in_array($field, self::STANDARD_FIELDS, true)) {
            return null;
        }
        $user = \core_user::get_user($userid, 'id, ' . $field);
        return $user ? (string) $user->$field : null;
    }

    #[\Override]
    public function is_met(int $userid, stdClass $instance, array $config): bool {
        $value = $this->get_user_value($userid, (string) ($config['field'] ?? ''));
        if ($value === null) {
            return false;
        }
        $value = core_text::strtolower(trim($value));
        $expected = core_text::strtolower(trim((string) ($config['value'] ?? '')));
        switch ($config['operator'] ?? '') {
            case self::OP_EQUALS:
                return $value === $expected;
            case self::OP_CONTAINS:
                return $expected === '' || str_contains($value, $expected);
            case self::OP_NOT_CONTAINS:
                return $expected === '' || !str_contains($value, $expected);
            case self::OP_STARTS:
                return str_starts_with($value, $expected);
            case self::OP_ENDS:
                return str_ends_with($value, $expected);
            case self::OP_EMPTY:
                return $value === '';
            case self::OP_NOT_EMPTY:
                return $value !== '';
        }
        return false;
    }

    #[\Override]
    public function get_description(array $config, bool $met): string {
        $field = (string) ($config['field'] ?? '');
        $fields = $this->get_fields();
        $operator = (string) ($config['operator'] ?? '');
        $a = (object) [
            'field' => s($fields[$field] ?? $field),
            'value' => s((string) ($config['value'] ?? '')),
        ];
        if (!array_key_exists($operator, $this->get_operators())) {
            $operator = self::OP_EQUALS;
        }
        return get_string('desc_' . $operator, 'enrolcond_profile', $a);
    }

    #[\Override]
    public function config_form_definition(MoodleQuickForm $mform): void {
        $mform->addElement('select', 'field', get_string('field', 'enrolcond_profile'), $this->get_fields());
        $mform->addElement('select', 'operator', get_string('operator', 'enrolcond_profile'), $this->get_operators());
        $mform->addElement('text', 'value', get_string('value', 'enrolcond_profile'), ['size' => 40]);
        $mform->setType('value', PARAM_TEXT);
        $mform->hideIf('value', 'operator', 'in', self::OPS_WITHOUT_VALUE);
    }

    #[\Override]
    public function config_from_form_data(stdClass $data): array {
        $operator = (string) ($data->operator ?? '');
        return [
            'field' => (string) ($data->field ?? ''),
            'operator' => $operator,
            'value' => in_array($operator, self::OPS_WITHOUT_VALUE, true) ? '' : trim((string) ($data->value ?? '')),
        ];
    }

    #[\Override]
    public function validate_config(array $config): array {
        $errors = [];
        if (!array_key_exists((string) ($config['field'] ?? ''), $this->get_fields())) {
            $errors['field'] = get_string('invalidfield', 'enrolcond_profile');
        }
        $operator = (string) ($config['operator'] ?? '');
        if (!array_key_exists($operator, $this->get_operators())) {
            $errors['operator'] = get_string('invalidoperator', 'enrolcond_profile');
        } else if (!in_array($operator, self::OPS_WITHOUT_VALUE, true) && trim((string) ($config['value'] ?? '')) === '') {
            $errors['value'] = get_string('required');
        }
        return $errors;
    }

    #[\Override]
    public function get_observed_events(): array {
        return [\core\event\user_updated::class];
    }
}
