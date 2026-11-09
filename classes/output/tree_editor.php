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

namespace local_enrolconditions\output;

use context;
use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use local_enrolconditions\condition\manager;
use local_enrolconditions\tree;
use MoodleQuickForm;

/**
 * Editor of a condition tree, kept in a hidden field of a form.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tree_editor implements renderable, templatable {
    /** @var string Id of the editor container. */
    protected string $uniqid;

    /**
     * Constructor.
     *
     * @param tree $tree Tree being edited.
     * @param string $fieldname Name of the hidden form field that receives the JSON.
     * @param context $context Course context, checked by the condition forms.
     */
    public function __construct(
        /** @var tree Tree being edited. */
        protected tree $tree,
        /** @var string Name of the hidden form field. */
        protected string $fieldname,
        /** @var context Course context. */
        protected context $context,
    ) {
        $this->uniqid = \html_writer::random_id('local_enrolconditions_editor');
    }

    /**
     * Id of the editor container.
     *
     * @return string
     */
    public function get_uniqid(): string {
        return $this->uniqid;
    }

    /**
     * Adds the hidden field and the editor to a form.
     *
     * The value of the field is the JSON of the tree (escaped by tree::JSON_FLAGS, so PARAM_NOTAGS keeps it).
     * Without JavaScript the editor shows a message and the field keeps its value.
     *
     * When the form comes back with validation errors, the editor shows the tree that was submitted, so
     * the changes are not lost; if that tree is not valid, it shows the saved one.
     *
     * @param MoodleQuickForm $mform
     * @param string $fieldname Name of the hidden field.
     * @param string $label Label shown next to the editor.
     * @param context $context Course context.
     * @param string|null $json Saved value.
     */
    public static function add_to_form(
        MoodleQuickForm $mform,
        string $fieldname,
        string $label,
        context $context,
        ?string $json,
    ): void {
        global $OUTPUT, $PAGE;

        $candidates = [$json];
        $submitted = optional_param($fieldname, null, PARAM_NOTAGS);
        if ($submitted !== null && data_submitted()) {
            array_unshift($candidates, $submitted);
        }
        $tree = tree::empty();
        foreach ($candidates as $candidate) {
            try {
                $tree = tree::from_json($candidate);
                break;
            } catch (\local_enrolconditions\invalid_tree_exception $e) {
                continue;
            }
        }
        $editor = new self($tree, $fieldname, $context);
        $mform->addElement('hidden', $fieldname);
        $mform->setType($fieldname, PARAM_NOTAGS);
        $mform->addElement('static', $fieldname . '_editor', $label, $OUTPUT->render($editor));
        $PAGE->requires->js_call_amd('local_enrolconditions/tree_editor', 'init', ['#' . $editor->get_uniqid()]);
    }

    /**
     * Data for the template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $types = [];
        foreach (manager::get_enabled_types() as $type) {
            $condition = manager::get_condition($type);
            if ($condition) {
                $types[] = ['type' => $type, 'name' => $condition->get_name()];
            }
        }
        usort($types, fn(array $a, array $b): int => strcmp($a['name'], $b['name']));

        $config = [
            'fieldname' => $this->fieldname,
            'contextid' => $this->context->id,
            'maxdepth' => tree::MAX_DEPTH,
            'types' => $types,
            'tree' => $this->with_summaries($this->tree->to_array()),
        ];
        return [
            'uniqid' => $this->uniqid,
            'config' => json_encode($config, tree::JSON_FLAGS),
        ];
    }

    /**
     * Adds the name and the summary of each condition, for display only.
     *
     * @param array $node
     * @return array
     */
    protected function with_summaries(array $node): array {
        if (isset($node['type'])) {
            $condition = manager::get_condition($node['type']);
            $node['name'] = $condition ? $condition->get_name() : $node['type'];
            $node['summary'] = $condition ? $condition->get_summary($node['config'])
                : get_string('editorunknowntype', 'local_enrolconditions', $node['type']);
            $node['known'] = (bool) $condition;
            return $node;
        }
        $node['children'] = array_map([$this, 'with_summaries'], $node['children']);
        return $node;
    }
}
