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

namespace enrolcond_cohort;

use MoodleQuickForm;
use stdClass;

/**
 * Condition met by the members of a cohort.
 *
 * Configuration: ['cohortid' => id]. Combine several conditions in a group to require any or all of many cohorts.
 *
 * @package    enrolcond_cohort
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \local_enrolconditions\condition\base {
    #[\Override]
    public function is_met(int $userid, stdClass $instance, array $config): bool {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
        $cohortid = (int) ($config['cohortid'] ?? 0);
        return $cohortid > 0 && cohort_is_member($cohortid, $userid);
    }

    /**
     * Name of a cohort, for display.
     *
     * @param int $cohortid
     * @return string|null Null when the cohort does not exist.
     */
    protected function get_cohort_name(int $cohortid): ?string {
        global $DB;
        $cohort = $DB->get_record('cohort', ['id' => $cohortid], 'id, name, contextid');
        if (!$cohort) {
            return null;
        }
        return format_string($cohort->name, true, ['context' => \context::instance_by_id($cohort->contextid)]);
    }

    #[\Override]
    public function get_description(array $config, bool $met): string {
        $cohortid = (int) ($config['cohortid'] ?? 0);
        $name = $this->get_cohort_name($cohortid);
        if ($name === null) {
            return get_string('descmissing', 'enrolcond_cohort', $cohortid);
        }
        return get_string('desc', 'enrolcond_cohort', $name);
    }

    /**
     * Cohorts that the current user can choose, in the context of the page.
     *
     * @return string[] Names by id.
     */
    public function get_cohort_options(): array {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/cohort/lib.php');
        // The condition form is a dynamic form, which sets the page context to the course.
        $options = [];
        foreach (cohort_get_available_cohorts($PAGE->context, COHORT_ALL, 0, 0) as $cohort) {
            $options[$cohort->id] = format_string(
                $cohort->name,
                true,
                ['context' => \context::instance_by_id($cohort->contextid)]
            );
        }
        return $options;
    }

    #[\Override]
    public function config_form_definition(MoodleQuickForm $mform): void {
        $options = $this->get_cohort_options();
        if (!$options) {
            $mform->addElement('static', 'nocohorts', '', get_string('nocohorts', 'enrolcond_cohort'));
        }
        $mform->addElement('autocomplete', 'cohortid', get_string('cohort', 'cohort'), $options);
        $mform->setType('cohortid', PARAM_INT);
    }

    #[\Override]
    public function config_from_form_data(stdClass $data): array {
        return ['cohortid' => (int) ($data->cohortid ?? 0)];
    }

    #[\Override]
    public function validate_config(array $config): array {
        global $DB;
        $cohortid = (int) ($config['cohortid'] ?? 0);
        if ($cohortid <= 0 || !$DB->record_exists('cohort', ['id' => $cohortid])) {
            return ['cohortid' => get_string('invalidcohort', 'enrolcond_cohort')];
        }
        return [];
    }

    #[\Override]
    public function config_form_validation(array $data, array $files): array {
        $errors = parent::config_form_validation($data, $files);
        $cohortid = (int) ($data['cohortid'] ?? 0);
        if (!$errors && !array_key_exists($cohortid, $this->get_cohort_options())) {
            // The user can only choose cohorts visible to them in the course.
            $errors['cohortid'] = get_string('invalidcohort', 'enrolcond_cohort');
        }
        return $errors;
    }

    #[\Override]
    public function restore_config(array $config, bool $samesite): array {
        // Cohorts are not in course backups: from another site the condition fails until it is edited.
        return $samesite ? $config : ['cohortid' => 0];
    }

    #[\Override]
    public function get_observed_events(): array {
        return [
            \core\event\cohort_member_added::class,
            \core\event\cohort_member_removed::class,
        ];
    }
}
