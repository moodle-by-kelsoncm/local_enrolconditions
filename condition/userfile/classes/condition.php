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

namespace enrolcond_userfile;

use local_enrolconditions\local\usernames;
use MoodleQuickForm;
use stdClass;

/**
 * Condition met by the users whose usernames are in a file kept with the condition.
 *
 * The file (CSV or text, usernames in the first column) is stored in the course context, in the file area
 * enrolcond_userfile/usernames, item fileid. Teachers update the list by replacing the file.
 * Configuration: ['fileid' => item id, 'filename' => name, 'count' => number of usernames].
 *
 * @package    enrolcond_userfile
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \local_enrolconditions\condition\base {
    /** @var string Component of the files. */
    public const COMPONENT = 'enrolcond_userfile';

    /** @var string File area. */
    public const FILEAREA = 'usernames';

    /** @var int Maximum number of usernames in a file. */
    public const MAX_USERNAMES = 200000;

    /** @var array[] Usernames by content hash, in this request. */
    protected static array $usernames = [];

    /**
     * Options of the file picker.
     *
     * @return array
     */
    protected function picker_options(): array {
        return ['accepted_types' => ['.csv', '.txt'], 'maxfiles' => 1, 'subdirs' => 0];
    }

    /**
     * Stored file of a configuration.
     *
     * @param array $config
     * @param \context $context Course context.
     * @return \stored_file|null
     */
    public static function get_file(array $config, \context $context): ?\stored_file {
        $files = get_file_storage()->get_area_files(
            $context->id,
            self::COMPONENT,
            self::FILEAREA,
            (int) ($config['fileid'] ?? 0),
            'id',
            false
        );
        return $files ? reset($files) : null;
    }

    /**
     * Usernames in a stored file, cached by its content.
     *
     * @param \stored_file $file
     * @return bool[] Keys are the usernames.
     */
    public static function get_usernames(\stored_file $file): array {
        $hash = $file->get_contenthash();
        if (!isset(self::$usernames[$hash])) {
            $cache = \cache::make(self::COMPONENT, 'usernames');
            $list = $cache->get($hash);
            if ($list === false) {
                $list = array_fill_keys(usernames::parse_csv($file->get_content()), true);
                $cache->set($hash, $list);
            }
            self::$usernames = [$hash => $list] + array_slice(self::$usernames, 0, 9, true);
        }
        return self::$usernames[$hash];
    }

    #[\Override]
    public function is_met(int $userid, stdClass $instance, array $config): bool {
        $file = self::get_file($config, \context_course::instance($instance->courseid));
        if (!$file) {
            // Unknown, not false: a lost file must not suspend anybody.
            throw new \moodle_exception('filemissing', self::COMPONENT);
        }
        $user = \core_user::get_user($userid, 'id, username');
        return $user && isset(self::get_usernames($file)[\core_text::strtolower($user->username)]);
    }

    #[\Override]
    public function get_description(array $config, bool $met): string {
        return get_string('desc', self::COMPONENT, (object) [
            'filename' => s((string) ($config['filename'] ?? '')),
            'count' => (int) ($config['count'] ?? 0),
        ]);
    }

    #[\Override]
    public function config_form_definition(MoodleQuickForm $mform): void {
        $mform->addElement('hidden', 'fileid');
        $mform->setType('fileid', PARAM_INT);
        $mform->addElement('filepicker', 'usernamesfile', get_string('file', self::COMPONENT), null, $this->picker_options());
        $mform->addHelpButton('usernamesfile', 'file', self::COMPONENT);
        $mform->addRule('usernamesfile', null, 'required', null, 'client');
    }

    #[\Override]
    public function config_to_form_data_in_context(array $config, \context $context): array {
        // Starts empty: file_prepare_draft_area() only copies the files into a new draft area.
        $draftitemid = 0;
        file_prepare_draft_area(
            $draftitemid,
            $context->id,
            self::COMPONENT,
            self::FILEAREA,
            empty($config['fileid']) ? null : (int) $config['fileid'],
            $this->picker_options()
        );
        return ['fileid' => (int) ($config['fileid'] ?? 0), 'usernamesfile' => $draftitemid];
    }

    /**
     * File in the draft area of the form.
     *
     * @param int $draftitemid
     * @return \stored_file|null
     */
    protected function get_draft_file(int $draftitemid): ?\stored_file {
        global $USER;
        if ($draftitemid <= 0) {
            return null;
        }
        $files = get_file_storage()->get_area_files(
            \context_user::instance($USER->id)->id,
            'user',
            'draft',
            $draftitemid,
            'id DESC',
            false
        );
        return $files ? reset($files) : null;
    }

    #[\Override]
    public function config_from_form_data(stdClass $data): array {
        $file = $this->get_draft_file((int) ($data->usernamesfile ?? 0));
        $list = $file ? usernames::parse_csv($file->get_content()) : [];
        return [
            'fileid' => (int) ($data->fileid ?? 0),
            'filename' => $file ? $file->get_filename() : '',
            'count' => count($list),
        ];
    }

    #[\Override]
    public function config_form_validation(array $data, array $files): array {
        $file = $this->get_draft_file((int) ($data['usernamesfile'] ?? 0));
        if (!$file) {
            return ['usernamesfile' => get_string('required')];
        }
        $list = usernames::parse_csv($file->get_content());
        if (!$list) {
            return ['usernamesfile' => get_string('empty', self::COMPONENT)];
        }
        if (count($list) > self::MAX_USERNAMES) {
            return ['usernamesfile' => get_string('toomany', self::COMPONENT, self::MAX_USERNAMES)];
        }
        $invalid = usernames::invalid($list);
        if ($invalid) {
            return ['usernamesfile' => get_string(
                'invalidusernames',
                self::COMPONENT,
                s(implode(', ', array_slice($invalid, 0, 10)))
            )];
        }
        return [];
    }

    #[\Override]
    public function config_after_submission(array $config, stdClass $data, \context $context): array {
        $fileid = (int) ($config['fileid'] ?? 0) ?: random_int(1, 2147483647);
        file_save_draft_area_files(
            (int) $data->usernamesfile,
            $context->id,
            self::COMPONENT,
            self::FILEAREA,
            $fileid,
            $this->picker_options()
        );
        $config['fileid'] = $fileid;
        return $config;
    }

    #[\Override]
    public function validate_config(array $config): array {
        if ((int) ($config['fileid'] ?? 0) <= 0 || (int) ($config['count'] ?? 0) <= 0) {
            return ['usernamesfile' => get_string('required')];
        }
        return [];
    }
}
