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

namespace local_enrolconditions\condition;

use MoodleQuickForm;
use stdClass;

/**
 * Base class of every condition.
 *
 * Each subplugin enrolcond_<name> provides the class \enrolcond_<name>\condition extending this one.
 * Other plugins may provide conditions through the hook \local_enrolconditions\hook\collect_conditions;
 * their classes must override get_type() and get_name().
 * Conditions are stateless: the configuration of each use comes in $config, an array that is stored
 * as JSON in the condition tree of the enrolment instance.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /**
     * Whether the user meets the condition.
     *
     * @param int $userid User being checked.
     * @param stdClass $instance Enrolment instance (record of the enrol table).
     * @param array $config Configuration of this use of the condition.
     * @return bool
     */
    abstract public function is_met(int $userid, stdClass $instance, array $config): bool;

    /**
     * Text shown to the user about this condition.
     *
     * @param array $config Configuration of this use of the condition.
     * @param bool $met Whether the user meets it; false gives the text shown when self enrolment is refused.
     * @return string Plain text or HTML already formatted for display.
     */
    abstract public function get_description(array $config, bool $met): string;

    /**
     * Adds the configuration fields of the condition to a form.
     *
     * The form is shown in a modal for one condition at a time, so the field names do not need a prefix.
     *
     * @param MoodleQuickForm $mform
     */
    abstract public function config_form_definition(MoodleQuickForm $mform): void;

    /**
     * Turns the submitted form data into the configuration stored in the tree.
     *
     * @param stdClass $data Data from the form.
     * @return array Configuration, made of scalars and arrays only (it is stored as JSON).
     */
    abstract public function config_from_form_data(stdClass $data): array;

    /**
     * Validates a configuration.
     *
     * Runs on every save, including trees sent by JavaScript or restored from a backup,
     * so it must not rely on the form validation.
     *
     * @param array $config
     * @return array<string, string> Errors keyed by form field name; empty when valid.
     */
    abstract public function validate_config(array $config): array;

    /**
     * Turns a stored configuration into default values for the configuration form.
     *
     * @param array $config
     * @return array Values keyed by form field name.
     */
    public function config_to_form_data(array $config): array {
        return $config;
    }

    /**
     * Form validation of the configuration fields.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string> Errors keyed by form field name.
     */
    public function config_form_validation(array $data, array $files): array {
        return $this->validate_config($this->config_from_form_data((object) $data));
    }

    /**
     * Events that may change the result of the condition for a user.
     *
     * The plugin that provides the condition must also declare these events in its own db/events.php,
     * pointing to the observer of the plugin that evaluates the conditions (for enrol_conditional,
     * \enrol_conditional\observer::condition_event); Moodle does not register observers at run time.
     *
     * @return string[] Fully qualified event class names.
     */
    public function get_observed_events(): array {
        return [];
    }

    /**
     * Identifier of the condition type, stored in the condition trees.
     *
     * For a subplugin it is the subplugin name without the enrolcond_ prefix. Conditions provided
     * through the hook must override it with an identifier that starts with their component name.
     *
     * @return string
     */
    public function get_type(): string {
        $namespace = explode('\\', static::class)[0];
        return str_starts_with($namespace, 'enrolcond_') ? substr($namespace, strlen('enrolcond_')) : $namespace;
    }

    /**
     * Human readable name of the condition type.
     *
     * Defaults to the pluginname string of the component of the class.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('pluginname', explode('\\', static::class)[0]);
    }
}
