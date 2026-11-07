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

namespace local_enrolconditions\form;

use context;
use core\url;
use local_enrolconditions\condition\base;
use local_enrolconditions\condition\manager;
use local_enrolconditions\tree;
use moodle_exception;

/**
 * Modal form that edits the configuration of one condition of a tree.
 *
 * It does not save anything: it returns the configuration (JSON) and its summary to the tree editor,
 * which keeps them in the field of the enrolment method form.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition_config extends \core_form\dynamic_form {
    /** @var string Name of the hidden field with the context id. */
    protected const FIELD_CONTEXT = 'enrolcondcontextid';

    /** @var string Name of the hidden field with the condition type. */
    protected const FIELD_TYPE = 'enrolcondtype';

    /** @var string Name of the hidden field with the current configuration (JSON). */
    protected const FIELD_CONFIG = 'enrolcondconfig';

    /**
     * Condition being edited.
     *
     * @return base
     * @throws moodle_exception When the type is not available.
     */
    protected function get_condition(): base {
        $type = $this->optional_param(self::FIELD_TYPE, '', PARAM_ALPHANUMEXT);
        $condition = $type === '' ? null : manager::get_condition($type);
        if (!$condition) {
            throw new moodle_exception('treeunknowntype', 'local_enrolconditions', '', $type);
        }
        return $condition;
    }

    /**
     * Configuration sent by the editor.
     *
     * @return array
     */
    protected function get_current_config(): array {
        $json = $this->optional_param(self::FIELD_CONFIG, '', PARAM_NOTAGS);
        $config = $json === '' ? [] : json_decode($json, true);
        return is_array($config) ? $config : [];
    }

    #[\Override]
    protected function get_context_for_dynamic_submission(): context {
        $contextid = $this->optional_param(self::FIELD_CONTEXT, 0, PARAM_INT);
        return context::instance_by_id($contextid);
    }

    #[\Override]
    protected function check_access_for_dynamic_submission(): void {
        $context = $this->get_context_for_dynamic_submission();
        if ($context->contextlevel !== CONTEXT_COURSE) {
            throw new moodle_exception('invalidcontext');
        }
        require_capability('moodle/course:enrolconfig', $context);
    }

    #[\Override]
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', self::FIELD_CONTEXT);
        $mform->setType(self::FIELD_CONTEXT, PARAM_INT);
        $mform->addElement('hidden', self::FIELD_TYPE);
        $mform->setType(self::FIELD_TYPE, PARAM_ALPHANUMEXT);
        $mform->addElement('hidden', self::FIELD_CONFIG);
        $mform->setType(self::FIELD_CONFIG, PARAM_NOTAGS);

        $this->get_condition()->config_form_definition($mform);
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        return array_merge($errors, $this->get_condition()->config_form_validation($data, $files));
    }

    #[\Override]
    public function set_data_for_dynamic_submission(): void {
        $condition = $this->get_condition();
        $config = $this->get_current_config();
        $data = $condition->config_to_form_data($config);
        $data[self::FIELD_CONTEXT] = $this->get_context_for_dynamic_submission()->id;
        $data[self::FIELD_TYPE] = $condition->get_type();
        $data[self::FIELD_CONFIG] = json_encode($config, tree::JSON_FLAGS);
        $this->set_data($data);
    }

    #[\Override]
    public function process_dynamic_submission(): array {
        $condition = $this->get_condition();
        $config = $condition->config_from_form_data($this->get_data());
        return [
            'config' => json_encode($config, tree::JSON_FLAGS),
            'summary' => $condition->get_summary($config),
        ];
    }

    #[\Override]
    protected function get_page_url_for_dynamic_submission(): url {
        $context = $this->get_context_for_dynamic_submission();
        return new url('/enrol/instances.php', ['id' => $context->instanceid]);
    }
}
