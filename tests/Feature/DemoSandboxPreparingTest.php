<?php

namespace Tests\Feature;

use App\Models\School;
use App\Services\DemoSandboxService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoSandboxPreparingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', 'schoolcore_testing');
        config()->set('cache.default', 'array');
        config()->set('queue.default', 'sync');
        config()->set('demo.sandbox.pool_enabled', false);
        DB::purge('mysql');
        DB::reconnect('mysql');

        $this->withoutTenant();
    }

    public function test_login_shows_preparing_screen_then_issues_ready_ticket(): void
    {
        $response = $this->post(route('demo.login.submit'), [
            'email' => config('demo.email'),
            'password' => config('demo.password'),
        ]);

        $response->assertRedirect(route('demo.preparing'));

        $sessionId = $this->app['session.store']->get('demo_pending_session');
        $this->assertIsString($sessionId);

        $school = School::withoutGlobalScopes()->where('demo_session_id', $sessionId)->first();
        $this->assertNotNull($school);

        try {
            $this->get(route('demo.preparing'))->assertOk();

            $status = $this->get(route('demo.preparing.status'));
            $status->assertOk()->assertJson(['status' => 'ready']);

            $url = (string) $status->json('url');
            $this->assertStringContainsString($school->subdomain.'.', $url);
            $this->assertStringContainsString('/demo/consume?ticket=', $url);
            $this->assertNull($this->app['session.store']->get('demo_pending_session'));
        } finally {
            app(DemoSandboxService::class)->delete((int) $school->id);
        }
    }
}
