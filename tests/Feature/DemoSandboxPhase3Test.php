<?php

namespace Tests\Feature;

use App\Http\Responses\DemoLogoutResponse;
use App\Models\School;
use App\Services\DemoSandboxService;
use App\Support\PlatformTenantFilter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class DemoSandboxPhase3Test extends TestCase
{
    use DatabaseTransactions;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql.database', 'schoolcore_testing');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutTenant();
    }

    public function test_logout_deletes_only_the_active_sandbox(): void
    {
        $sandbox = $this->createSchool(true, now()->addHour());
        $other = $this->createSchool(true, now()->addHour());

        app()->instance('current_tenant', $sandbox);

        app(DemoLogoutResponse::class)->toResponse(request());
        $this->app->terminate();

        $this->assertNull(School::withoutGlobalScopes()->withTrashed()->find($sandbox->id));
        $this->assertNotNull(School::withoutGlobalScopes()->withTrashed()->find($other->id));
    }

    public function test_cleanup_deletes_only_expired_sandboxes(): void
    {
        config()->set('demo.sandbox.pool_enabled', false);

        $expired = $this->createSchool(true, now()->subMinute());
        $active = $this->createSchool(true, now()->addHour());
        $real = $this->createSchool(false, null);

        $deleted = app(DemoSandboxService::class)->deleteExpired();

        $this->assertSame(1, $deleted);
        $this->assertNull(School::withoutGlobalScopes()->withTrashed()->find($expired->id));
        $this->assertNotNull(School::withoutGlobalScopes()->withTrashed()->find($active->id));
        $this->assertNotNull(School::withoutGlobalScopes()->withTrashed()->find($real->id));
    }

    public function test_sandboxes_are_hidden_from_platform_queries(): void
    {
        $sandbox = $this->createSchool(true, now()->addHour());
        $real = $this->createSchool(false, null);

        $this->assertFalse(School::query()->whereKey($sandbox->id)->exists());
        $this->assertTrue(School::query()->whereKey($real->id)->exists());
        $this->assertTrue(PlatformTenantFilter::isExcluded((int) $sandbox->id));
        $this->assertFalse(PlatformTenantFilter::isExcluded((int) $real->id));
    }

    private function createSchool(bool $sandbox, ?Carbon $expiresAt): School
    {
        return School::withoutGlobalScopes()->create([
            'name' => 'Phase 3 '.strtoupper(Str::random(8)),
            'subdomain' => 'phase3-'.strtolower(Str::random(12)),
            'login_code' => 'P3-'.strtoupper(Str::random(12)),
            'status' => 'active',
            'institution_type' => 'secondary',
            'is_demo_sandbox' => $sandbox,
            'expires_at' => $expiresAt,
        ]);
    }
}
