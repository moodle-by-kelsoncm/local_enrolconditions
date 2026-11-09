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

namespace enrolcond_date;

use MoodleQuickForm;
use stdClass;

/**
 * Condition met within a period: from a date, until a date, or both.
 *
 * Configuration: ['from' => timestamp or 0, 'to' => timestamp or 0]. The period includes "from" and excludes "to".
 * Time is not an event, so re-checks of a period that ends depend on the login or periodic check modes.
 *
 * @package    enrolcond_date
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \local_enrolconditions\condition\base {
    /** @var int|null Fixed current time, for tests. */
    protected ?int $now = null;

    /**
     * Fixes the current time, for tests.
     *
     * @param int|null $now
     */
    public function set_now(?int $now): void {
        $this->now = $now;
    }

    /**
     * Normalised configuration.
     *
     * @param array $config
     * @return array{from: int, to: int}
     */
    protected function normalise(array $config): array {
        return ['from' => max(0, (int) ($config['from'] ?? 0)), 'to' => max(0, (int) ($config['to'] ?? 0))];
    }

    #[\Override]
    public function is_met(int $userid, stdClass $instance, array $config): bool {
        ['from' => $from, 'to' => $to] = $this->normalise($config);
        $now = $this->now ?? time();
        return (!$from || $now >= $from) && (!$to || $now < $to);
    }

    #[\Override]
    public function get_description(array $config, bool $met): string {
        ['from' => $from, 'to' => $to] = $this->normalise($config);
        $format = get_string('strftimedatetime', 'langconfig');
        if ($from && $to) {
            return get_string('descfromto', 'enrolcond_date', (object) [
                'from' => userdate($from, $format),
                'to' => userdate($to, $format),
            ]);
        }
        if ($to) {
            return get_string('descto', 'enrolcond_date', userdate($to, $format));
        }
        return get_string('descfrom', 'enrolcond_date', userdate($from, $format));
    }

    #[\Override]
    public function config_form_definition(MoodleQuickForm $mform): void {
        $mform->addElement('date_time_selector', 'from', get_string('from', 'enrolcond_date'), ['optional' => true]);
        $mform->addHelpButton('from', 'from', 'enrolcond_date');
        $mform->addElement('date_time_selector', 'to', get_string('to', 'enrolcond_date'), ['optional' => true]);
        $mform->addHelpButton('to', 'to', 'enrolcond_date');
    }

    #[\Override]
    public function config_from_form_data(stdClass $data): array {
        return $this->normalise(['from' => $data->from ?? 0, 'to' => $data->to ?? 0]);
    }

    #[\Override]
    public function validate_config(array $config): array {
        ['from' => $from, 'to' => $to] = $this->normalise($config);
        if (!$from && !$to) {
            return ['from' => get_string('required', 'enrolcond_date')];
        }
        if ($from && $to && $to <= $from) {
            return ['to' => get_string('toafterfrom', 'enrolcond_date')];
        }
        return [];
    }
}
