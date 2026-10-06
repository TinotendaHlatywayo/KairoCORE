<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

/**
 * Covers the Google Single Sign-On hand-off.
 *
 * The OAuth callback necessarily runs on the CENTRAL host (Google redirects
 * there), but tenant sessions are host-only cookies and do not exist on a
 * school subdomain. So a tenant user is handed a single-use ticket and the
 * sign-in is completed on THEIR subdomain.
 *
 * These tests exist because this path shipped broken and untested: the consume
 * route was registered on the central host while the callback redirected to a
 * tenant subdomain, so every tenant Google sign-in 404'd.
 */
class GoogleSsoHandOffTest extends TestCase
{
    private ?School $schoolA = null;

    private ?School $schoolB = null;

    private array $createdSchoolIds = [];

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

        Config::set('services.google.enabled', true);
        Config::set('app.url', 'https://lvh.me');

        $this->schoolA = $this->ensureSchool('sso-test-a', 'SSO Test A');
        $this->schoolB = $this->ensureSchool('sso-test-b', 'SSO Test B');

        Cache::flush();
    }

    protected function tearDown(): void
    {
        School::withTrashed()
            ->whereIn('id', array_filter($this->createdSchoolIds))
            ->forceDelete();

        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────
    // The hand-off itself
    // ─────────────────────────────────────────────────────────────────────

    public function test_the_consume_route_exists_on_the_tenant_subdomain(): void
    {
        $user = $this->tenantUser($this->schoolA);

        $ticket = $this->issueTicket($user);

        // The bug: this 404'd because auth.sso.consume was registered with
        // ->domain(base) while the callback pointed at the tenant subdomain.
        $response = $this->get("https://sso-test-a.lvh.me/auth/sso/consume?ticket={$ticket}");

        $this->assertNotSame(
            404,
            $response->getStatusCode(),
            'The consume route must exist on the school subdomain, not only on the central host.'
        );
    }

    public function test_a_tenant_google_sign_in_completes_inside_their_own_school(): void
    {
        $user = $this->tenantUser($this->schoolA);

        $this->mockGoogleUser($user->email);

        $callback = $this->get('https://lvh.me/auth/google/callback');

        $location = (string) $callback->headers->get('Location');

        $this->assertStringStartsWith(
            'https://sso-test-a.lvh.me/auth/sso/consume',
            $location,
            'A tenant user must be handed to their own subdomain, not bounced back to the central host.'
        );

        $consume = $this->get($location);

        $consume->assertRedirect('https://sso-test-a.lvh.me/workspace');

        $this->assertTrue(Auth::check(), 'The hand-off must leave the tenant signed in.');
        $this->assertSame((int) $user->id, (int) Auth::user()->id);
    }

    public function test_the_central_host_is_left_without_a_session_for_a_tenant_user(): void
    {
        $user = $this->tenantUser($this->schoolA);

        $this->mockGoogleUser($user->email);

        $callback = $this->get('https://lvh.me/auth/google/callback');

        // Regression guard: signing the tenant user in on the central host
        // creates a live session on a host that resolves no tenant, where
        // tenant query scopes are not applied at all.
        $this->assertTrue(
            Auth::guest(),
            'The central host must not hold a session for a tenant user; the ticket IS the hand-off.'
        );

        $this->assertStringStartsWith('https://sso-test-a.lvh.me/', (string) $callback->headers->get('Location'));
    }

    public function test_the_google_identity_is_linked_on_first_sign_in(): void
    {
        $user = $this->tenantUser($this->schoolA);

        $this->assertNull($user->google_id);

        $this->mockGoogleUser($user->email);

        $location = (string) $this->get('https://lvh.me/auth/google/callback')->headers->get('Location');

        $this->get($location);

        $this->assertSame(
            'google-subject-'.sha1($user->email),
            $user->fresh()->google_id,
            'The first successful sign-in must bind the Google identity to the account.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // Ticket integrity
    // ─────────────────────────────────────────────────────────────────────

    public function test_a_ticket_cannot_be_replayed(): void
    {
        $user = $this->tenantUser($this->schoolA);
        $ticket = $this->issueTicket($user);

        // First use must actually succeed, otherwise "the second use was
        // refused" proves nothing.
        $this->get("https://sso-test-a.lvh.me/auth/sso/consume?ticket={$ticket}")
            ->assertRedirect('https://sso-test-a.lvh.me/workspace');

        $this->get("https://sso-test-a.lvh.me/auth/sso/consume?ticket={$ticket}")
            ->assertRedirect('https://sso-test-a.lvh.me/workspace/login')
            ->assertSessionHasErrors('google');
    }

    public function test_a_ticket_for_one_school_cannot_be_redeemed_on_another_subdomain(): void
    {
        $user = $this->tenantUser($this->schoolA);
        $ticket = $this->issueTicket($user);

        // Presented on school B's host, this must be refused even though the
        // ticket is perfectly valid for school A.
        $this->get("https://sso-test-b.lvh.me/auth/sso/consume?ticket={$ticket}")
            ->assertRedirect('https://sso-test-b.lvh.me/workspace/login')
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    /**
     * The ticket names the school it was minted for, independently of the user.
     * Without that binding, transferring a user between schools inside the
     * 60-second window would let a ticket minted for school A open school B on
     * the strength of the user's new school_id alone.
     */
    public function test_a_ticket_is_refused_when_the_user_has_since_moved_school(): void
    {
        $user = $this->tenantUser($this->schoolA);
        $ticket = $this->issueTicket($user);

        // The user is transferred to another school while the ticket is still live.
        $user->forceFill(['school_id' => $this->schoolB->id])->save();

        $this->get("https://sso-test-b.lvh.me/auth/sso/consume?ticket={$ticket}")
            ->assertRedirect('https://sso-test-b.lvh.me/workspace/login')
            ->assertSessionHasErrors('google');

        $this->assertTrue(
            Auth::guest(),
            'A ticket minted for one school must not open another, even for its own former user.'
        );
    }

    public function test_an_expired_ticket_is_refused(): void
    {
        $user = $this->tenantUser($this->schoolA);
        $ticket = $this->issueTicket($user);

        $this->travel(61)->seconds();

        $this->get("https://sso-test-a.lvh.me/auth/sso/consume?ticket={$ticket}")
            ->assertRedirect('https://sso-test-a.lvh.me/workspace/login')
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public function test_an_unknown_ticket_is_refused(): void
    {
        $this->get('https://sso-test-a.lvh.me/auth/sso/consume?ticket=nonsense')
            ->assertRedirect('https://sso-test-a.lvh.me/workspace/login')
            ->assertSessionHasErrors('google');
    }

    public function test_a_pending_user_cannot_complete_a_hand_off(): void
    {
        $user = $this->tenantUser($this->schoolA, User::STATUS_PENDING);
        $ticket = $this->issueTicket($user);

        $this->get("https://sso-test-a.lvh.me/auth/sso/consume?ticket={$ticket}")
            ->assertRedirect('https://sso-test-a.lvh.me/workspace/login')
            ->assertSessionHasErrors('google');

        $this->assertTrue(Auth::guest(), 'An unapproved account must never reach a tenant workspace.');
    }

    public function test_a_suspended_school_cannot_complete_a_hand_off(): void
    {
        $user = $this->tenantUser($this->schoolA);
        $ticket = $this->issueTicket($user);

        $this->schoolA->update(['status' => 'suspended']);

        Cache::flush(); // ResolveTenant caches the subdomain lookup briefly.

        $this->get("https://sso-test-a.lvh.me/auth/sso/consume?ticket={$ticket}")
            ->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Paths that must keep working exactly as they did
    // ─────────────────────────────────────────────────────────────────────

    public function test_a_platform_administrator_still_signs_in_on_the_central_host(): void
    {
        $admin = $this->platformAdmin();

        $this->mockGoogleUser($admin->email);

        $response = $this->get('https://lvh.me/auth/google/callback');

        // Platform admins have no tenant, so they are signed in directly and
        // never handed a ticket.
        $response->assertRedirect('https://lvh.me/platform');
        $this->assertTrue(Auth::check());
        $this->assertSame((int) $admin->id, (int) Auth::user()->id);
    }

    public function test_a_pending_user_is_refused_at_the_callback_with_a_clear_message(): void
    {
        $user = $this->tenantUser($this->schoolA, User::STATUS_PENDING);

        $this->mockGoogleUser($user->email);

        $this->get('https://lvh.me/auth/google/callback')
            ->assertSessionHasErrors('google');

        $this->assertTrue(Auth::guest(), 'An unapproved account must never be signed in.');
    }

    public function test_an_unknown_google_account_is_refused(): void
    {
        $this->mockGoogleUser('nobody@nowhere.example');

        $this->get('https://lvh.me/auth/google/callback')
            ->assertSessionHasErrors('google');

        $this->assertTrue(Auth::guest());
    }

    public function test_google_sign_in_is_absent_when_disabled(): void
    {
        Config::set('services.google.enabled', false);

        $this->get('https://lvh.me/auth/google/redirect')->assertStatus(404);
        $this->get('https://lvh.me/auth/google/callback')->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function issueTicket(User $user): string
    {
        $ticket = 'ticket-'.uniqid();

        Cache::put(
            'sso.ticket:'.hash('sha256', $ticket),
            ['user_id' => $user->id, 'school_id' => (int) $user->school_id, 'remember' => true],
            now()->addSeconds(60),
        );

        return $ticket;
    }

    private function mockGoogleUser(string $email): void
    {
        // The subject id is unique per Google account, so derive it from the
        // address. A fixed id would make every later test match whichever
        // account was linked first (google_id is unique).
        $googleUser = (new SocialiteUser)->setRaw([])->map([
            'id' => 'google-subject-'.sha1($email),
            'nickname' => 'guser',
            'name' => 'Google User',
            'email' => $email,
            'avatar' => 'https://example.test/a.png',
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);
    }

    private function tenantUser(School $school, string $status = User::STATUS_ACTIVE): User
    {
        return User::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'name' => 'Tenant User',
            'email' => 'tenant-'.uniqid().'@example.test',
            'password' => bcrypt('secret-password'),
            'account_status' => $status,
        ]);
    }

    private function platformAdmin(): User
    {
        return User::withoutGlobalScopes()->create([
            'school_id' => null,
            'name' => 'Platform Admin',
            'email' => 'sso-admin-'.uniqid().'@example.test',
            'password' => bcrypt('secret-password'),
            'account_status' => User::STATUS_ACTIVE,
        ]);
    }

    private function ensureSchool(string $subdomain, string $name): School
    {
        $school = School::withTrashed()->where('subdomain', $subdomain)->first();

        if (! $school) {
            $school = School::create(['name' => $name, 'subdomain' => $subdomain, 'status' => 'active']);
        } elseif ($school->trashed()) {
            $school->restore();
            $school->update(['status' => 'active']);
        }

        $this->createdSchoolIds[] = $school->id;

        return $school;
    }
}
