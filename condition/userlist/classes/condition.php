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

use local_enrolconditions\local\usernames;
use MoodleQuickForm;
use stdClass;

/**
 * Condition met by the users whose usernames are typed in a list.
 *
 * Configuration: ['usernames' => list of usernames in lower case]. For long lists kept outside Moodle, use the
 * file condition (enrolcond_userfile).
 *
 * @package    enrolcond_userlist
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \local_enrolconditions\condition\base {
    /** @var int Maximum number of usernames in a list. */
    public const MAX_USERNAMES = 5000;

    #[\Override]
    public function is_met(int $userid, stdClass $instance, array $config): bool {
        $user = \core_user::get_user($userid, 'id, username');
        return $user && in_array(\core_text::strtolower($user->username), $config['usernames'] ?? [], true);
    }

    #[\Override]
    public function get_description(array $config, bool $met): string {
        return get_string('desc', 'enrolcond_userlist', count($config['usernames'] ?? []));
    }

    #[\Override]
    public function config_form_definition(MoodleQuickForm $mform): void {
        $mform->addElement('textarea', 'usernames', get_string('usernames', 'enrolcond_userlist'), ['rows' => 10, 'cols' => 50]);
        $mform->setType('usernames', PARAM_RAW);
        $mform->addHelpButton('usernames', 'usernames', 'enrolcond_userlist');
    }

    #[\Override]
    public function config_to_form_data(array $config): array {
        return ['usernames' => implode("\n", $config['usernames'] ?? [])];
    }

    #[\Override]
    public function config_from_form_data(stdClass $data): array {
        return ['usernames' => usernames::parse_list((string) ($data->usernames ?? ''))];
    }

    #[\Override]
    public function validate_config(array $config): array {
        $list = $config['usernames'] ?? null;
        if (!is_array($list) || !$list) {
            return ['usernames' => get_string('required')];
        }
        if (count($list) > self::MAX_USERNAMES) {
            return ['usernames' => get_string('toomany', 'enrolcond_userlist', self::MAX_USERNAMES)];
        }
        $invalid = usernames::invalid(array_map('strval', $list));
        if ($invalid) {
            $sample = s(implode(', ', array_slice($invalid, 0, 10)));
            return ['usernames' => get_string('invalidusernames', 'enrolcond_userlist', $sample)];
        }
        return [];
    }
}
