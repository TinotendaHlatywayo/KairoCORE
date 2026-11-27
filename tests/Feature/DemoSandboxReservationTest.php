<?php

namespace Tests\Feature;

use App\Exceptions\DemoBusyException;
use App\Models\School;
use App\Services\DemoSandboxService;
use App\Support\DemoSandbox;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Tests\TestCase;

class DemoSandboxReservationTest extends TestCase
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

        Queue::fake();
        $this->withoutTenant();
    }

    public function test_sandbox_tokens_are_short_lowercase_alphanumeric(): void
    {
        foreach ([12, 14, 16] as $length) {
            $token = DemoSandbox::token($length);

            $this->assertMatchesRegularExpression('/^[a-z0-9]{'.$length.'}$/', $token);
            $this->assertStringStartsWith('demo-', DemoSandbox::subdomain($token));
            $this->assertStringEndsWith('@'.DemoSandbox::EMAIL_DOMAIN, DemoSandbox::email($token));
        }
    }

    public function test_demo_prefix_and_internal_names_are_reserved(): void
    {
        $this->assertTrue(DemoSandbox::isReservedSubdomain('demo-abc123xyz'));
        $this->assertTrue(DemoSandbox::isReservedSubdomain('DEMO_ABC123XYZ'));
        $this->assertTrue(DemoSandbox::isReservedSubdomain('demo'));
        $this->assertTrue(DemoSandbox::isReservedSubdomain('platform-site'));
        $this->assertFalse(DemoSandbox::isReservedSubdomain('acme-high-school'));
    }

    public function test_database_has_unique_indexes_for_subdomain_login_code_and_session(): void
    {
        $indexes = DB::select(
            'SHOW INDEX FROM schools WHERE Key_name IN (?, ?, ?)',
            ['schools_subdomain_unique', 'schools_login_code_unique', 'schools_demo_session_id_unique']
        );

        $names = collect($indexes)->pluck('Key_name')->unique()->all();

        $this->assertContains('schools_subdomain_unique', $names);
        $this->assertContains('schools_login_code_unique', $names);
        $this->assertContains('schools_demo_session_id_unique', $names);
    }

    public function test_normal_schools_cannot_use_reserved_subdomains_but_sandboxes_can(): void
    {
        $token = DemoSandbox::token();

        try {
            School::withoutGlobalScopes()->create([
                'name' => 'Reserved School',
                'subdomain' => DemoSandbox::subdomain($token),
                'login_code' => 'RES-'.strtoupper($token),
                'status' => 'active',
                'institution_type' => 'secondary',
            ]);
            $this->fail('A normal school with a demo- prefix should be rejected.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        $sandbox = School::withoutGlobalScopes()->create([
            'name' => 'Sandbox School',
            'subdomain' => DemoSandbox::subdomain($token),
            'login_code' => 'SAN-'.strtoupper($token),
            'status' => 'active',
            'institution_type' => 'secondary',
            'is_demo_sandbox' => true,
        ]);

        $this->assertTrue($sandbox->is_demo_sandbox);
    }

    public function test_claim_reuses_a_ready_pooled_sandbox(): void
    {
        config()->set('demo.sandbox.pool_enabled', true);
        DemoSandboxService::$lastSeedDurationMs = null;

        $token = DemoSandbox::token();
        $pooled = School::withoutGlobalScopes()->create([
            'name' => 'Ready Sandbox',
            'subdomain' => DemoSandbox::subdomain($token),
            'login_code' => 'POOL-'.strtoupper($token),
            'status' => 'active',
            'institution_type' => 'secondary',
            'is_demo_sandbox' => true,
            'pool_status' => 'ready',
            'expires_at' => now()->addHour(),
            'hard_expires_at' => now()->addHours(8),
        ]);

        $claim = app(DemoSandboxService::class)->claim();

        $this->assertSame((int) $pooled->id, (int) $claim['school']->id);
        $this->assertSame('claimed', $claim['school']->pool_status);
        $this->assertNull(DemoSandboxService::$lastSeedDurationMs);
    }

    public function test_global_cap_returns_busy_exception(): void
    {
        config()->set('demo.sandbox.max_concurrent', 0);

        $this->expectException(DemoBusyException::class);

        app(DemoSandboxService::class)->claim();
    }

    public function test_sandbox_schools_are_hidden_from_platform_queries_only(): void
    {
        $suffix = strtolower(DemoSandbox::token());

        $sandbox = School::withoutGlobalScopes()->create([
            'name' => 'Sandbox Hidden',
            'subdomain' => DemoSandbox::subdomain($suffix),
            'login_code' => 'HID-'.strtoupper($suffix),
            'status' => 'active',
            'institution_type' => 'secondary',
            'is_demo_sandbox' => true,
        ]);

        $real = School::withoutGlobalScopes()->create([
            'name' => 'Real School Visible',
            'subdomain' => 'real-'.$suffix,
            'login_code' => 'VIS-'.strtoupper($suffix),
            'status' => 'active',
            'institution_type' => 'secondary',
        ]);

        $this->assertFalse(School::query()->whereKey($sandbox->id)->exists());
        $this->assertTrue(School::query()->whereKey($real->id)->exists());

        $this->actingAsTenant($sandbox);
        $this->assertTrue(School::query()->whereKey($sandbox->id)->exists());
    }
}
