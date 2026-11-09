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

namespace enrolcond_userfile\task;

use enrolcond_userfile\condition;

/**
 * Deletes files of usernames that no condition refers to any more.
 *
 * They are left behind when a condition is removed, or when its form is saved but the enrolment method is not.
 * Files younger than a day are kept, so forms still open are not affected.
 *
 * @package    enrolcond_userfile
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_files extends \core\task\scheduled_task {
    #[\Override]
    public function get_name() {
        return get_string('cleanupfilestask', condition::COMPONENT);
    }

    /**
     * File ids referred to by the condition trees of the enrolment methods of a course.
     *
     * @param int $courseid
     * @return bool[] Keys are the file ids.
     */
    public static function get_referenced_fileids(int $courseid): array {
        global $DB;
        $ids = [];
        $trees = $DB->get_fieldset_select('enrol', 'customtext2', 'courseid = ? AND enrol = ?', [$courseid, 'conditional']);
        foreach ($trees as $json) {
            $tree = json_decode((string) $json, true);
            if (is_array($tree)) {
                array_walk_recursive($tree, function ($value, $key) use (&$ids): void {
                    if ($key === 'fileid') {
                        $ids[(int) $value] = true;
                    }
                });
            }
        }
        return $ids;
    }

    #[\Override]
    public function execute() {
        global $DB;
        $records = $DB->get_records_sql(
            "SELECT f.id, f.itemid, ctx.instanceid AS courseid
               FROM {files} f
               JOIN {context} ctx ON ctx.id = f.contextid AND ctx.contextlevel = :level
              WHERE f.component = :component AND f.filearea = :filearea AND f.filename <> '.'
                AND f.timemodified < :before",
            ['level' => CONTEXT_COURSE, 'component' => condition::COMPONENT, 'filearea' => condition::FILEAREA,
                'before' => time() - DAYSECS]
        );
        $referenced = [];
        $fs = get_file_storage();
        $deleted = 0;
        foreach ($records as $record) {
            $referenced[$record->courseid] ??= self::get_referenced_fileids((int) $record->courseid);
            if (!isset($referenced[$record->courseid][(int) $record->itemid])) {
                $fs->get_file_by_id($record->id)?->delete();
                $deleted++;
            }
        }
        mtrace("$deleted files of usernames deleted.");
    }
}
