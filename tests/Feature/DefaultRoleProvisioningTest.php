<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Security\RoleCatalogue;
use App\Services\SchoolApprovalService;
use App\Services\UserRegistrationService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Admin\Services\SystemRolePresets;
use Tests\TestCase;

/**
 * Default permissions for every role, checked where they are actually decided.
 *
 * The catalogue is the single answer to "what does this role grant?", so a role
 * row is only correct while it agrees with the catalogue. These tests cover the
 * three ways that can go wrong: a school that never had its defaults created, a
 * role row left behind by an older catalogue, and a role an administrator has
 * deliberately tailored. The last one matters most — a defaults refresh that
 * overwrote real work would make the roles screen unusable.
 */
class DefaultRoleProvisioningTest extends TestCase
{
    private ?School $school = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useMysql();

        $school = School::create([
            'name' => 'Provisioning Primary',
            'subdomain' => 'provisioning-'.uniqid(),
            'status' => 'active',
        ]);

        $this->school = $school;
        $this->actingAsTenant($school);
    }

    protected function tearDown(): void
    {
        if ($this->school) {
            CustomRole::withoutTenantScope()->where('school_id', $this->school->id)->delete();
            User::where('school_id', $this->school->id)->delete();
            $this->school->delete();
        }

        parent::tearDown();
    }

    public function test_a_new_school_receives_every_catalogue_role_with_exactly_the_catalogue_permissions(): void
    {
        $result = SystemRolePresets::provisionForSchool($this->school);

        $this->assertCount(count(RoleCatalogue::keys()), $result['created']);

        foreach (RoleCatalogue::keys() as $key) {
            $role = CustomRole::withoutTenantScope()
                ->where('school_id', $this->school->id)
                ->where('role_key', $key)
                ->first();

            $this->assertNotNull($role, "{$key} was not provisioned");
            $this->assertSame(
                RoleCatalogue::permissionsFor($key),
                $role->permissions,
                "{$key} does not grant what the catalogue says",
            );
            $this->assertTrue($role->is_system);
            $this->assertFalse($role->hasCustomisedPermissions());
        }
    }

    public function test_the_teaching_staff_role_can_produce_report_cards_but_not_publish_them(): void
    {
        SystemRolePresets::provisionForSchool($this->school);

        $teacher = $this->role('teaching_staff')->permissions;

        // Teachers write the report cards; publishing them to the student
        // portal is a separate office, so every key on that page stays out.
        $this->assertContains('exams.report_cards.create', $teacher);
        $this->assertContains('exams.reports_academic_publishing.run', $teacher);
        $this->assertSame(
            [],
            array_values(array_filter(
                $teacher,
                fn (string $key): bool => str_starts_with($key, 'exams.publish_to_student_portal.')
            )),
            'Teaching Staff must not publish to the student portal',
        );
    }

    public function test_procurement_runs_purchasing_and_stock(): void
    {
        SystemRolePresets::provisionForSchool($this->school);

        $procurement = $this->role('procurement')->permissions;

        $this->assertContains('inventory.view_module', $procurement);
        $this->assertContains('inventory.procurement.run', $procurement);
        $this->assertContains('inventory.procurement.approve', $procurement);
        $this->assertContains('inventory.stock_inventory.run', $procurement);

        // It no longer looks after assessment publishing: that was attached to
        // the job, and procurement's job is now purchasing and stock.
        $this->assertSame(
            [],
            array_values(array_filter(
                $procurement,
                fn (string $key): bool => str_starts_with($key, 'exams.')
            )),
            'Procurement must not hold any Exams & Grading permission',
        );
        $this->assertSame(
            [],
            array_values(array_filter(
                $procurement,
                fn (string $key): bool => str_starts_with($key, 'website.')
            )),
            'Procurement must not hold any Website permission',
        );
    }

    public function test_librarian_runs_the_library_and_not_the_website(): void
    {
        SystemRolePresets::provisionForSchool($this->school);

        $librarian = $this->role('librarian')->permissions;

        $this->assertContains('library.view_module', $librarian);
        $this->assertContains('library.catalogue.view', $librarian);
        $this->assertContains('library.circulation.issue', $librarian);
        $this->assertContains('library.circulation.return', $librarian);

        $this->assertSame(
            [],
            array_values(array_filter(
                $librarian,
                fn (string $key): bool => str_starts_with($key, 'website.')
            )),
            'The public website belongs to the administrators, not the librarian',
        );
    }

    public function test_the_public_website_belongs_to_both_administrators(): void
    {
        SystemRolePresets::provisionForSchool($this->school);

        $systemAdmin = $this->role('administrator')->permissions;
        $this->assertSame([PermissionRegistry::WILDCARD], $systemAdmin);

        $schoolAdmin = $this->role('school_administrator')->permissions;
        $this->assertContains('website.templates_design.view', $schoolAdmin);
        $this->assertContains('website.templates_design.configure', $schoolAdmin);
        $this->assertContains('website.content_manager.publish', $schoolAdmin);
    }

    public function test_the_administrator_role_is_named_system_administrator(): void
    {
        SystemRolePresets::provisionForSchool($this->school);

        $role = $this->role('administrator');

        $this->assertSame('System Administrator', $role->name);
        $this->assertSame(
            'System Administrator',
            RoleCatalogue::label('administrator'),
        );
    }

    public function test_a_role_left_behind_by_an_older_catalogue_is_refreshed(): void
    {
        // A role row as an earlier release wrote it: system-flagged, carrying a
        // catalogue name, but holding the old hand-written bundle of legacy
        // keys and none of the pages the catalogue now describes.
        $stale = CustomRole::create([
            'school_id' => $this->school->id,
            'name' => 'Teaching Staff',
            'permissions' => ['exams.enter_marks', 'reports.generate', 'attendance.record'],
            'is_system' => true,
        ]);

        $result = SystemRolePresets::provisionForSchool($this->school);

        $this->assertContains('teaching_staff', $result['refreshed']);

        $stale->refresh();

        $this->assertSame(RoleCatalogue::permissionsFor('teaching_staff'), $stale->permissions);
        $this->assertSame('teaching_staff', $stale->role_key, 'the legacy row should be adopted, not duplicated');
        $this->assertFalse($stale->hasCustomisedPermissions());

        // The whole point: the role is refreshed in place, so the accounts
        // already assigned to it are corrected without being reassigned.
        $this->assertSame(1, CustomRole::withoutTenantScope()
            ->where('school_id', $this->school->id)
            ->where('role_key', 'teaching_staff')
            ->count());
    }

    public function test_a_role_an_administrator_tailored_is_never_overwritten(): void
    {
        SystemRolePresets::provisionForSchool($this->school);

        $narrowed = $this->role('librarian');
        $narrowed->permissions = ['website.view_module', 'website.preview'];
        $narrowed->save();

        $narrowed->refresh();
        $this->assertTrue($narrowed->hasCustomisedPermissions(), 'an edit should be recorded as a tailoring');

        $result = SystemRolePresets::provisionForSchool($this->school);

        $this->assertContains('librarian', $result['customised']);
        $this->assertNotContains('librarian', $result['refreshed']);

        $this->assertSame(['website.view_module', 'website.preview'], $narrowed->refresh()->permissions);
    }

    public function test_restore_defaults_is_the_explicit_way_back(): void
    {
        SystemRolePresets::provisionForSchool($this->school);

        $narrowed = $this->role('hr');
        $narrowed->permissions = ['hr.view_module'];
        $narrowed->save();

        SystemRolePresets::restoreDefaults($this->school->id);

        $narrowed->refresh();

        $this->assertSame(RoleCatalogue::permissionsFor('hr'), $narrowed->permissions);
        $this->assertFalse($narrowed->hasCustomisedPermissions());
    }

    public function test_a_role_an_administrator_created_is_never_claimed_even_with_our_name(): void
    {
        $handmade = CustomRole::create([
            'school_id' => $this->school->id,
            'name' => 'Teaching Staff',
            'description' => 'Only the two senior teachers, nothing else.',
            'permissions' => ['academics.timetables.view'],
            'is_system' => false,
        ]);

        SystemRolePresets::provisionForSchool($this->school);

        $handmade->refresh();

        $this->assertNull($handmade->role_key, 'an administrator\'s role must not be adopted');
        $this->assertSame(['academics.timetables.view'], $handmade->permissions);

        // Names are unique per school, so the default takes a marked name
        // rather than failing to be created at all — a missing default would
        // otherwise leave the approver with nothing but the fallback role.
        $default = $this->role('teaching_staff');

        $this->assertSame('Teaching Staff (Default)', $default->name);
        $this->assertSame(RoleCatalogue::permissionsFor('teaching_staff'), $default->permissions);
    }

    public function test_a_school_with_a_free_catalogue_label_gets_the_plain_name(): void
    {
        SystemRolePresets::provisionForSchool($this->school);

        $this->assertSame('Teaching Staff', $this->role('teaching_staff')->name);
    }

    public function test_provisioning_twice_changes_nothing(): void
    {
        SystemRolePresets::provisionForSchool($this->school);
        $before = $this->role('teaching_staff')->permissions;

        $second = SystemRolePresets::provisionForSchool($this->school);

        $this->assertSame([], $second['created']);
        $this->assertSame([], $second['refreshed']);
        $this->assertSame([], $second['customised']);
        $this->assertSame($before, $this->role('teaching_staff')->permissions);
    }

    public function test_approval_hands_the_account_the_current_defaults_not_a_stale_role(): void
    {
        $stale = CustomRole::create([
            'school_id' => $this->school->id,
            'name' => 'Accounts / Finance',
            'permissions' => ['finance.receive_payments'],
            'is_system' => true,
        ]);

        $applicant = User::create([
            'school_id' => $this->school->id,
            'name' => 'Bursar',
            'email' => 'bursar@provisioning.test',
            'password' => Hash::make('Password@1'),
            'requested_role' => 'accounts_finance',
            'account_status' => User::STATUS_PENDING,
        ]);

        (new UserRegistrationService)->approve($applicant);

        $assigned = $applicant->refresh()->customRole;

        $this->assertNotNull($assigned);
        $this->assertSame($stale->id, $assigned->id, 'the existing role should be reused, not duplicated');
        $this->assertSame(RoleCatalogue::permissionsFor('accounts_finance'), $assigned->permissions);
    }

    public function test_approval_creates_a_role_the_school_never_had(): void
    {
        $applicant = User::create([
            'school_id' => $this->school->id,
            'name' => 'Librarian',
            'email' => 'librarian@provisioning.test',
            'password' => Hash::make('Password@1'),
            'requested_role' => 'librarian',
            'account_status' => User::STATUS_PENDING,
        ]);

        (new UserRegistrationService)->approve($applicant);

        $assigned = $applicant->refresh()->customRole;

        $this->assertSame('librarian', $assigned->role_key);
        $this->assertSame(RoleCatalogue::permissionsFor('librarian'), $assigned->permissions);
    }

    public function test_the_founder_is_auto_provisioned_with_the_wildcard(): void
    {
        $founder = User::create([
            'school_id' => $this->school->id,
            'name' => 'Founder',
            'email' => 'founder@provisioning.test',
            'password' => Hash::make('Password@1'),
            'requested_role' => 'administrator',
            'account_status' => User::STATUS_PENDING,
        ]);

        $this->assertTrue(PermissionRegistry::ensureAdminHasRole($founder, $this->school->id));

        $assigned = $founder->refresh()->customRole;

        $this->assertSame('administrator', $assigned->role_key);
        $this->assertSame([PermissionRegistry::WILDCARD], $assigned->permissions);
    }

    public function test_the_administrator_role_cannot_be_narrowed_away(): void
    {
        SystemRolePresets::provisionForSchool($this->school);

        $admin = $this->role('administrator');
        $admin->permissions = ['finance.view_module'];
        $admin->save();

        $this->assertTrue($admin->refresh()->hasCustomisedPermissions());

        // The one role a school cannot tailor: the founder signs in through it,
        // so a narrowed role would lock them out of the screen that fixes it.
        SystemRolePresets::provisionForSchool($this->school);

        $this->assertSame([PermissionRegistry::WILDCARD], $admin->refresh()->permissions);
    }

    public function test_an_administrator_whose_role_was_narrowed_is_restored_on_sign_in(): void
    {
        $founder = User::create([
            'school_id' => $this->school->id,
            'name' => 'Founder',
            'email' => 'founder@provisioning.test',
            'password' => Hash::make('Password@1'),
            'requested_role' => 'administrator',
            'account_status' => User::STATUS_ACTIVE,
        ]);

        PermissionRegistry::ensureAdminHasRole($founder, $this->school->id);

        $role = $founder->refresh()->customRole;
        $role->permissions = ['finance.view_module'];
        $role->save();

        $this->assertFalse(
            PermissionRegistry::roleHasUnrestrictedAccess($role->refresh()) && $role->permissions === ['*'],
            'the edit should have taken effect for the moment',
        );

        PermissionRegistry::ensureAdminHasRole($founder->refresh(), $this->school->id);

        $this->assertSame([PermissionRegistry::WILDCARD], $role->refresh()->permissions);
    }

    public function test_approving_a_school_provisions_its_default_roles(): void
    {
        $this->assertSame(0, CustomRole::withoutTenantScope()
            ->where('school_id', $this->school->id)
            ->count());

        app(SchoolApprovalService::class)->approve($this->school);

        $keys = CustomRole::withoutTenantScope()
            ->where('school_id', $this->school->id)
            ->whereNotNull('role_key')
            ->pluck('role_key')
            ->all();

        foreach (RoleCatalogue::keys() as $key) {
            $this->assertContains($key, $keys, "{$key} missing after approval");
        }
    }

    public function test_cloning_a_catalogue_role_produces_an_independent_administrator_role(): void
    {
        SystemRolePresets::provisionForSchool($this->school);

        $clone = $this->role('librarian')->replicateForClone();

        $this->assertNull($clone->role_key, 'a clone must not answer to the catalogue');
        $this->assertFalse($clone->is_system);
        $this->assertTrue($clone->hasCustomisedPermissions());

        // Provisioning afterwards must leave the copy alone: it is now an
        // administrator-owned role, whatever it is called.
        $clone->permissions = ['website.preview'];
        $clone->save();

        SystemRolePresets::provisionForSchool($this->school);

        $this->assertSame(['website.preview'], $clone->refresh()->permissions);
    }

    public function test_the_sync_command_reports_and_repairs_a_school(): void
    {
        CustomRole::create([
            'school_id' => $this->school->id,
            'name' => 'Health',
            'permissions' => ['clinic.view_module'],
            'is_system' => true,
        ]);

        $this->artisan('schoolcore:sync-catalogue-roles', ['school' => $this->school->id])
            ->expectsOutputToContain('refreshed')
            ->assertSuccessful();

        $this->assertSame(RoleCatalogue::permissionsFor('health'), $this->role('health')->permissions);
    }

    public function test_the_sync_command_fails_loudly_for_an_unknown_school(): void
    {
        $this->artisan('schoolcore:sync-catalogue-roles', ['school' => 99999999])
            ->expectsOutputToContain('No schools found')
            ->assertFailed();
    }

    /**
     * This suite writes real rows (roles are read through withoutTenantScope
     * from several places), so it runs against the same MySQL database the
     * tenant tests use rather than an in-memory SQLite file.
     */
    private function useMysql(): void
    {
        Config::set('app.env', 'local');
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    private function role(string $roleKey): CustomRole
    {
        return CustomRole::withoutTenantScope()
            ->where('school_id', $this->school->id)
            ->where('role_key', $roleKey)
            ->firstOrFail();
    }
}
