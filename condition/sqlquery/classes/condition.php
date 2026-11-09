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

namespace enrolcond_sqlquery;

use enrolcond_sqlquery\local\queries;
use MoodleQuickForm;
use stdClass;

/**
 * Condition met by the users whose usernames a query defined by the administrator returns.
 *
 * Configuration: ['query' => slot key]. A missing or failing query makes the condition unknown, which blocks new
 * enrolments but never suspends existing ones.
 *
 * @package    enrolcond_sqlquery
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \local_enrolconditions\condition\base {
    #[\Override]
    public function is_met(int $userid, stdClass $instance, array $config): bool {
        $usernames = queries::get_usernames((string) ($config['query'] ?? ''), (int) $instance->courseid);
        $user = \core_user::get_user($userid, 'id, username');
        return $user && isset($usernames[\core_text::strtolower($user->username)]);
    }

    #[\Override]
    public function get_description(array $config, bool $met): string {
        $key = (string) ($config['query'] ?? '');
        $queries = queries::get_queries();
        if (!isset($queries[$key])) {
            return get_string('descmissing', queries::COMPONENT);
        }
        $name = format_string($queries[$key]['name'], true, ['context' => \context_system::instance()]);
        return get_string('desc', queries::COMPONENT, $name);
    }

    #[\Override]
    public function config_form_definition(MoodleQuickForm $mform): void {
        $options = [];
        foreach (queries::get_queries() as $key => $query) {
            $options[$key] = format_string($query['name'], true, ['context' => \context_system::instance()]);
        }
        if (!$options) {
            $mform->addElement('static', 'noqueries', '', get_string('noqueries', queries::COMPONENT));
        }
        $mform->addElement('select', 'query', get_string('query', queries::COMPONENT), $options);
        $mform->addHelpButton('query', 'query', queries::COMPONENT);
    }

    #[\Override]
    public function config_from_form_data(stdClass $data): array {
        return ['query' => (string) ($data->query ?? '')];
    }

    #[\Override]
    public function validate_config(array $config): array {
        if (!isset(queries::get_queries()[(string) ($config['query'] ?? '')])) {
            return ['query' => get_string('invalidquery', queries::COMPONENT)];
        }
        return [];
    }
}
