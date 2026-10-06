<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Term;
use Modules\Admin\Services\SystemRolePresets;
use Tests\TestCase;

/**
 * The app panel is registered without ->domain(), so /workspace resolves on the
 * central platform host as well as on every school subdomain. ResolveTenant
 * deliberately binds nothing there, which left TenantScope with no
 * current_tenant and therefore NO filter at all — one query returning rows
 * from every school at once.
 *
 * Reachable in production: a tenant user signs in with a password at
 * https://kairocore.me/workspace/login, which mints a central-host session
 * cookie, and then reads across tenants.
 *
 * The fix resolves the tenant from the signed-in user's own school instead of
 * leaving the context absent, so the workspace either scopes correctly or is
 * refused. It is never served unscoped.
 */
class CentralHostWorkspaceIsolationTest extends TestCase
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

        Config::set('app.url', 'https://lvh.me');

        $this->schoolA = $this->ensureSchool('host-iso-a', 'Host Iso A');
        $this->schoolB = $this->ensureSchool('host-iso-b', 'Host Iso B');

        // A fresh process-wide container for each test: ResolveTenant binds by
        // mutating the container, so a leftover binding would hide the very
        // bug under test.
        $this->withoutTenant();
    }

    protected function tearDown(): void
    {
        School::withTrashed()
            ->whereIn('id', array_filter($this->createdSchoolIds))
            ->forceDelete();

        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────
    // The hole
    // ─────────────────────────────────────────────────────────────────────

    public function test_a_central_host_workspace_session_cannot_read_another_school(): void
    {
        $user = $this->tenantUser($this->schoolA);

        $termA = $this->makeTerm($this->schoolA, 'A-2026');
        $termB = $this->makeTerm($this->schoolB, 'B-2026');

        $this->actingAs($user);

        // Relative URL => resolved against APP_URL => the CENTRAL host, which
        // is exactly the shape of the vulnerability.
        $this->get('https://lvh.me/workspace')->assertOk();

        // Sanity: the request really did serve a workspace page.
        $this->assertNotNull(app('current_tenant'), 'The workspace ran without a tenant context.');

        $visible = Term::pluck('id')->all();

        $this->assertContains(
            $termA->id,
            $visible,
            'The signed-in user must see their own school.'
        );
        $this->assertNotContains(
            $termB->id,
            $visible,
            'A central-host session must never return another school\'s rows.'
        );
    }

    public function test_the_tenant_resolved_on_the_central_host_is_the_signed_in_users_own_school(): void
    {
        $user = $this->tenantUser($this->schoolB);

        $this->actingAs($user);

        $this->get('https://lvh.me/workspace')->assertOk();

        $this->assertSame(
            (int) $this->schoolB->id,
            (int) app('current_tenant')->id,
            'The context must follow the person signed in, never a neighbouring tenant.'
        );
    }

    public function test_the_scope_is_actually_applied_not_merely_present(): void
    {
        $user = $this->tenantUser($this->schoolA);

        $this->makeTerm($this->schoolA, 'Scoped A');
        $this->makeTerm($this->schoolB, 'Scoped B');

        // Demonstrate the failure mode directly: without a bound tenant the
        // query returns BOTH schools, which is the whole problem.
        $this->withoutTenant();
        $fixtureIds = [$this->schoolA->id, $this->schoolB->id];

        $this->assertSame(
            2,
            Term::withoutTenantScope()->whereIn('school_id', $fixtureIds)->count(),
            'Both fixture terms exist, so an unscoped query would return two.'
        );

        $this->actingAs($user);
        $this->get('https://lvh.me/workspace')->assertOk();

        $this->assertSame(
            1,
            Term::whereIn('school_id', $fixtureIds)->count(),
            'With the tenant resolved, exactly one school\'s rows remain.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // The cases that must still be refused
    // ─────────────────────────────────────────────────────────────────────

    public function test_a_guest_is_sent_to_login_without_a_tenant_context(): void
    {
        $this->get('https://lvh.me/workspace')->assertRedirect();

        $this->assertFalse(
            app()->bound('current_tenant'),
            'A guest must not cause a tenant to be resolved from nothing.'
        );
    }

    public function test_a_platform_administrator_is_refused_the_workspace_on_the_central_host(): void
    {
        $admin = $this->platformAdmin();

        $this->actingAs($admin);

        $response = $this->get('https://lvh.me/workspace');

        $this->assertNotSame(
            200,
            $response->getStatusCode(),
            'A platform administrator has no school to scope to and must not be served a workspace.'
        );
        $this->assertFalse(app()->bound('current_tenant'));
    }

    public function test_the_student_portal_stays_refused_on_the_central_host(): void
    {
        $this->get('https://lvh.me/student/dashboard')->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Regression: real subdomains must be untouched
    // ─────────────────────────────────────────────────────────────────────

    public function test_a_real_tenant_subdomain_still_resolves_its_own_school(): void
    {
        $user = $this->tenantUser($this->schoolA);

        $termA = $this->makeTerm($this->schoolA, 'Sub A');
        $this->makeTerm($this->schoolB, 'Sub B');

        $this->actingAs($user);

        $this->get('https://host-iso-a.lvh.me/workspace')->assertOk();

        $this->assertSame((int) $this->schoolA->id, (int) app('current_tenant')->id);

        $visible = Term::pluck('id')->all();
        $this->assertContains($termA->id, $visible);
        $this->assertCount(1, $visible, 'A subdomain request must be scoped to its own school.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function makeTerm(School $school, string $name): Term
    {
        $year = AcademicYear::withoutGlobalScopes()->firstWhere('school_id', $school->id)
            ?? AcademicYear::withoutGlobalScopes()->create([
                'school_id' => $school->id,
                'name' => 'Year '.$school->subdomain,
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'is_active' => true,
            ]);

        return Term::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => $name,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->startOfYear()->addMonths(3),
        ]);
    }

    private function tenantUser(School $school): User
    {
        $role = SystemRolePresets::roleFor((int) $school->id, 'administrator');

        return User::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'name' => 'Central Host User',
            'email' => 'host-iso-'.uniqid().'@example.test',
            'password' => bcrypt('secret-password'),
            'account_status' => User::STATUS_ACTIVE,
            'custom_role_id' => $role->id,
            'requested_role' => 'administrator',
        ]);
    }

    private function platformAdmin(): User
    {
        return User::withoutGlobalScopes()->create([
            'school_id' => null,
            'name' => 'Central Platform Admin',
            'email' => 'host-iso-admin-'.uniqid().'@example.test',
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
