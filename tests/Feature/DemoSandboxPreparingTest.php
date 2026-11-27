<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Services\DemoSandboxService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DemoSandboxPreparingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', 'schoolcore_testing');
        config()->set('cache.default', 'array');
        config()->set('queue.default', 'sync');
        config()->set('demo.sandbox.pool_enabled', true);
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

    public function test_public_login_does_not_create_or_mutate_shared_demo_tenant(): void
    {
        Queue::fake();

        $masterSubdomain = (string) config('demo.school.subdomain');
        $masterBefore = School::withoutGlobalScopes()
            ->where('subdomain', $masterSubdomain)
            ->count();
        $demoUserBefore = User::withoutGlobalScopes()
            ->where('email', mb_strtolower((string) config('demo.email')))
            ->count();

        $response = $this->post(route('demo.login.submit'), [
            'email' => config('demo.email'),
            'password' => config('demo.password'),
        ]);

        $response->assertRedirect(route('demo.preparing'));

        $sessionId = $this->app['session.store']->get('demo_pending_session');
        $school = School::withoutGlobalScopes()->where('demo_session_id', $sessionId)->first();

        try {
            $this->assertNotNull($school);
            $this->assertSame($masterBefore, School::withoutGlobalScopes()->where('subdomain', $masterSubdomain)->count());
            $this->assertSame($demoUserBefore, User::withoutGlobalScopes()->where('email', mb_strtolower((string) config('demo.email')))->count());
        } finally {
            if ($school) {
                app(DemoSandboxService::class)->delete((int) $school->id);
            }
        }
    }
}
