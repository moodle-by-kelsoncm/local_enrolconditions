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

/**
 * Strings for local_enrolconditions.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['conditiondisabled'] = 'Condition "{$a}" disabled.';
$string['conditionenabled'] = 'Condition "{$a}" enabled.';
$string['conditionnotinstalled'] = 'The condition "{$a}" is not installed.';
$string['editoraddcondition'] = 'Add condition';
$string['editoraddgroup'] = 'Add group';
$string['editorall'] = 'All of these';
$string['editorany'] = 'Any of these';
$string['editorchoosetype'] = 'Condition type';
$string['editoredit'] = 'Edit';
$string['editorempty'] = 'No conditions.';
$string['editornoscript'] = 'The condition editor needs JavaScript. The saved conditions are kept unchanged.';
$string['editornot'] = 'Must not be met';
$string['editoroperator'] = 'How to combine the items of this group';
$string['editorremove'] = 'Remove';
$string['editorremovegroup'] = 'Remove group';
$string['editorunknowntype'] = 'The condition type "{$a}" is not installed. Self enrolment is refused while it is in use.';
$string['failurenot'] = 'You must not meet this condition: {$a}';
$string['failurenotgroup'] = 'You meet a group of conditions that must not be met.';
$string['failureoneof'] = 'You must meet at least one of the following:';
$string['failureunknown'] = 'A condition can no longer be checked. Contact the course teacher.';
$string['manageconditions'] = 'Manage conditions';
$string['pluginname'] = 'Enrolment conditions';
$string['privacy:metadata'] = 'The Enrolment conditions plugin does not store any personal data.';
$string['subplugintype_enrolcond'] = 'Condition';
$string['subplugintype_enrolcond_plural'] = 'Conditions';
$string['treebadnode'] = 'Invalid node at position {$a}.';
$string['treeconditionerror'] = '{$a->name}: {$a->error}';
$string['treeinvalid'] = 'Invalid condition tree: {$a}';
$string['treeinvalidjson'] = 'The conditions are not valid JSON.';
$string['treeroot'] = 'root';
$string['treerootnotgroup'] = 'The root of the conditions must be a group.';
$string['treetoodeep'] = 'Groups can be nested at most {$a} levels deep.';
$string['treetoomanynodes'] = 'At most {$a} conditions and groups are allowed.';
$string['treeunknowntype'] = 'The condition type "{$a}" is not available.';
