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
 * Settings of enrolcond_sqlquery: the queries that teachers can choose.
 *
 * @package    enrolcond_sqlquery
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrolcond_sqlquery\local\queries;

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'enrolcond_sqlquery/intro',
        '',
        get_string('settingsintro', 'enrolcond_sqlquery')
    ));
    $settings->add(new admin_setting_configtext(
        'enrolcond_sqlquery/slots',
        get_string('slots', 'enrolcond_sqlquery'),
        get_string('slots_desc', 'enrolcond_sqlquery', queries::MAX_SLOTS),
        queries::DEFAULT_SLOTS,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configduration(
        'enrolcond_sqlquery/cachettl',
        get_string('cachettl', 'enrolcond_sqlquery'),
        get_string('cachettl_desc', 'enrolcond_sqlquery'),
        5 * MINSECS
    ));
    for ($i = 1; $i <= queries::get_slots(); $i++) {
        $settings->add(new admin_setting_heading(
            'enrolcond_sqlquery/query' . $i,
            get_string('queryheading', 'enrolcond_sqlquery', $i),
            ''
        ));
        $settings->add(new admin_setting_configtext(
            'enrolcond_sqlquery/query' . $i . 'name',
            get_string('queryname', 'enrolcond_sqlquery'),
            get_string('queryname_desc', 'enrolcond_sqlquery'),
            '',
            PARAM_TEXT
        ));
        $settings->add(new \enrolcond_sqlquery\admin\setting_query(
            'enrolcond_sqlquery/query' . $i . 'sql',
            get_string('querysql', 'enrolcond_sqlquery'),
            get_string('querysql_desc', 'enrolcond_sqlquery')
        ));
    }
}
