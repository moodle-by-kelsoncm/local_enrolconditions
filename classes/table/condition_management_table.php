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

namespace local_enrolconditions\table;

use core\url;
use local_enrolconditions\plugininfo\enrolcond;

/**
 * Table that lists the installed conditions, with enable, settings and uninstall links.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition_management_table extends \core_admin\table\plugin_management_table {
    /**
     * Plugin type listed by the table.
     *
     * @return string
     */
    protected function get_plugintype(): string {
        return 'enrolcond';
    }

    /**
     * Base URL of the table.
     */
    public function guess_base_url(): void {
        $this->define_baseurl(enrolcond::get_manage_url());
    }

    /**
     * URL of the page that enables and disables conditions.
     *
     * @param array $params
     * @return url
     */
    protected function get_action_url(array $params = []): url {
        return new url('/local/enrolconditions/conditions.php', $params);
    }
}
