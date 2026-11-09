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
 * Strings for component 'enrolcond_userfile', language 'en'.
 *
 * @package    enrolcond_userfile
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['cachedef_usernames'] = 'Usernames read from the files of usernames';
$string['cleanupfilestask'] = 'Delete files of usernames no longer used by any condition';
$string['desc'] = 'Your username is in the file {$a->filename} ({$a->count} users)';
$string['empty'] = 'The file has no usernames.';
$string['file'] = 'File of usernames';
$string['file_help'] = 'A CSV or text file with one username per line, in the first column. A first line "username" is ignored as a header. Upper and lower case are the same. The file is kept with the condition: to update the list, replace the file.';
$string['filemissing'] = 'The file of usernames of this condition no longer exists.';
$string['invalidusernames'] = 'These are not valid usernames: {$a}';
$string['pluginname'] = 'File of usernames';
$string['privacy:metadata:core_files'] = 'Files of usernames uploaded by teachers, which list the users who meet the condition.';
$string['toomany'] = 'A file can have at most {$a} usernames.';
