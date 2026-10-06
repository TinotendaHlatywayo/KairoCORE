<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\SchoolResource\Pages\ListSchools;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Admin\Models\CustomRole;
use Modules\SaaS\Models\PlatformAuditLog;
use Modules\SaaS\Services\PlatformImpersonationService;
use Tests\TestCase;

/**
 * Covers the platform -> school hand-off ("Enter school" on /platform/schools).
 *
 * The security properties under test, in order of importance:
 *
 *  1. The ticket is single use and short lived, so a leaked URL is worthless.
 *  2. The ticket is bound to ONE school and cannot be replayed on another
 *     school's subdomain.
 *  3. The actor recorded in the ticket must still be a platform administrator,
 *     so a tenant user can never obtain an entry.
 *  4. The session expires and is destroyed rather than downgraded.
 *  5. Every entry and exit is audited against the platform admin, not the
 *     shadow account.
 */
class PlatformAdminEntersSchoolTest extends TestCase
{
    private ?School $schoolA = null;

    private ?School $schoolB = null;

    private ?User $platformAdmin = null;

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

        // Deterministic regardless of the shell's APP_URL.
        Config::set('platform.base_url', 'https://lvh.me');
        Config::set('platform.session_ttl_minutes', 30);
        Config::set('platform.ticket_ttl_seconds', 60);

        $this->schoolA = $this->ensureSchool('enter-test-a', 'Enter Test A');
        $this->schoolB = $this->ensureSchool('enter-test-b', 'Enter Test B');

        $this->platformAdmin = $this->ensurePlatformAdmin();

        $this->clearImpersonationAudit();

