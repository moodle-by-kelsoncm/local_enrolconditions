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
 * Strings for component 'enrolcond_sqlquery', language 'en'.
 *
 * @package    enrolcond_sqlquery
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['cachedef_results'] = 'Usernames returned by the queries';
$string['cachettl'] = 'Keep results for';
$string['cachettl_desc'] = 'How long the usernames returned by a query are reused before the query runs again. 0 runs the query on every check.';
$string['desc'] = 'You are in the list "{$a}"';
$string['descmissing'] = 'You are in a list that no longer exists';
$string['errorcomment'] = 'The query cannot contain comments (--, /* or #).';
$string['errorforbidden'] = 'The query cannot contain "{$a}".';
$string['errormissing'] = 'The query {$a} is no longer defined.';
$string['errorquery'] = 'Invalid query: {$a}';
$string['errorrun'] = 'The query failed: {$a}';
$string['errorselect'] = 'The query must start with SELECT or WITH.';
$string['errorsemicolon'] = 'The query must be a single statement, without ";".';
$string['errortransaction'] = 'The list is not run inside a database transaction; the condition is unknown until the list is read outside one.';
$string['invalidquery'] = 'Choose a list.';
$string['noqueries'] = 'The administrator has not defined any list yet.';
$string['pluginname'] = 'List from a query';
$string['privacy:metadata'] = 'The List from a query enrolment condition does not store any personal data. The queries read data stored by other components.';
$string['query'] = 'List';
$string['query_help'] = 'Users whose usernames this list contains meet the condition. The administrator defines the lists as database queries; the list may change at any time. Re-check the enrolments at login or periodically to follow its changes.';
$string['queryheading'] = 'List {$a}';
$string['queryname'] = 'Name';
$string['queryname_desc'] = 'Name shown to teachers and to users who do not meet the condition. Leave it empty to disable the list.';
$string['querysql'] = 'Query';
$string['querysql_desc'] = 'A single SELECT (or WITH ... SELECT) that returns a column named "username". Use {table} for table names. :courseid is the course of the enrolment method. Statements that change data, comments and ";" are not accepted; the query runs once when saved.';
$string['settingsintro'] = 'Only administrators define these lists. The queries run with the database permissions of Moodle and can read any table: review them as you would review code.';
$string['slots'] = 'Number of lists';
$string['slots_desc'] = 'How many lists can be defined below, up to {$a}.';
