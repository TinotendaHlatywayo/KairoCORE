<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureDemoSandboxAccess;
use App\Models\School;
use App\Models\User;
use App\Security\DemoPersona;
use App\Services\DemoAccountService;
use App\Support\DemoSandbox;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DemoSandboxTicketTest extends TestCase
{
    use DatabaseTransactions;

    protected School $school;

    protected User $user;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql.database', 'schoolcore_testing');
        $app['config']->set('cache.default', 'array');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $token = strtolower(Str::random(12));
        $this->school = School::withoutGlobalScopes()->create([
            'name' => 'Ticket Sandbox',
            'subdomain' => DemoSandbox::subdomain($token),
            'login_code' => 'TKT-'.strtoupper(Str::random(12)),
            'status' => 'active',
            'institution_type' => 'secondary',
            'is_demo_sandbox' => true,
            'expires_at' => now()->addHour(),
            'hard_expires_at' => now()->addHours(8),
            'pool_status' => 'claimed',
        ]);

        $this->user = User::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => 'Demo Ticket User',
            'email' => DemoSandbox::email($token),
            'username' => DemoSandbox::email($token),
            'password' => Hash::make(Str::password(32)),
            'account_status' => User::STATUS_ACTIVE,
            'requested_role' => 'administrator',
            'is_platform_managed' => true,
        ]);

        $this->actingAsTenant($this->school);
        request()->headers->set('Host', DemoSandbox::sandboxHost($this->school));
        request()->setLaravelSession(app('session')->driver());
    }

    public function test_demo_and_sandbox_emails_are_blocked_from_normal_login(): void
    {
        $this->assertTrue(DemoSandbox::loginEmailIsBlocked((string) config('demo.email')));
        $this->assertTrue(DemoSandbox::loginEmailIsBlocked($this->user->email));
        $this->assertFalse(DemoSandbox::loginEmailIsBlocked('real@school.example'));
    }

    public function test_ticket_is_host_bound_and_single_use(): void
    {
        $service = app(DemoAccountService::class);
        $ticket = $service->issueTicket($this->user);

        $this->assertSame($this->user->id, $service->redeemTicket($ticket)?->id);
        $this->assertNull($service->redeemTicket($ticket));
    }

    public function test_ticket_from_another_host_is_rejected(): void
    {
        $service = app(DemoAccountService::class);
        $ticket = $service->issueTicket($this->user);

        request()->headers->set('Host', parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'kairo.co.zw');

        $this->assertNull($service->redeemTicket($ticket));
    }

    public function test_sign_in_sets_sandbox_session_flags(): void
    {
        app(DemoAccountService::class)->signIn($this->user);

        $this->assertTrue(Auth::check());
        $this->assertTrue(session('is_demo_session'));
        $this->assertSame((int) $this->school->id, (int) session('demo_sandbox_id'));
        $this->assertSame('administrator', DemoPersona::roleKey());
    }

    public function test_expired_sandbox_redirects_to_central_ended_page(): void
    {
        app(DemoAccountService::class)->signIn($this->user);
        $this->school->forceFill(['expires_at' => now()->subMinute()])->saveQuietly();

        $request = request();
        $response = app(EnsureDemoSandboxAccess::class)->handle($request, fn () => response('ok'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(DemoSandbox::centralUrl('demo/ended'), $response->headers->get('Location'));
    }
}
