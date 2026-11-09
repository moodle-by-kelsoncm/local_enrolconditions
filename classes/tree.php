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

namespace local_enrolconditions;

use local_enrolconditions\condition\manager;
use stdClass;

/**
 * Tree of conditions combined with AND, OR and NOT, stored as JSON.
 *
 * Format: the root is always a group.
 * - Group: {"op": "and"|"or", "negate": bool, "children": [node, ...]}
 * - Condition: {"type": "<condition type>", "negate": bool, "config": {...}}
 *
 * An empty root group (or no tree at all) has no conditions and is always met.
 *
 * The evaluation uses three values: true, false and null (unknown). A condition whose type is no
 * longer installed is unknown. AND is false if any child is false, otherwise unknown if any child
 * is unknown; OR is true if any child is true, otherwise unknown if any child is unknown; NOT keeps
 * unknown as unknown. Callers decide what unknown means: enrol_conditional refuses new enrolments
 * and takes no action on existing ones.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tree {
    /** @var string Operator of a group whose children must all be met. */
    public const OP_AND = 'and';

    /** @var string Operator of a group with at least one child met. */
    public const OP_OR = 'or';

    /** @var int Maximum depth of nested groups, the root included. */
    public const MAX_DEPTH = 5;

    /** @var int Maximum number of nodes (groups and conditions) in a tree. */
    public const MAX_NODES = 100;

    /** @var int Flags of json_encode(): no characters that HTML or PARAM_NOTAGS would touch. */
    public const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;

    /**
     * Constructor.
     *
     * @param array $root Root group, already normalised.
     */
    protected function __construct(
        /** @var array Root group. */
        protected array $root,
    ) {
    }

    /**
     * Tree without conditions.
     *
     * @return self
     */
    public static function empty(): self {
        return new self(['op' => self::OP_AND, 'negate' => false, 'children' => []]);
    }

    /**
     * Builds a tree from its JSON, checking only the structure (not the condition types or configs).
     *
     * @param string|null $json Stored JSON; null or an empty string give an empty tree.
     * @return self
     * @throws invalid_tree_exception When the JSON or its structure is invalid.
     */
    public static function from_json(?string $json): self {
        if ($json === null || trim($json) === '') {
            return self::empty();
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new invalid_tree_exception(get_string('treeinvalidjson', 'local_enrolconditions'));
        }
        return self::from_array($data);
    }

    /**
     * Builds a tree from its decoded array, checking only the structure.
     *
     * @param array $data
     * @return self
     * @throws invalid_tree_exception When the structure is invalid.
     */
    public static function from_array(array $data): self {
        $errors = [];
        $count = 0;
        $root = self::normalise_node($data, '', 1, $count, $errors);
        if ($root !== null && !isset($root['op'])) {
            $errors[] = get_string('treerootnotgroup', 'local_enrolconditions');
        }
        if ($errors) {
            throw new invalid_tree_exception(implode("\n", $errors));
        }
        return new self($root);
    }

    /**
     * JSON to store; null when the tree has no conditions.
     *
     * @return string|null
     */
    public function to_json(): ?string {
        if ($this->is_empty()) {
            return null;
        }
        return json_encode($this->root, self::JSON_FLAGS);
    }

    /**
     * Root group as an array.
     *
     * @return array
     */
    public function to_array(): array {
        return $this->root;
    }

    /**
     * Whether the tree has no conditions.
     *
     * @return bool
     */
    public function is_empty(): bool {
        return empty($this->root['children']);
    }

    /**
     * Condition types used in the tree.
     *
     * @return string[]
     */
    public function get_types(): array {
        $types = [];
        self::walk($this->root, function (array $node) use (&$types): void {
            if (isset($node['type'])) {
                $types[$node['type']] = true;
            }
        });
        return array_keys($types);
    }

    /**
     * Fully qualified names of the events that may change the result of the tree.
     *
     * @return string[]
     */
    public function get_observed_events(): array {
        $events = [];
        foreach ($this->get_types() as $type) {
            $condition = manager::get_condition($type);
            if ($condition) {
                foreach ($condition->get_observed_events() as $event) {
                    $events[ltrim($event, '\\')] = true;
                }
            }
        }
        return array_keys($events);
    }

    /**
     * Validates the condition types and configurations.
     *
     * New conditions must be of an enabled type. Types in $allowedtypes (usually the types of the
     * tree saved before) are accepted even when disabled or not installed, so an existing tree can
     * still be saved; their configuration is only validated when the type is installed.
     *
     * @param string[] $allowedtypes Types accepted even when not enabled.
     * @return string[] Error messages; empty when valid.
     */
    public function validate(array $allowedtypes = []): array {
        $errors = [];
        self::walk($this->root, function (array $node, string $path) use (&$errors, $allowedtypes): void {
            if (!isset($node['type'])) {
                return;
            }
            $type = $node['type'];
            $condition = manager::get_condition($type);
            $allowed = in_array($type, $allowedtypes, true);
            if (!$allowed && (!$condition || !manager::is_enabled($type))) {
                $errors[] = get_string('treeunknowntype', 'local_enrolconditions', $type);
                return;
            }
            if ($condition) {
                foreach ($condition->validate_config($node['config']) as $message) {
                    $errors[] = get_string(
                        'treeconditionerror',
                        'local_enrolconditions',
                        ['name' => $condition->get_name(), 'error' => $message]
                    );
                }
            }
        });
        return $errors;
    }

    /**
     * Evaluates the tree for a user.
     *
     * @param int $userid
     * @param stdClass $instance Enrolment instance passed to the conditions.
     * @return tree_result
     */
    public function evaluate(int $userid, stdClass $instance): tree_result {
        $failures = [];
        $met = $this->evaluate_node($this->root, $userid, $instance, $failures);
        return new tree_result($met, $failures);
    }

    /**
     * Evaluates one node.
     *
     * @param array $node
     * @param int $userid
     * @param stdClass $instance
     * @param array $failures Receives the explanation of the node when it is not met.
     * @return bool|null True, false or null (unknown).
     */
    protected function evaluate_node(array $node, int $userid, stdClass $instance, array &$failures): ?bool {
        if (isset($node['type'])) {
            return $this->evaluate_condition($node, $userid, $instance, $failures);
        }

        $childfailures = [];
        $results = [];
        foreach ($node['children'] as $child) {
            $results[] = $this->evaluate_node($child, $userid, $instance, $childfailures);
        }
        if (!$results) {
            $value = true;
        } else if ($node['op'] === self::OP_AND) {
            $value = in_array(false, $results, true) ? false : (in_array(null, $results, true) ? null : true);
        } else {
            $value = in_array(true, $results, true) ? true : (in_array(null, $results, true) ? null : false);
        }
        if ($node['negate'] && $value !== null) {
            $value = !$value;
        }

        if ($value !== true) {
            if ($node['negate']) {
                $failures[] = ['message' => get_string('failurenotgroup', 'local_enrolconditions'), 'children' => []];
            } else if ($node['op'] === self::OP_OR && count($childfailures) > 1) {
                $failures[] = ['message' => get_string('failureoneof', 'local_enrolconditions'), 'children' => $childfailures];
            } else {
                array_push($failures, ...$childfailures);
            }
        }
        return $value;
    }

    /**
     * Evaluates a condition node.
     *
     * @param array $node
     * @param int $userid
     * @param stdClass $instance
     * @param array $failures Receives the explanation when the condition is not met.
     * @return bool|null Null when the type is not installed.
     */
    protected function evaluate_condition(array $node, int $userid, stdClass $instance, array &$failures): ?bool {
        $condition = manager::get_condition($node['type']);
        if (!$condition) {
            $failures[] = ['message' => get_string('failureunknown', 'local_enrolconditions'), 'children' => []];
            return null;
        }
        $met = $condition->is_met($userid, $instance, $node['config']);
        $value = $node['negate'] ? !$met : $met;
        if (!$value) {
            $message = $node['negate']
                ? get_string('failurenot', 'local_enrolconditions', $condition->get_description($node['config'], true))
                : $condition->get_description($node['config'], false);
            $failures[] = ['message' => $message, 'children' => []];
        }
        return $value;
    }

    /**
     * Checks and normalises a node.
     *
     * @param mixed $node Decoded node.
     * @param string $path Position of the node, for error messages.
     * @param int $depth Depth of the node, the root being 1.
     * @param int $count Nodes seen so far.
     * @param string[] $errors Receives the errors.
     * @return array|null Normalised node, or null when invalid.
     */
    protected static function normalise_node($node, string $path, int $depth, int &$count, array &$errors): ?array {
        $where = $path === '' ? get_string('treeroot', 'local_enrolconditions') : $path;
        if (!is_array($node) || array_is_list($node) && $node !== []) {
            $errors[] = get_string('treebadnode', 'local_enrolconditions', $where);
            return null;
        }
        if (++$count > self::MAX_NODES) {
            $errors[] = get_string('treetoomanynodes', 'local_enrolconditions', self::MAX_NODES);
            return null;
        }
        $negate = $node['negate'] ?? false;
        if (!is_bool($negate)) {
            $errors[] = get_string('treebadnode', 'local_enrolconditions', $where);
            return null;
        }

        if (array_key_exists('type', $node)) {
            $type = $node['type'];
            $config = $node['config'] ?? [];
            if (
                !is_string($type) || !preg_match('/^[a-z][a-z0-9_]*$/', $type) || !is_array($config)
                    || array_diff(array_keys($node), ['type', 'negate', 'config'])
            ) {
                $errors[] = get_string('treebadnode', 'local_enrolconditions', $where);
                return null;
            }
            return ['type' => $type, 'negate' => $negate, 'config' => $config];
        }

        $op = $node['op'] ?? null;
        $children = $node['children'] ?? [];
        if (
            !in_array($op, [self::OP_AND, self::OP_OR], true) || !is_array($children) || !array_is_list($children)
                || array_diff(array_keys($node), ['op', 'negate', 'children'])
        ) {
            $errors[] = get_string('treebadnode', 'local_enrolconditions', $where);
            return null;
        }
        if ($depth > self::MAX_DEPTH) {
            $errors[] = get_string('treetoodeep', 'local_enrolconditions', self::MAX_DEPTH);
            return null;
        }
        $normalised = [];
        foreach ($children as $i => $child) {
            $childnode = self::normalise_node($child, ltrim($path . '.' . ($i + 1), '.'), $depth + 1, $count, $errors);
            if ($childnode !== null) {
                $normalised[] = $childnode;
            }
        }
        return ['op' => $op, 'negate' => $negate, 'children' => $normalised];
    }

    /**
     * Copy of the tree with the configurations adapted for a restored course.
     *
     * Each condition decides through base::restore_config(); unknown types are kept as they are.
     *
     * @param bool $samesite Whether the backup comes from this site.
     * @return self
     */
    public function for_restore(bool $samesite): self {
        return new self(self::map_conditions($this->root, function (array $node) use ($samesite): array {
            $condition = manager::get_condition($node['type']);
            if ($condition) {
                $node['config'] = $condition->restore_config($node['config'] ?? [], $samesite);
            }
            return $node;
        }));
    }

    /**
     * Applies a function to every condition node, keeping the groups.
     *
     * @param array $node
     * @param callable $callback Receives a condition node and returns it changed.
     * @return array
     */
    protected static function map_conditions(array $node, callable $callback): array {
        if (isset($node['type'])) {
            return $callback($node);
        }
        $node['children'] = array_map(
            fn(array $child): array => self::map_conditions($child, $callback),
            $node['children'] ?? []
        );
        return $node;
    }

    /**
     * Calls a function for every node, depth first.
     *
     * @param array $node
     * @param callable $callback Receives the node and its path.
     * @param string $path
     */
    protected static function walk(array $node, callable $callback, string $path = ''): void {
        $callback($node, $path);
        foreach ($node['children'] ?? [] as $i => $child) {
            self::walk($child, $callback, ltrim($path . '.' . ($i + 1), '.'));
        }
    }
}
