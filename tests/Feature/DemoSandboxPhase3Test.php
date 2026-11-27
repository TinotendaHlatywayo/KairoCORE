<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureDemoSandboxAccess;
use App\Http\Responses\DemoLogoutResponse;
use App\Models\School;
use App\Models\User;
use App\Services\DemoSandboxService;
use App\Support\DemoSandbox;
use App\Support\PlatformTenantFilter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    public function test_deleted_sandbox_data_is_restored_on_next_login(): void
    {
        config()->set('demo.sandbox.pool_enabled', false);

        $sandboxes = app(DemoSandboxService::class);
        $first = $sandboxes->claim();
        $firstSchoolId = (int) $first['school']['id'];
        $studentId = (int) DB::table('students')->where('school_id', $firstSchoolId)->value('id');
        $originalCount = DB::table('students')->where('school_id', $firstSchoolId)->count();

        DB::table('students')->where('id', $studentId)->delete();

        $this->assertSame($originalCount - 1, DB::table('students')->where('school_id', $firstSchoolId)->count());

        app()->instance('current_tenant', $first['school']);
        app(DemoLogoutResponse::class)->toResponse(request());
        $this->app->terminate();

        $this->assertNull(School::withoutGlobalScopes()->withTrashed()->find($firstSchoolId));

        $second = $sandboxes->claim();
        $secondSchoolId = (int) $second['school']['id'];

        try {
            $this->assertNotSame($firstSchoolId, $secondSchoolId);
            $this->assertSame($originalCount, DB::table('students')->where('school_id', $secondSchoolId)->count());
            $this->assertFalse(DB::table('students')->where('school_id', $secondSchoolId)->where('id', $studentId)->exists());
        } finally {
            $sandboxes->delete($secondSchoolId);
        }
    }

    public function test_legacy_master_demo_session_is_ended_without_deleting_master(): void
    {
        $master = School::withoutGlobalScopes()->firstOrCreate(
            ['subdomain' => (string) config('demo.school.subdomain')],
            [
                'name' => 'Legacy Master Demo',
                'login_code' => 'LEGACY-DEMO',
                'status' => 'active',
                'institution_type' => 'secondary',
                'is_demo_sandbox' => false,
            ]
        );

        $user = User::withoutGlobalScopes()->firstOrNew([
            'email' => mb_strtolower((string) config('demo.email')),
        ]);
        $user->forceFill([
            'school_id' => $master->id,
            'name' => 'Legacy Demo User',
            'username' => (string) config('demo.email'),
            'password' => Hash::make(Str::password(32)),
            'account_status' => User::STATUS_ACTIVE,
            'is_platform_managed' => false,
        ])->save();

        app()->instance('current_tenant', $master);
        request()->setLaravelSession(app('session')->driver());
        request()->headers->set('Host', DemoSandbox::sandboxHost($master));
        request()->session()->put([
            'is_demo_session' => true,
            'demo_sandbox_id' => (int) $master->id,
        ]);
        $this->actingAs($user);

        $response = app(EnsureDemoSandboxAccess::class)->handle(
            request(),
            fn () => response('ok')
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(DemoSandbox::centralUrl('demo/ended'), $response->headers->get('Location'));
        $this->assertNotNull(School::withoutGlobalScopes()->withTrashed()->find($master->id));
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
