<?php

namespace Tests\Feature;

use App\Filament\App\Pages\SystemSettingsPage;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\SystemSetting;
use Tests\TestCase;

class PerUserThemeTest extends TestCase
{
    use InteractsWithDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    private function school(): School
    {
        return School::findOrFail(config('tenancy.single_tenant_id'));
    }

    private function adminFor(School $school): User
    {
        $role = CustomRole::where('school_id', $school->id)->where('role_key', 'administrator')->firstOrFail();

        return User::where('school_id', $school->id)->where('custom_role_id', $role->id)->firstOrFail();
    }

    private function nonAdminFor(School $school, int $adminRoleId): User
    {
        return User::where('school_id', $school->id)
            ->where(function ($q) use ($adminRoleId) {
                $q->whereNull('custom_role_id')->orWhere('custom_role_id', '!=', $adminRoleId);
            })
            ->firstOrFail();
    }

    private function bootPanel(School $school, User $user): void
    {
        $this->actingAs($user);
        App::instance('current_tenant', $school);
        URL::defaults(['panel' => 'app']);
        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    public function test_theme_change_defaults_to_only_the_current_user(): void
    {
        DB::beginTransaction();
        try {
            $school = $this->school();
            $admin = $this->adminFor($school);
            $this->bootPanel($school, $admin);

            $before = SystemSetting::withoutTenantScope()
                ->where('school_id', $school->id)->where('group', 'branding')->where('key', 'theme')
                ->value('value');

            Livewire::test(SystemSettingsPage::class)
                ->fillForm([
                    'branding_theme' => 'digital_cobalt',
                    'branding_theme_scope' => 'me',
                ])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertSame('digital_cobalt', $admin->fresh()->theme);

            $after = SystemSetting::withoutTenantScope()
                ->where('school_id', $school->id)->where('group', 'branding')->where('key', 'theme')
                ->value('value');

            $this->assertSame($before, $after, 'A personal theme change must not rewrite the school-wide default.');
        } finally {
            DB::rollBack();
        }
    }

    public function test_administrator_can_apply_theme_to_all_users(): void
    {
        DB::beginTransaction();
        try {
            $school = $this->school();
            $admin = $this->adminFor($school);
            $other = $this->nonAdminFor($school, $admin->custom_role_id);
            $this->bootPanel($school, $admin);

            // Give the other user a stale personal override that must be cleared.
            $other->forceFill(['theme' => 'ocean_breeze'])->save();

            Livewire::test(SystemSettingsPage::class)
                ->fillForm([
                    'branding_theme' => 'crimson_academy',
                    'branding_theme_scope' => 'all',
                ])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertSame('crimson_academy', SystemSetting::withoutTenantScope()
                ->where('school_id', $school->id)->where('group', 'branding')->where('key', 'theme')
                ->value('value'));

            $this->assertNull($other->fresh()->theme, 'Applying to all must clear personal overrides.');
            $this->assertNull($admin->fresh()->theme);
        } finally {
            DB::rollBack();
        }
    }

    public function test_non_administrator_scope_all_falls_back_to_personal(): void
    {
        DB::beginTransaction();
        try {
            $school = $this->school();
            $admin = $this->adminFor($school);
            $user = $this->nonAdminFor($school, $admin->custom_role_id);
            // Grant just enough to open the page while keeping them a
            // non-administrator, so the "apply to all" gate is what is tested.
            $user->forceFill(['permissions' => [
                'administration.system_settings_panel.view',
                'administration.system_settings_panel.edit',
            ]])->save();
            $this->bootPanel($school, $user);

            $before = SystemSetting::withoutTenantScope()
                ->where('school_id', $school->id)->where('group', 'branding')->where('key', 'theme')
                ->value('value');

            Livewire::test(SystemSettingsPage::class)
                ->fillForm([
                    'branding_theme' => 'royal_purple',
                    'branding_theme_scope' => 'all',
                ])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertSame('royal_purple', $user->fresh()->theme);
            $this->assertSame($before, SystemSetting::withoutTenantScope()
                ->where('school_id', $school->id)->where('group', 'branding')->where('key', 'theme')
                ->value('value'), 'A non-administrator must never change the school-wide default.');
        } finally {
            DB::rollBack();
        }
    }
}
