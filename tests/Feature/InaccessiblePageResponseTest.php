<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Security\RoleCatalogue;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\Admin\Models\CustomRole;
use Tests\TestCase;

/**
 * Someone who follows an old bookmark, or types a URL, after their role has
 * changed should be told plainly that the page is closed to them and how to get
 * help — not shown a framework error page, and certainly not "session expired".
 */
class InaccessiblePageResponseTest extends TestCase
{
    private ?int $userId = null;

    private ?int $roleId = null;

    private ?int $schoolId = null;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.env', 'local');
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    protected function tearDown(): void
    {
        if ($this->roleId) {
            CustomRole::whereKey($this->roleId)->forceDelete();
        }

        if ($this->userId) {
            User::whereKey($this->userId)->forceDelete();
        }

        if ($this->schoolId) {
            School::whereKey($this->schoolId)->forceDelete();
        }

        parent::tearDown();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actingAsWith(array $permissions): void
    {
        $school = School::create([
            'name' => 'Access Denied School',
            'subdomain' => 'access-denied-'.uniqid(),
            'status' => 'active',
        ]);

        $this->schoolId = $school->id;

        $role = CustomRole::create([
            'school_id' => $school->id,
            'name' => 'Access Denied Probe',
            'permissions' => $permissions,
        ]);

        $this->roleId = $role->id;

        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Denied Probe',
            'email' => 'access-denied-test@example.test',
            'password' => bcrypt('secret1234'),
        ]);

        $this->userId = $user->id;

        $user->forceFill([
            'custom_role_id' => $role->id,
            'permissions' => null,
        ])->save();

        $this->actingAs($user);
        $this->actingAsTenant($school);
    }

    public function test_a_page_outside_their_role_is_refused_with_a_readable_explanation(): void
    {
        $this->actingAsWith(RoleCatalogue::permissionsFor('teaching_staff'));

        $response = $this->get('/workspace/invoices');

        $response->assertStatus(403);
        $response->assertSee('Access Denied');
        $response->assertSee(__('This part of the workspace is not available to you.'));
        $response->assertSee(__('Ask a school administrator to review the permissions on your role.'));
    }

    public function test_the_refusal_offers_a_way_back_into_the_workspace(): void
    {
        $this->actingAsWith(RoleCatalogue::permissionsFor('teaching_staff'));

        $response = $this->get('/workspace/payroll-periods');

        $response->assertStatus(403);
        $response->assertSee('href="/workspace"', false);
        $response->assertSee(__('Back to My Workspace'));
    }

    public function test_the_refusal_does_not_name_the_page_that_was_asked_for(): void
    {
        $this->actingAsWith(RoleCatalogue::permissionsFor('teaching_staff'));

        $response = $this->get('/workspace/invoices');

        $response->assertStatus(403);

        // Confirming "Invoices exists, you just can't have it" hands a role a
        // way to map the school by walking URLs. The sidebar already tells each
        // person what they may open; the refusal stays neutral.
        $response->assertDontSee('Invoice');
        $response->assertDontSee('Payroll');
    }

    public function test_a_refusal_does_not_claim_the_session_expired(): void
    {
        $this->actingAsWith(RoleCatalogue::permissionsFor('teaching_staff'));

        $response = $this->get('/workspace/invoices');

        $response->assertStatus(403);
        $response->assertDontSee(__('Page Expired'));
        $response->assertDontSee('419');
    }

    public function test_a_page_they_do_have_access_to_is_unaffected(): void
    {
        $this->actingAsWith(RoleCatalogue::permissionsFor('teaching_staff'));

        $this->get('/workspace/subjects')->assertStatus(200);
    }

    public function test_an_api_caller_gets_a_403_and_not_an_html_page(): void
    {
        $this->actingAsWith(RoleCatalogue::permissionsFor('teaching_staff'));

        $response = $this->getJson('/workspace/invoices');

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Access denied.']);
    }

    public function test_a_refusal_never_leaks_internals_to_an_api_caller(): void
    {
        $this->actingAsWith(RoleCatalogue::permissionsFor('teaching_staff'));

        $response = $this->getJson('/workspace/invoices');

        $body = $response->getContent();

        // A refusal is an ordinary answer, not a malfunction, so it must not
        // hand out file paths, vendor namespaces or a stack trace.
        $this->assertStringNotContainsString('vendor/', $body);
        $this->assertStringNotContainsString('trace', $body);
        $this->assertStringNotContainsString(base_path(), $body);
    }

    /**
     * A policy refusal arrives as an AuthorizationException, which the framework
     * converts to AccessDeniedHttpException. That used to be answered with the
     * "session expired" page and a 419 status, so a model policy quietly telling
     * someone no read as their login having timed out.
     */
    public function test_a_policy_refusal_is_reported_as_forbidden_not_as_an_expired_session(): void
    {
        $this->actingAsWith(RoleCatalogue::permissionsFor('teaching_staff'));

        Route::middleware('web')->get('/policy-denial-probe', function () {
            throw new AuthorizationException('This action is unauthorized.');
        });

        $response = $this->get('/policy-denial-probe');

        $response->assertStatus(403);
        $response->assertDontSee(__('Page Expired'));
        $response->assertDontSee('419');
    }
}
