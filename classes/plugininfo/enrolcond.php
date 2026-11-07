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

namespace local_enrolconditions\plugininfo;

use core\url;

/**
 * Plugin info for the enrolcond subplugins (conditions of local_enrolconditions).
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrolcond extends \core\plugininfo\base {
    /** @var string Admin section of the condition management page. */
    public const MANAGE_SECTION = 'local_enrolconditions_manage';

    /**
     * Conditions can be disabled by the administrator.
     *
     * @return bool
     */
    public static function plugintype_supports_disabling(): bool {
        return true;
    }

    /**
     * Conditions can be uninstalled.
     *
     * @return bool
     */
    public function is_uninstall_allowed(): bool {
        return true;
    }

    /**
     * URL of the condition management page.
     *
     * @return url
     */
    public static function get_manage_url(): url {
        return new url('/admin/settings.php', ['section' => self::MANAGE_SECTION]);
    }

    /**
     * Name of the settings section of this condition, if it has settings.
     *
     * @return string|null
     */
    public function get_settings_section_name(): ?string {
        if (!file_exists($this->full_path('settings.php'))) {
            return null;
        }
        return 'enrolcond_' . $this->name . '_settings';
    }

    /**
     * Includes the settings.php of the condition, if there is one.
     *
     * @param \part_of_admin_tree $adminroot
     * @param string $parentnodename
     * @param bool $hassiteconfig Whether the current user has moodle/site:config.
     */
    public function load_settings(\part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig): void {
        // Globals that settings.php may refer to.
        global $CFG, $USER, $DB, $OUTPUT, $PAGE;

        $ADMIN = $adminroot; // phpcs:ignore moodle.NamingConventions.ValidVariableName.VariableNameUCase
        $plugininfo = $this;

        if (!$this->is_installed_and_upgraded() || !$hassiteconfig) {
            return;
        }
        $section = $this->get_settings_section_name();
        if ($section === null) {
            return;
        }

        $settings = new \admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);
        include($this->full_path('settings.php')); // This may also set $settings to null.
        if ($settings) {
            $ADMIN->add($parentnodename, $settings);
        }
    }

    /**
     * Names of the enabled conditions.
     *
     * @return array<string, string> Condition names, keyed by name.
     */
    public static function get_enabled_plugins(): array {
        $plugins = \core_plugin_manager::instance()->get_installed_plugins('enrolcond');
        $enabled = [];
        foreach (array_keys($plugins) as $name) {
            if (empty(get_config('enrolcond_' . $name, 'disabled'))) {
                $enabled[$name] = $name;
            }
        }
        return $enabled;
    }

    /**
     * Enables or disables a condition.
     *
     * @param string $pluginname Condition name, without the enrolcond_ prefix.
     * @param int $enabled 1 to enable, 0 to disable.
     * @return bool Whether the setting changed.
     */
    public static function enable_plugin(string $pluginname, int $enabled): bool {
        $component = 'enrolcond_' . $pluginname;
        $olddisabled = !empty(get_config($component, 'disabled'));
        $disabled = empty($enabled);
        if ($olddisabled === $disabled) {
            return false;
        }
        if ($disabled) {
            set_config('disabled', 1, $component);
        } else {
            unset_config('disabled', $component);
        }
        add_to_config_log('disabled', (int) $olddisabled, (int) $disabled, $component);
        \core_plugin_manager::reset_caches();
        return true;
    }
}
