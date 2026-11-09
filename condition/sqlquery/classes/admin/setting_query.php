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

namespace enrolcond_sqlquery\admin;

use enrolcond_sqlquery\local\queries;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Admin setting with the SQL of a query: checks the text and runs it once before saving.
 *
 * @package    enrolcond_sqlquery
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_query extends \admin_setting_configtextarea {
    /**
     * Constructor.
     *
     * @param string $name
     * @param string $visiblename
     * @param string $description
     */
    public function __construct($name, $visiblename, $description) {
        parent::__construct($name, $visiblename, $description, '', PARAM_RAW, 60, 6);
    }

    #[\Override]
    public function validate($data) {
        $data = trim((string) $data);
        if ($data === '') {
            return true;
        }
        $error = queries::check_text($data);
        if ($error !== null) {
            return $error;
        }
        try {
            queries::run($data, SITEID, 1);
        } catch (\Throwable $e) {
            return get_string('errorrun', queries::COMPONENT, $e->getMessage());
        }
        return true;
    }
}
