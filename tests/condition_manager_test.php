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

namespace local_enrolconditions;

use coding_exception;
use local_enrolconditions\condition\manager;
use local_enrolconditions\fixtures\hooked_condition;
use local_enrolconditions\hook\collect_conditions;
use local_enrolconditions\plugininfo\enrolcond;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/fixtures/fake_condition.php');
require_once(__DIR__ . '/fixtures/hooked_condition.php');

/**
 * Tests of the condition infrastructure: subplugin type, manager, hook and admin pages.
 *
 * @package    local_enrolconditions
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(manager::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(enrolcond::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(condition\base::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(collect_conditions::class)]
final class condition_manager_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        manager::reset_caches();
    }

    #[\Override]
    protected function tearDown(): void {
        manager::reset_caches();
        parent::tearDown();
    }

    /**
     * Makes the hook manager use only the callbacks of the test fixtures.
     */
    protected function use_fixture_hooks(): void {
        \core\di::set(
            \core\hook\manager::class,
            \core\hook\manager::phpunit_get_instance(['local_enrolconditions' => __DIR__ . '/fixtures/hooks.php'])
        );
        manager::reset_caches();
    }

    public function test_subplugin_type_is_declared(): void {
        $types = \core_component::get_plugin_types();
        $this->assertArrayHasKey('enrolcond', $types);
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $types['enrolcond']);
        $this->assertStringEndsWith('/local/enrolconditions/condition', $path);
        $this->assertSame('local_enrolconditions', \core_component::get_subtype_parent('enrolcond'));
        $this->assertSame(enrolcond::class, ltrim(\core_plugin_manager::resolve_plugininfo_class('enrolcond'), '\\'));
    }

    public function test_unknown_condition_is_null(): void {
        $this->assertNull(manager::get_condition('doesnotexist'));
        $this->assertNotContains('doesnotexist', manager::get_installed_types());
        $this->assertFalse(manager::is_enabled('doesnotexist'));
    }

    public function test_register_for_testing(): void {
        $fake = new \enrolcond_fake\condition();
        manager::register_for_testing('fake', $fake);

        $this->assertSame($fake, manager::get_condition('fake'));
        $this->assertContains('fake', manager::get_installed_types());
        $this->assertContains('fake', manager::get_enabled_types());
        $this->assertTrue(manager::is_enabled('fake'));

        manager::reset_caches();
        $this->assertNull(manager::get_condition('fake'));
    }

    public function test_base_defaults(): void {
        $fake = new \enrolcond_fake\condition();
        $instance = (object) ['id' => 1, 'courseid' => 2];

        $this->assertSame('fake', $fake->get_type());
        $this->assertTrue($fake->is_met(5, $instance, ['userids' => [5]]));
        $this->assertFalse($fake->is_met(6, $instance, ['userids' => [5]]));
        $this->assertSame(['userids' => [5]], $fake->config_to_form_data(['userids' => [5]]));
        $this->assertSame([\core\event\user_updated::class], $fake->get_observed_events());

        // Form validation goes through validate_config().
        $this->assertSame(['userids' => 'required'], $fake->config_form_validation(['userids' => ''], []));
        $this->assertSame([], $fake->config_form_validation(['userids' => '3,4'], []));
    }

    public function test_hooked_condition_is_available(): void {
        $this->use_fixture_hooks();

        $condition = manager::get_condition('local_enrolconditions_hooked');
        $this->assertInstanceOf(hooked_condition::class, $condition);
        $this->assertContains('local_enrolconditions_hooked', manager::get_installed_types());
        $this->assertTrue(manager::is_enabled('local_enrolconditions_hooked'));
    }

    public function test_hook_rejects_type_without_component_prefix(): void {
        $hook = new collect_conditions();
        $this->expectException(coding_exception::class);
        $hook->add_condition(new hooked_condition('hooked'));
    }

    public function test_hook_rejects_duplicate_type(): void {
        $hook = new collect_conditions();
        $hook->add_condition(new hooked_condition());
        $this->expectException(coding_exception::class);
        $hook->add_condition(new hooked_condition());
    }

    public function test_enable_plugin(): void {
        $this->assertFalse(enrolcond::enable_plugin('fake', 1));
        $this->assertTrue(enrolcond::enable_plugin('fake', 0));
        $this->assertEquals(1, get_config('enrolcond_fake', 'disabled'));
        $this->assertFalse(enrolcond::enable_plugin('fake', 0));
        $this->assertTrue(enrolcond::enable_plugin('fake', 1));
        $this->assertFalse(get_config('enrolcond_fake', 'disabled'));
    }

    public function test_admin_pages(): void {
        $this->setAdminUser();
        $root = admin_get_root(true, true);

        $this->assertNotNull($root->locate('local_enrolconditions_folder'));
        $this->assertNotNull($root->locate(enrolcond::MANAGE_SECTION));
        $this->assertSame(enrolcond::MANAGE_SECTION, enrolcond::get_manage_url()->get_param('section'));
    }

    public function test_hook_is_discoverable(): void {
        $this->assertArrayHasKey(collect_conditions::class, hooks::discover_hooks());
    }
}
