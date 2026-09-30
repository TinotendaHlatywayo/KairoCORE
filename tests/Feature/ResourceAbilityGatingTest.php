<?php

namespace Tests\Feature;

use App\Filament\App\Resources\AssessmentTypeResource;
use App\Filament\App\Resources\InvoiceResource;
use App\Filament\App\Resources\SubjectResource;
use App\Models\School;
use App\Models\User;
use App\Security\RoleCatalogue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\CustomRole;
use Tests\TestCase;

/**
 * Filament decides whether to offer Create, Edit, Delete, Duplicate and Restore
 * by asking the resource, so gating that one answer is what gates every button
 * in a table, a header, or on a page. These tests pin that down against real
 * roles, including the case where a page is reached but only partly.
 */
class ResourceAbilityGatingTest extends TestCase
{
    private ?int $userId = null;

    private ?int $roleId = null;

    /**
     * The suite defaults to SQLite in memory, which this environment has no
     * driver for. These tests need real rows — a user, a role, a tenant — so they
     * run against the MySQL the rest of the suite's tenant tests use.
     */
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

    private ?int $schoolId = null;

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
     * Give the given permissions to a real user and sign them in, so the answers
     * below come from the same place a live request reads them.
     *
     * @param  array<int, string>  $permissions
     */
    private function actingAsWith(array $permissions): void
    {
        // A dedicated tenant, so these tests never touch a real school's roles.
        $school = School::create([
            'name' => 'Ability Gate School',
            'subdomain' => 'ability-gate-'.uniqid(),
            'status' => 'active',
        ]);

        // Gating is a property of the signed-in user, so no employee record is
        // needed: one is left out rather than half-populated.
        $this->schoolId = $school->id;

        $role = CustomRole::create([
            'school_id' => $school->id,
            'name' => 'Ability Gate Probe',
            'slug' => 'ability-gate-probe-'.uniqid(),
            'permissions' => $permissions,
        ]);

        $this->roleId = $role->id;

        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Ability Gate',
            'email' => 'ability-gate-test@example.test',
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

    public function test_reading_a_page_does_not_imply_changing_or_deleting_it(): void
    {
        $this->actingAsWith(['academics.subjects.view', 'academics.subjects.export']);

        $this->assertTrue(SubjectResource::can('viewAny'));
        $this->assertTrue(SubjectResource::can('view'));

        $this->assertFalse(SubjectResource::can('create'));
        $this->assertFalse(SubjectResource::can('update'));
        $this->assertFalse(SubjectResource::can('delete'));
        $this->assertFalse(SubjectResource::can('deleteAny'));
        $this->assertFalse(SubjectResource::can('replicate'));
        $this->assertFalse(SubjectResource::can('restore'));
    }

    public function test_being_allowed_to_add_something_does_not_imply_being_allowed_to_remove_it(): void
    {
        $this->actingAsWith(['academics.subjects.view', 'academics.subjects.create', 'academics.subjects.edit']);

        $this->assertTrue(SubjectResource::can('create'));
        $this->assertTrue(SubjectResource::can('update'));
        $this->assertFalse(SubjectResource::can('delete'));
        $this->assertFalse(SubjectResource::can('deleteAny'));
    }

    public function test_a_page_outside_a_role_is_refused_outright(): void
    {
        $this->actingAsWith(RoleCatalogue::permissionsFor('teaching_staff'));

        $this->assertTrue(SubjectResource::can('viewAny'));

        foreach (['viewAny', 'view', 'create', 'update', 'delete', 'deleteAny'] as $ability) {
            $this->assertFalse(
                InvoiceResource::can($ability),
                "A teacher should not be able to {$ability} an invoice",
            );
        }
    }

    public function test_a_role_with_the_module_can_do_the_usual_crud_set(): void
    {
        $this->actingAsWith(RoleCatalogue::permissionsFor('accounts_finance'));

        $this->assertTrue(InvoiceResource::can('viewAny'));
        $this->assertTrue(InvoiceResource::can('create'));
        $this->assertTrue(InvoiceResource::can('update'));
        $this->assertTrue(InvoiceResource::can('delete'));
        $this->assertTrue(InvoiceResource::can('deleteAny'));
    }

    public function test_bulk_delete_follows_the_delete_grant(): void
    {
        // The catalogue offers one 'delete' capability per page, covering both
        // removing one record and selecting many to remove at once. Someone who
        // may delete a subject may as well delete a hundred in one action.
        $this->actingAsWith(['academics.subjects.view', 'academics.subjects.delete']);

        $this->assertTrue(SubjectResource::can('delete'));
        $this->assertTrue(SubjectResource::can('deleteAny'));
        $this->assertTrue(SubjectResource::can('forceDelete'));
        $this->assertTrue(SubjectResource::can('forceDeleteAny'));
    }

    public function test_bulk_delete_is_closed_when_deleting_is(): void
    {
        $this->actingAsWith(['academics.subjects.view', 'academics.subjects.create']);

        $this->assertTrue(SubjectResource::can('create'));
        $this->assertFalse(SubjectResource::can('delete'));
        $this->assertFalse(SubjectResource::can('deleteAny'));
    }

    public function test_a_duplicate_is_treated_as_a_creation(): void
    {
        $this->actingAsWith(['exams.assessment_types.view', 'exams.assessment_types.create']);

        $this->assertTrue(AssessmentTypeResource::can('replicate'));
        $this->assertFalse(AssessmentTypeResource::can('update'));
    }

    public function test_a_restore_is_treated_as_an_edit(): void
    {
        $this->actingAsWith(['academics.subjects.view', 'academics.subjects.edit']);

        $this->assertTrue(SubjectResource::can('restore'));
        $this->assertFalse(SubjectResource::can('delete'));
    }

    public function test_the_wildcard_covers_every_ability(): void
    {
        $this->actingAsWith(['*']);

        foreach (['viewAny', 'view', 'create', 'update', 'delete', 'deleteAny', 'replicate', 'restore'] as $ability) {
            $this->assertTrue(SubjectResource::can($ability), "A wildcard failed to cover {$ability}");
            $this->assertTrue(InvoiceResource::can($ability), "A wildcard failed to cover {$ability} on another module");
        }
    }

    public function test_a_page_whose_permission_is_removed_stops_being_reachable_the_moment_it_is(): void
    {
        $this->actingAsWith(['academics.subjects.view', 'academics.subjects.edit']);

        $this->assertTrue(SubjectResource::can('update'));

        $role = CustomRole::findOrFail($this->roleId);
        $role->permissions = ['academics.subjects.view'];
        $role->save();

        $this->assertTrue(SubjectResource::can('view'));
        $this->assertFalse(SubjectResource::can('update'));
    }
}