        Cache::flush();
    }

    /**
     * Audit rows are append-only by design, so earlier runs of this file would
     * otherwise make every count assertion fail. Only rows belonging to the two
     * throwaway schools are removed.
     */
    private function clearImpersonationAudit(): void
    {
        $ids = array_map('intval', array_filter([$this->schoolA->id, $this->schoolB->id]));

        PlatformAuditLog::where('action', 'like', 'impersonation.%')
            ->get()
            ->filter(fn ($row) => in_array((int) ($row->payload['school_id'] ?? 0), $ids, true))
            ->each(fn ($row) => $row->delete());
    }

    protected function tearDown(): void
    {
        // Shadow accounts are children of the schools and are cleaned up with
        // them; the platform admin is left alone because audit rows reference
        // it with ON DELETE SET NULL.
        School::withTrashed()
            ->whereIn('id', array_filter($this->createdSchoolIds))
            ->forceDelete();

        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────
    // 1. The happy path
    // ─────────────────────────────────────────────────────────────────────

    public function test_entering_a_school_signs_the_admin_in_as_that_schools_system_administrator(): void
    {
        $response = $this->enterSchool($this->schoolA);

        $response->assertRedirect('https://enter-test-a.lvh.me/workspace');

        $this->assertTrue(Auth::check(), 'The hand-off must leave a real signed-in session.');

        $user = Auth::user();

        $this->assertSame(
            (int) $this->schoolA->id,
            (int) $user->school_id,
            'The entered session must belong to the target school.'
        );
        $this->assertTrue(
            (bool) $user->is_platform_managed,
            'Entry must use the platform-provisioned shadow account.'
        );
        $this->assertSame('System Administrator', $user->name);
    }

    public function test_the_shadow_account_carries_the_schools_administrator_role(): void
    {
        $this->enterSchool($this->schoolA);

        $user = Auth::user();

        $this->assertNotNull($user->custom_role_id, 'The shadow account needs a role to have any access at all.');

        $role = CustomRole::find($user->custom_role_id);

        $this->assertSame('administrator', $role->role_key, 'Entry must grant full administrator reach.');
    }

    public function test_re_entering_reuses_the_same_shadow_account_rather_than_creating_another(): void
    {
        $this->enterSchool($this->schoolA);
        $firstId = (int) Auth::user()->id;

        $this->post('https://lvh.me/platform-exit-probe')->assertStatus(404);

        $this->enterSchool($this->schoolA);

        $this->assertSame($firstId, (int) Auth::user()->id, 'A second entry must not orphan another account.');
        $this->assertSame(
            1,
            User::withoutGlobalScopes()
                ->where('school_id', $this->schoolA->id)
                ->where('is_platform_managed', true)
                ->count()
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // 2. Ticket integrity
    // ─────────────────────────────────────────────────────────────────────

    public function test_a_ticket_cannot_be_replayed(): void
    {
        $ticket = $this->ticketFor($this->schoolA);

        $this->get("https://enter-test-a.lvh.me/platform-entry?ticket={$ticket}")->assertRedirect();

        // Same URL a second time must be worthless.
        $replay = $this->get("https://enter-test-a.lvh.me/platform-entry?ticket={$ticket}");

        $replay->assertRedirect('https://enter-test-a.lvh.me/workspace/login');
        $replay->assertSessionHasErrors('platform');
    }

    public function test_an_expired_ticket_is_rejected(): void
    {
        $ticket = $this->ticketFor($this->schoolA);

        $this->travel(61)->seconds();

        $this->get("https://enter-test-a.lvh.me/platform-entry?ticket={$ticket}")
            ->assertRedirect('https://enter-test-a.lvh.me/workspace/login')
            ->assertSessionHasErrors('platform');

        $this->assertGuest();
    }

    public function test_a_ticket_for_one_school_cannot_be_redeemed_on_another_school_subdomain(): void
    {
        $ticket = $this->ticketFor($this->schoolA);

        // Presented on school B's host, the ticket must be refused even though
        // it is perfectly valid for school A.
        $this->get("https://enter-test-b.lvh.me/platform-entry?ticket={$ticket}")
            ->assertRedirect('https://enter-test-b.lvh.me/workspace/login')
            ->assertSessionHasErrors('platform');

        $this->assertGuest();
    }

    public function test_a_ticket_whose_actor_is_a_tenant_user_is_refused(): void
    {
        // Forge a ticket naming a tenant user as the "platform admin".
        $tenantUser = User::withoutGlobalScopes()->create([
            'school_id' => $this->schoolA->id,
            'name' => 'Not An Admin',
            'email' => 'forged-actor@'.(uniqid()).'.example',
            'password' => bcrypt('secret-password'),
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $token = 'forged-'.uniqid();
        Cache::put(
            'platform_impersonation_ticket:'.hash('sha256', $token),
            [
                'school_id' => $this->schoolA->id,
                'platform_user_id' => $tenantUser->id,
                'issued_at' => now()->toIso8601String(),
            ],
            now()->addSeconds(60),
        );

        $this->get("https://enter-test-a.lvh.me/platform-entry?ticket={$token}")
            ->assertRedirect('https://enter-test-a.lvh.me/workspace/login')
            ->assertSessionHasErrors('platform');

        $this->assertTrue(Auth::guest(), 'A tenant user must never be able to mint an entry.');
    }

    public function test_an_unknown_ticket_is_rejected(): void
    {
        $this->get('https://enter-test-a.lvh.me/platform-entry?ticket=total-nonsense')
            ->assertRedirect('https://enter-test-a.lvh.me/workspace/login')
            ->assertSessionHasErrors('platform');
    }

    // ─────────────────────────────────────────────────────────────────────
    // 3. Session lifetime
    // ─────────────────────────────────────────────────────────────────────

    public function test_the_banner_names_the_school_and_the_original_administrator(): void
    {
        $this->enterSchool($this->schoolA);

        $this->get('https://enter-test-a.lvh.me/workspace/students')
            ->assertOk()
            ->assertSee('System Administrator mode')
            ->assertSee($this->schoolA->name)
            ->assertSee($this->platformAdmin->name);
    }

    public function test_an_ordinary_tenant_session_sees_no_banner(): void
    {
        $teacher = User::withoutGlobalScopes()->create([
            'school_id' => $this->schoolA->id,
            'name' => 'Ordinary Teacher',
            'email' => 'teacher-'.(uniqid()).'@example.com',
            'password' => bcrypt('secret-password'),
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($teacher)
            ->get('https://enter-test-a.lvh.me/workspace/students')
            ->assertOk()
            ->assertDontSee('System Administrator mode');
    }

    public function test_the_session_is_destroyed_once_the_window_closes(): void
    {
        $this->enterSchool($this->schoolA);

        $this->travel(31)->minutes();

        $response = $this->get('https://enter-test-a.lvh.me/workspace');

        $response->assertRedirect('https://lvh.me/platform/schools');

        $this->assertTrue(
            Auth::guest(),
            'An expired hand-off must be logged out, never downgraded to a normal tenant session.'
        );
    }

    public function test_the_expiry_is_recorded_in_the_audit_log(): void
    {
        $this->enterSchool($this->schoolA);

        $this->travel(31)->minutes();
        $this->get('https://enter-test-a.lvh.me/workspace')->assertRedirect();

        $this->assertSame(
            1,
            PlatformAuditLog::where('action', 'impersonation.expired')
                ->where('user_id', $this->platformAdmin->id)
                ->count(),
            'An automatic expiry must be as traceable as a deliberate exit.'
        );
    }

    public function test_leaving_the_school_returns_to_the_platform_and_signs_the_tenant_out(): void
    {
        $this->enterSchool($this->schoolA);

        $response = $this->post('https://enter-test-a.lvh.me/platform-exit');

        $response->assertRedirect('https://lvh.me/platform/schools');

        $this->assertTrue(Auth::guest(), 'Leaving must end the tenant session completely.');
        $this->assertSame(
            1,
            PlatformAuditLog::where('action', 'impersonation.exited')
                ->where('user_id', $this->platformAdmin->id)
                ->count()
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // 4. Audit trail
    // ─────────────────────────────────────────────────────────────────────

    public function test_entering_is_audited_against_the_platform_admin_not_the_shadow_account(): void
    {
        $this->enterSchool($this->schoolA);

        $log = PlatformAuditLog::where('action', 'impersonation.entered')
            ->where('user_id', $this->platformAdmin->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            (int) $this->platformAdmin->id,
            (int) $log->user_id,
            'The audit must name the human who entered, not the shadow account.'
        );
        $this->assertSame((int) $this->schoolA->id, (int) $log->payload['school_id']);
        $this->assertNotNull($log->ip_address);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 5. Housekeeping the school actually sees
    // ─────────────────────────────────────────────────────────────────────

    public function test_the_shadow_account_does_not_inflate_the_schools_admin_count(): void
    {
        $this->enterSchool($this->schoolA);

        $actual = User::withoutGlobalScopes()->where('school_id', $this->schoolA->id)->count();
        $this->assertGreaterThanOrEqual(1, $actual);

        $this->actingAs($this->platformAdmin);

        // SchoolResource lives on the /platform panel; make it current so the
        // table resolves its own routes while rendering.
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        // Exercise the REAL resource column rather than re-implementing the
        // filter here, so this test fails if the exclusion is ever dropped.
        $component = Livewire::test(ListSchools::class);
        $component->assertOk();

        $column = $component->instance()
            ->getTable()
            ->getColumn('users_count');

        $this->assertNotNull($column, 'The Admins column must still exist on the schools table.');

        $counted = $column->applyRelationshipAggregates(
            School::withoutGlobalScopes()->where('id', $this->schoolA->id)
        )->first()->users_count;

        $this->assertSame(
            $actual - 1,
            (int) $counted,
            'The platform account must not be presented as one of the school\'s own admins.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function ticketFor(School $school): string
    {
        return app(PlatformImpersonationService::class)->issueTicket($school, $this->platformAdmin);
    }

    private function enterSchool(School $school)
    {
        return $this->get(
            'https://'.$school->subdomain.'.lvh.me/platform-entry?ticket='.$this->ticketFor($school)
        );
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

    private function ensurePlatformAdmin(): User
    {
        $email = 'platform-admin@'.uniqid().'.example';

        $existing = User::withoutGlobalScopes()
            ->whereNull('school_id')
            ->where('email', $email)
            ->first();

        if ($existing) {
            return $existing;
        }

        return User::withoutGlobalScopes()->create([
            'name' => 'Ada Platform',
            'email' => $email,
            'password' => bcrypt('secret-password'),
            'school_id' => null,
            'account_status' => User::STATUS_ACTIVE,
        ]);
    }
}
