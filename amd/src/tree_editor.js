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

/**
 * Editor of a condition tree (groups with AND/OR/NOT and conditions), kept in a hidden form field.
 *
 * @module     local_enrolconditions/tree_editor
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {getStrings} from 'core/str';

const FORM_CLASS = 'local_enrolconditions\\form\\condition_config';

const STRING_KEYS = [
    'editorall', 'editorany', 'editornot', 'editoraddcondition', 'editoraddgroup', 'editoredit',
    'editorremove', 'editorremovegroup', 'editorempty', 'editorchoosetype', 'editoroperator',
];

/**
 * Keys of a condition or group that are stored; the others are only for display.
 *
 * @param {Object} node Node of the tree.
 * @returns {Object} Node without display data.
 */
const toStored = (node) => {
    if (node.type !== undefined) {
        return {type: node.type, negate: !!node.negate, config: node.config || {}};
    }
    return {op: node.op, negate: !!node.negate, children: node.children.map(toStored)};
};

/**
 * JSON with the characters escaped that PARAM_NOTAGS would remove (they only appear inside strings).
 *
 * @param {*} value Value to encode.
 * @returns {string}
 */
const toJson = (value) => JSON.stringify(value)
    .replace(/</g, '\\u003C')
    .replace(/>/g, '\\u003E')
    .replace(/&/g, '\\u0026')
    .replace(/'/g, '\\u0027');

/**
 * JSON of the tree to store.
 *
 * @param {Object} root Root group.
 * @returns {string} JSON, or an empty string when there are no conditions.
 */
const serialise = (root) => (root.children.length ? toJson(toStored(root)) : '');

/**
 * Creates an element.
 *
 * @param {string} tag Tag name.
 * @param {string} className CSS classes.
 * @param {string} text Text content.
 * @returns {HTMLElement}
 */
const element = (tag, className = '', text = '') => {
    const el = document.createElement(tag);
    if (className) {
        el.className = className;
    }
    if (text) {
        el.textContent = text;
    }
    return el;
};

/**
 * Creates a button.
 *
 * @param {string} label Visible text.
 * @param {string} className CSS classes.
 * @param {Function} handler Click handler.
 * @returns {HTMLButtonElement}
 */
const button = (label, className, handler) => {
    const el = element('button', 'btn btn-sm ' + className, label);
    el.type = 'button';
    el.addEventListener('click', (e) => {
        e.preventDefault();
        handler();
    });
    return el;
};

/**
 * Creates a "must not" checkbox.
 *
 * @param {Object} node Node whose negate flag it controls.
 * @param {string} label Label.
 * @param {Function} onChange Called after the change.
 * @returns {HTMLElement}
 */
const negateCheckbox = (node, label, onChange) => {
    const wrapper = element('div', 'form-check form-check-inline mb-0');
    const input = element('input', 'form-check-input');
    input.type = 'checkbox';
    input.id = 'local_enrolconditions_not_' + Math.random().toString(36).slice(2);
    input.checked = !!node.negate;
    input.addEventListener('change', () => {
        node.negate = input.checked;
        onChange();
    });
    const lab = element('label', 'form-check-label', label);
    lab.htmlFor = input.id;
    wrapper.append(input, lab);
    return wrapper;
};

/**
 * Tree editor bound to one container.
 */
class TreeEditor {
    /**
     * Constructor.
     *
     * @param {HTMLElement} container Editor container.
     * @param {Object} config Data from the template.
     * @param {Object} strings Language strings keyed by identifier.
     */
    constructor(container, config, strings) {
        this.container = container;
        this.config = config;
        this.strings = strings;
        this.root = config.tree;
        this.field = container.closest('form').querySelector(`input[name="${config.fieldname}"]`);
    }

    /**
     * Writes the tree into the hidden field and draws it again.
     */
    update() {
        this.field.value = serialise(this.root);
        this.render();
    }

    /**
     * Draws the whole tree.
     */
    render() {
        this.container.replaceChildren(this.renderGroup(this.root, null, 1));
    }

    /**
     * Draws a group.
     *
     * @param {Object} group Group node.
     * @param {Object|null} parent Parent group, null for the root.
     * @param {number} depth Depth, the root being 1.
     * @returns {HTMLElement}
     */
    renderGroup(group, parent, depth) {
        const s = this.strings;
        const box = element('div', 'local_enrolconditions-group border rounded p-2 mb-2');
        box.dataset.depth = depth;

        const header = element('div', 'd-flex flex-wrap align-items-center gap-2 mb-2');
        const select = element('select', 'form-select form-select-sm w-auto');
        select.setAttribute('aria-label', s.editoroperator);
        [['and', s.editorall], ['or', s.editorany]].forEach(([value, label]) => {
            const option = element('option', '', label);
            option.value = value;
            option.selected = group.op === value;
            select.append(option);
        });
        select.addEventListener('change', () => {
            group.op = select.value;
            this.update();
        });
        header.append(select, negateCheckbox(group, s.editornot, () => this.update()));
        if (parent) {
            header.append(button(s.editorremovegroup, 'btn-outline-danger ms-auto', () => {
                parent.children.splice(parent.children.indexOf(group), 1);
                this.update();
            }));
        }
        box.append(header);

        const list = element('div', 'ms-3');
        if (!group.children.length) {
            list.append(element('p', 'text-muted mb-2', s.editorempty));
        }
        group.children.forEach((child) => {
            list.append(child.type !== undefined
                ? this.renderCondition(child, group)
                : this.renderGroup(child, group, depth + 1));
        });
        box.append(list);
        box.append(this.renderAddBar(group, depth));
        return box;
    }

    /**
     * Draws a condition.
     *
     * @param {Object} node Condition node.
     * @param {Object} parent Parent group.
     * @returns {HTMLElement}
     */
    renderCondition(node, parent) {
        const s = this.strings;
        const row = element('div', 'local_enrolconditions-condition d-flex flex-wrap align-items-center gap-2 border-bottom py-1');
        const text = element('div', 'flex-grow-1');
        text.append(element('strong', '', node.name || node.type));
        // The summary is built by the condition on the server and may contain formatted HTML.
        const summary = element('div', 'small text-muted');
        summary.innerHTML = node.summary || '';
        text.append(summary);
        row.append(text, negateCheckbox(node, s.editornot, () => this.update()));
        if (node.known !== false) {
            row.append(button(s.editoredit, 'btn-secondary', () => this.openForm(node.type, node, (config, summaryhtml) => {
                node.config = config;
                node.summary = summaryhtml;
                this.update();
            })));
        }
        row.append(button(s.editorremove, 'btn-outline-danger', () => {
            parent.children.splice(parent.children.indexOf(node), 1);
            this.update();
        }));
        return row;
    }

    /**
     * Draws the controls that add a condition or a group.
     *
     * @param {Object} group Group that receives the new node.
     * @param {number} depth Depth of the group.
     * @returns {HTMLElement}
     */
    renderAddBar(group, depth) {
        const s = this.strings;
        const bar = element('div', 'd-flex flex-wrap align-items-center gap-2 mt-2');
        if (this.config.types.length) {
            const select = element('select', 'form-select form-select-sm w-auto');
            select.setAttribute('aria-label', s.editorchoosetype);
            this.config.types.forEach((type) => {
                const option = element('option', '', type.name);
                option.value = type.type;
                select.append(option);
            });
            bar.append(select, button(s.editoraddcondition, 'btn-primary', () => {
                const type = this.config.types.find((t) => t.type === select.value);
                this.openForm(type.type, null, (config, summaryhtml) => {
                    group.children.push({type: type.type, negate: false, config, name: type.name, summary: summaryhtml});
                    this.update();
                });
            }));
        }
        if (depth < this.config.maxdepth) {
            bar.append(button(s.editoraddgroup, 'btn-outline-secondary', () => {
                group.children.push({op: 'and', negate: false, children: []});
                this.update();
            }));
        }
        return bar;
    }

    /**
     * Opens the configuration form of a condition in a modal.
     *
     * @param {string} type Condition type.
     * @param {Object|null} node Condition being edited, null for a new one.
     * @param {Function} onSaved Receives the configuration and its summary.
     */
    openForm(type, node, onSaved) {
        const typeinfo = this.config.types.find((t) => t.type === type);
        const modal = new ModalForm({
            formClass: FORM_CLASS,
            args: {
                enrolcondcontextid: this.config.contextid,
                enrolcondtype: type,
                enrolcondconfig: node ? toJson(node.config || {}) : '',
            },
            modalConfig: {title: typeinfo ? typeinfo.name : (node && node.name) || type},
        });
        modal.addEventListener(modal.events.FORM_SUBMITTED, (e) => {
            onSaved(JSON.parse(e.detail.config), e.detail.summary);
        });
        modal.show();
    }
}

/**
 * Starts the editor.
 *
 * @param {string} selector Selector of the editor container.
 */
export const init = async(selector) => {
    const container = document.querySelector(selector);
    if (!container) {
        return;
    }
    const config = JSON.parse(container.dataset.config);
    const values = await getStrings(STRING_KEYS.map((key) => ({key, component: 'local_enrolconditions'})));
    const strings = {};
    STRING_KEYS.forEach((key, i) => {
        strings[key] = values[i];
    });
    const editor = new TreeEditor(container, config, strings);
    editor.update();
};
