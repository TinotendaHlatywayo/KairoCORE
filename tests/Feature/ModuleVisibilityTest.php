<?php

namespace Tests\Feature;

use App\Filament\App\Pages\SystemSettingsPage;
use App\Models\School;
use App\Models\User;
use App\Services\ModuleVisibilityManager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Modules\Admin\Models\SystemSetting;
use Modules\Admin\Services\PermissionRegistry;
use Tests\TestCase;

class ModuleVisibilityTest extends TestCase
{
    private const NEW_MODULES = ['lms', 'knowledge', 'reports', 'administration', 'saas'];

    private array $savedModules = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->useMysql();

        $school = School::where('subdomain', 'rujeko')->first();
        if (! $school) {
            $school = School::create(['name' => 'Rujeko High', 'subdomain' => 'rujeko', 'status' => 'active']);
        }
        $user = User::where('school_id', $school->id)->first();
        if (! $user) {
            User::create([
                'school_id' => $school->id,
                'name' => 'Admin',
                'email' => 'admin@rujeko.test',
                'password' => Hash::make('Password@1'),
                'account_status' => 'active',
                'requested_role' => 'administrator',
            ]);
        }

        // Preserve the tenant's real module toggle state so this test never
        // leaves the database modified.
        foreach (array_merge(self::NEW_MODULES, ['students']) as $module) {
            $this->savedModules[$module] = SystemSetting::get('modules', $module, '1');
        }
    }

    private function useMysql(): void
    {
        Config::set('app.env', 'local');
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    protected function tearDown(): void
    {
        foreach ($this->savedModules as $module => $value) {
            SystemSetting::set('modules', $module, $value);
        }

        parent::tearDown();
    }

    private function tenant(): array
    {
        $school = School::where('subdomain', 'rujeko')->first() ?? School::first();
        $user = User::where('school_id', $school->id)->first();

        $this->assertNotNull($school);
        $this->assertNotNull($user);

        App::instance('current_tenant', $school);
        view()->share('school', $school);

        return [$school, $user];
    }

    public function test_master_toggles_resolve_visibility(): void
    {
        $this->tenant();

        foreach (self::NEW_MODULES as $module) {
            SystemSetting::set('modules', $module, '0');
            $this->assertFalse(ModuleVisibilityManager::isVisible($module), "$module should be off");
            $this->assertFalse(ModuleVisibilityManager::isModuleVisible($module), "module $module should be off");

            SystemSetting::set('modules', $module, '1');
            $this->assertTrue(ModuleVisibilityManager::isVisible($module), "$module should be on");
        }
    }

    public function test_sidebar_hides_closed_modules(): void
    {
        [, $user] = $this->tenant();
        PermissionRegistry::ensureAdminHasRole($user, $user->school_id);
        $user->forceFill(['account_status' => 'active'])->save();
        $this->actingAs($user);

        foreach (self::NEW_MODULES as $module) {
            SystemSetting::set('modules', $module, '0');
        }

        $html = $this->get('/workspace')->assertOk()->getContent();

        foreach (['Homework &amp; Lessons', 'School Repository', 'Analytics Explorer', 'Generate Report', 'User Accounts', 'Overview &amp; Billing'] as $needle) {
            $this->assertStringNotContainsString($needle, $html, "closed module item '$needle' should be hidden");
        }

        // Even with all modules closed, the topbar settings shortcut must remain accessible for admins
        $this->assertStringContainsString('system-settings-page', $html, 'Settings shortcut must be present in topbar even when all modules are closed');

        // Turning one back on restores its sidebar entry. Simulate a fresh HTTP
        // request: Filament's navigation manager is a scoped singleton already
        // mounted in this request's container, so the reopened request needs a
        // freshly booted application to reflect the new module settings.
        SystemSetting::set('modules', 'lms', '1');
        $this->refreshApplication();
        $this->useMysql();
        [$school] = $this->tenant();
        $user = User::where('school_id', $school->id)->first();
        PermissionRegistry::ensureAdminHasRole($user, $user->school_id);
        $user->forceFill(['account_status' => 'active'])->save();
        $this->actingAs($user);
        $html = $this->get('/workspace')->assertOk()->getContent();
        $this->assertStringContainsString('Homework', $html, 'LMS item should reappear when re-enabled');
    }

    public function test_system_settings_page_renders_new_module_toggles(): void
    {
        [, $user] = $this->tenant();
        PermissionRegistry::ensureAdminHasRole($user, $user->school_id);
        $user->forceFill(['account_status' => 'active'])->save();
        $this->actingAs($user);

        $component = Livewire::test(SystemSettingsPage::class);
        $component->assertOk();

        $page = $component->instance();
        $names = [];
        $this->collectFieldNames($page->form->getComponents(), $names);

        foreach (self::NEW_MODULES as $module) {
            $this->assertContains('modules_'.$module, $names, "modules_$module toggle missing from Manage Modules form");
        }
    }

    private function collectFieldNames($components, array &$names): void
    {
        foreach ($components as $component) {
            if (method_exists($component, 'getName') && $component->getName()) {
                $names[] = $component->getName();
            }
            if (method_exists($component, 'getChildComponentContainer')) {
                $this->collectFieldNames($component->getChildComponentContainer()->getComponents(), $names);
            }
        }
    }
}
