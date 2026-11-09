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

namespace enrolcond_userfile\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use enrolcond_userfile\condition;
use local_enrolconditions\local\usernames;

/**
 * Privacy provider: the files of usernames name the users they list.
 *
 * A user is found by their username in the files of each course. Deleting the data of a user removes their
 * line from the files.
 *
 * @package    enrolcond_userfile
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');
        return $collection;
    }

    /**
     * Files of usernames, optionally of one context.
     *
     * @param int|null $contextid
     * @return \stored_file[]
     */
    protected static function get_files(?int $contextid = null): array {
        global $DB;
        $params = ['component' => condition::COMPONENT, 'filearea' => condition::FILEAREA];
        $where = "component = :component AND filearea = :filearea AND filename <> '.'";
        if ($contextid !== null) {
            $where .= ' AND contextid = :contextid';
            $params['contextid'] = $contextid;
        }
        $fs = get_file_storage();
        $files = [];
        foreach ($DB->get_records_select('files', $where, $params, 'id') as $record) {
            $files[] = $fs->get_file_instance($record);
        }
        return $files;
    }

    /**
     * Username of a user, in lower case.
     *
     * @param int $userid
     * @return string|null
     */
    protected static function get_username(int $userid): ?string {
        $user = \core_user::get_user($userid, 'id, username');
        return $user ? \core_text::strtolower($user->username) : null;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $username = self::get_username($userid);
        $contextids = [];
        foreach ($username === null ? [] : self::get_files() as $file) {
            if (isset(condition::get_usernames($file)[$username])) {
                $contextids[$file->get_contextid()] = true;
            }
        }
        if ($contextids) {
            global $DB;
            [$insql, $params] = $DB->get_in_or_equal(array_keys($contextids), SQL_PARAMS_NAMED);
            $contextlist->add_from_sql("SELECT id FROM {context} WHERE id $insql", $params);
        }
        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;
        $listed = [];
        foreach (self::get_files($userlist->get_context()->id) as $file) {
            $listed += condition::get_usernames($file);
        }
        foreach (array_chunk(array_keys($listed), 1000) as $chunk) {
            [$insql, $params] = $DB->get_in_or_equal(array_map('strval', $chunk), SQL_PARAMS_NAMED);
            $userlist->add_from_sql('id', "SELECT id FROM {user} WHERE username $insql", $params);
        }
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist): void {
        $username = self::get_username((int) $contextlist->get_user()->id);
        if ($username === null) {
            return;
        }
        foreach ($contextlist->get_contexts() as $context) {
            $names = [];
            foreach (self::get_files($context->id) as $file) {
                if (isset(condition::get_usernames($file)[$username])) {
                    $names[] = $file->get_filename();
                }
            }
            if ($names) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', condition::COMPONENT)],
                    (object) ['username' => $username, 'files' => $names]
                );
            }
        }
    }

    /**
     * Removes the lines of some usernames from the files of a context.
     *
     * @param int $contextid
     * @param string[]|null $remove Usernames, in lower case; null removes the files.
     */
    protected static function remove_from_files(int $contextid, ?array $remove): void {
        $remove = $remove === null ? null : array_fill_keys($remove, true);
        foreach (self::get_files($contextid) as $file) {
            if ($remove === null) {
                $file->delete();
                continue;
            }
            $lines = preg_split('/\r\n|\r|\n/', $file->get_content());
            $kept = array_filter($lines, fn(string $line): bool => !isset($remove[usernames::first_value($line) ?? '']));
            if (count($kept) === count($lines)) {
                continue;
            }
            $record = [
                'contextid' => $file->get_contextid(),
                'component' => $file->get_component(),
                'filearea' => $file->get_filearea(),
                'itemid' => $file->get_itemid(),
                'filepath' => $file->get_filepath(),
                'filename' => $file->get_filename(),
            ];
            $file->delete();
            get_file_storage()->create_file_from_string($record, implode("\n", $kept));
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context): void {
        self::remove_from_files($context->id, null);
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $username = self::get_username((int) $contextlist->get_user()->id);
        if ($username === null) {
            return;
        }
        foreach ($contextlist->get_contexts() as $context) {
            self::remove_from_files($context->id, [$username]);
        }
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $remove = array_filter(array_map(fn($userid) => self::get_username((int) $userid), $userlist->get_userids()));
        if ($remove) {
            self::remove_from_files($userlist->get_context()->id, array_values($remove));
        }
    }
}
