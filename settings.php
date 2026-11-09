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
 * Admin pages of local_enrolconditions: condition management and the settings of each condition.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_enrolconditions\plugininfo\enrolcond;
use local_enrolconditions\table\condition_management_table;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('enrolments', new admin_category(
        'local_enrolconditions_folder',
        new lang_string('pluginname', 'local_enrolconditions')
    ));

    $manage = new admin_settingpage(enrolcond::MANAGE_SECTION, new lang_string('manageconditions', 'local_enrolconditions'));
    $manage->add(new \core_admin\admin\admin_setting_plugin_manager(
        'enrolcond',
        condition_management_table::class,
        'local_enrolconditions_manager',
        get_string('manageconditions', 'local_enrolconditions')
    ));
    $ADMIN->add('local_enrolconditions_folder', $manage);

    foreach (core_plugin_manager::instance()->get_plugins_of_type('enrolcond') as $plugin) {
        /** @var enrolcond $plugin */
        $plugin->load_settings($ADMIN, 'local_enrolconditions_folder', $hassiteconfig);
    }
}
