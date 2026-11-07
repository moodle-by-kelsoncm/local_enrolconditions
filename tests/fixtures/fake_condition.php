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

namespace enrolcond_fake;

use MoodleQuickForm;
use stdClass;

/**
 * Fake condition for unit tests: met when the user id is listed in the configuration.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \local_enrolconditions\condition\base {
    #[\Override]
    public function is_met(int $userid, stdClass $instance, array $config): bool {
        return in_array($userid, $config['userids'] ?? [], true);
    }

    #[\Override]
    public function get_description(array $config, bool $met): string {
        return $met ? 'Listed user' : 'Not a listed user';
    }

    #[\Override]
    public function config_form_definition(MoodleQuickForm $mform): void {
        $mform->addElement('text', 'userids', 'User ids');
        $mform->setType('userids', PARAM_SEQUENCE);
    }

    #[\Override]
    public function config_from_form_data(stdClass $data): array {
        $ids = array_filter(explode(',', (string) ($data->userids ?? '')), 'strlen');
        return ['userids' => array_map('intval', $ids)];
    }

    #[\Override]
    public function validate_config(array $config): array {
        if (empty($config['userids'])) {
            return ['userids' => 'required'];
        }
        return [];
    }

    #[\Override]
    public function get_observed_events(): array {
        return [\core\event\user_updated::class];
    }

    #[\Override]
    public function get_name(): string {
        return 'Fake condition';
    }
}
