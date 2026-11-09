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

namespace local_enrolconditions\local;

use core_text;

/**
 * Reads lists of usernames, for the conditions that use them.
 *
 * Usernames are compared in lower case, as Moodle stores them.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class usernames {
    /**
     * Usernames typed in a text: separated by new lines, spaces, commas or semicolons.
     *
     * @param string $text
     * @return string[] Unique usernames, in the order they appear.
     */
    public static function parse_list(string $text): array {
        return self::unique(preg_split('/[\s,;]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    /**
     * Usernames in the first column of a CSV or text file. A first line "username" is a header.
     *
     * @param string $content
     * @return string[] Unique usernames, in the order they appear.
     */
    public static function parse_csv(string $content): array {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $values = [];
        foreach (preg_split('/\r\n|\r|\n/', $content) as $i => $line) {
            $first = self::first_value($line);
            if ($first === null || ($i === 0 && $first === 'username')) {
                continue;
            }
            $values[] = $first;
        }
        return self::unique($values);
    }

    /**
     * Username in the first column of a line of a CSV or text file.
     *
     * @param string $line
     * @return string|null In lower case; null for an empty line.
     */
    public static function first_value(string $line): ?string {
        $first = core_text::strtolower(trim((string) preg_split('/[,;\t]/', $line, 2)[0], " \t\"'"));
        return $first === '' ? null : $first;
    }

    /**
     * Values that are not valid usernames.
     *
     * @param string[] $usernames
     * @return string[]
     */
    public static function invalid(array $usernames): array {
        return array_values(array_filter(
            $usernames,
            fn(string $username): bool => clean_param($username, PARAM_USERNAME) !== $username
        ));
    }

    /**
     * Unique values in lower case.
     *
     * @param string[] $values
     * @return string[]
     */
    protected static function unique(array $values): array {
        $unique = [];
        foreach ($values as $value) {
            $value = core_text::strtolower(trim($value));
            if ($value !== '') {
                $unique[$value] = true;
            }
        }
        return array_map('strval', array_keys($unique));
    }
}
