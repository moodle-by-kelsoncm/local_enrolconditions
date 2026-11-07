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
 * Enables and disables conditions (enrolcond subplugins).
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_enrolconditions\plugininfo\enrolcond;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_admin();
require_sesskey();

$action = required_param('action', PARAM_ALPHA);
$plugin = required_param('plugin', PARAM_PLUGIN);

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/enrolconditions/conditions.php');

$installed = core_plugin_manager::instance()->get_installed_plugins('enrolcond');
if (!isset($installed[$plugin])) {
    throw new moodle_exception('conditionnotinstalled', 'local_enrolconditions', enrolcond::get_manage_url(), $plugin);
}
$pluginname = get_string('pluginname', 'enrolcond_' . $plugin);

if ($action === 'enable' || $action === 'disable') {
    $enabled = $action === 'enable';
    if (enrolcond::enable_plugin($plugin, (int) $enabled)) {
        $message = get_string($enabled ? 'conditionenabled' : 'conditiondisabled', 'local_enrolconditions', $pluginname);
        \core\notification::success($message);
    }
}

redirect(enrolcond::get_manage_url());
