<?php

namespace Tests\Feature;

use App\Filament\App\Pages\SystemSettingsPage;
use App\Livewire\DemoPersonaSwitcher;
use App\Models\User;
use App\Security\DemoPersona;
use App\Security\RoleCatalogue;
use App\Services\AudienceTargeting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Modules\Admin\Services\PermissionRegistry;
use Tests\TestCase;

class DemoAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('demo.email', 'demo@example.test');
        Config::set('demo.password', 'Demo123');
    }

    public function test_demo_routes_are_registered_on_the_host_and_tenant_host(): void
    {
        $this->assertTrue(Route::has('demo.login'));
        $this->assertTrue(Route::has('demo.login.submit'));
        $this->assertTrue(Route::has('demo.consume'));
        $this->assertTrue(Route::has('tenant.livewire.update'));
    }

    public function test_switching_demo_persona_replaces_permissions_without_touching_the_database(): void
    {
        $user = new User([
            'name' => 'Demo Account',
            'email' => 'demo@example.test',
            'school_id' => 1,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        session()->put(DemoPersona::SESSION_KEY, 'hr');

        $this->assertSame(
            RoleCatalogue::permissionsFor('hr'),
            PermissionRegistry::permissionsFor($user),
        );
        $this->assertTrue(PermissionRegistry::userHasRole($user, 'hr'));
        $this->assertFalse(PermissionRegistry::userHasRole($user, 'finance'));
        $this->assertFalse($user->isStudent());
        $this->assertSame('HR', DemoPersona::roleLabel($user));
        $this->assertSame(['hr'], DemoPersona::roleKeys($user));
        $this->assertFalse(AudienceTargeting::isSystemAdministrator($user));

        session()->put(DemoPersona::SESSION_KEY, 'student');

        $this->assertTrue($user->isStudent());
        $this->assertSame(
            RoleCatalogue::permissionsFor('student'),
            PermissionRegistry::permissionsFor($user),
        );

        session()->put(DemoPersona::SESSION_KEY, 'administrator');

        $this->assertTrue(PermissionRegistry::userHasRole($user, 'librarian'));
        $this->assertSame(['*'], PermissionRegistry::permissionsFor($user));
        $this->assertSame('System Administrator', DemoPersona::roleLabel($user));
        $this->assertSame(['administrator'], DemoPersona::roleKeys($user));
        $this->assertTrue(AudienceTargeting::isSystemAdministrator($user));
    }

    public function test_demo_persona_controls_administrator_only_theme_application(): void
    {
        $user = new User([
            'name' => 'Demo Account',
            'email' => 'demo@example.test',
            'school_id' => 1,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        Auth::shouldReceive('user')->andReturn($user);
        $page = (new \ReflectionClass(SystemSettingsPage::class))->newInstanceWithoutConstructor();

        session()->put(DemoPersona::SESSION_KEY, 'hr');
        $this->assertFalse($page->canApplyThemeToAll());

        session()->put(DemoPersona::SESSION_KEY, 'administrator');
        $this->assertTrue($page->canApplyThemeToAll());
    }

    public function test_demo_switcher_role_buttons_live_inside_the_livewire_root(): void
    {
        $user = new User([
            'name' => 'Demo Account',
            'email' => 'demo@example.test',
            'school_id' => 1,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        Auth::shouldReceive('user')->andReturn($user);
        session()->put(DemoPersona::SESSION_KEY, 'hr');

        $html = Livewire::test(DemoPersonaSwitcher::class)->html();
        $rootPosition = strpos($html, 'wire:id');
        $roleButtonPosition = strpos($html, 'wire:click="switchTo');

        $this->assertNotFalse($rootPosition);
        $this->assertNotFalse($roleButtonPosition);
        $this->assertLessThan($roleButtonPosition, $rootPosition);
    }

    public function test_a_real_account_is_unaffected_by_a_demo_persona_in_the_session(): void
    {
        session()->put(DemoPersona::SESSION_KEY, 'hr');

        $user = new User([
            'name' => 'Real Account',
            'email' => 'teacher@example.test',
            'school_id' => 1,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $this->assertFalse(DemoPersona::isDemoUser($user));
        $this->assertNull(DemoPersona::active($user));
    }
}
