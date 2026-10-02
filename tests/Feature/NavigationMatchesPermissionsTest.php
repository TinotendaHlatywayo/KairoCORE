<?php

namespace Tests\Feature;

use App\Filament\App\Pages\Academic\SetupStructureHub;
use App\Models\School;
use App\Models\User;
use App\Services\ModuleVisibilityManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Admin\Services\SystemRolePresets;
use Tests\TestCase;

/**
 * What a person is offered, and what they are then able to do, must be the same
 * question asked twice.
 *
 * The failure this guards against is a page that appears in a menu and then
 * answers 403 when it is opened. For someone building a workspace around their
 * staff that is the difference between software that fits them and software
 * that is merely switched on — it tells the person they have been classified
 * wrongly rather than told what they may do.
 *
 * It also covers the two places that decide what is offered: the sidebar, and
 * the dashboard's quick-launch cards. The dashboard used to check only whether
 * the school had a module switched on, which says nothing about the person.
 */
class NavigationMatchesPermissionsTest extends TestCase
{
    private $ids = [];

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
        foreach ($this->ids as $model) {
            $model::whereKey($model->getKey())->forceDelete();
        }

        parent::tearDown();
    }

    private function school(): School
    {
        $school = School::create([
            'name' => 'Nav '.uniqid(),
            'subdomain' => 'nav'.uniqid(),
            'status' => 'active',
        ]);
        $this->ids[] = $school;

        return $school;
    }

    private function userFor(string $roleKey): User
    {
        return $this->userIn($this->school(), $roleKey);
    }

    private function userIn(School $school, string $roleKey): User
    {
        $role = SystemRolePresets::roleFor($school->id, $roleKey);

        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Nav '.uniqid(),
            'email' => uniqid().'@nav.test',
            'password' => bcrypt('secret1234'),
        ]);
        $this->ids[] = $user;

        $user->forceFill([
            'custom_role_id' => $role->id,
            'requested_role' => $roleKey,
            'account_status' => User::STATUS_ACTIVE,
        ])->save();

        $this->actingAs($user);
        $this->actingAsTenant($school);

        return $user;
    }

    /**
     * Every link the panel renders, as paths.
     *
     * @return array<int, string>
     */
    private function offeredWorkspaceLinks(): array
    {
        $html = $this->get('/workspace')->getContent();

        preg_match_all('#href="(/workspace[^"]*)"#', $html, $matches);

        return array_values(array_unique(
            array_map(fn ($href) => rtrim(parse_url($href, PHP_URL_PATH) ?? $href, '/'), $matches[1])
        ));
    }

    public function test_a_teacher_is_offered_no_link_that_refuses_them(): void
    {
        $this->userFor('teaching_staff');

        foreach ($this->offeredWorkspaceLinks() as $path) {
            if ($path === '/workspace/logout') {
                continue;
            }

            $status = $this->get($path)->getStatusCode();

            $this->assertNotSame(
                403,
                $status,
                "The panel offers {$path}, which answers 403. A link should never be a dead end."
            );
        }
    }

    public function test_the_dashboard_does_not_offer_cards_the_person_cannot_open(): void
    {
        $teacher = $this->userFor('teaching_staff');

        // A teacher has neither the student directory nor online admissions.
        $this->assertFalse(PermissionRegistry::userCan($teacher, 'students.students.view'));
        $this->assertFalse(PermissionRegistry::userCan($teacher, 'admissions.applications.view'));

        $html = $this->get('/workspace')->getContent();

        // These cards used to be gated on the tenant's module switch, which
        // says the school owns the module — not that this person may open it.
        $this->assertStringNotContainsString('/workspace/students', $html);
        $this->assertStringNotContainsString('/workspace/applications', $html);
    }

    public function test_the_dashboard_offers_cards_the_person_can_open(): void
    {
        $admin = $this->userFor('administrator');

        $this->assertTrue(PermissionRegistry::userCan($admin, 'students.students.view'));
        $this->assertTrue(PermissionRegistry::userCan($admin, 'admissions.applications.view'));

        $html = $this->get('/workspace')->getContent();

        $this->assertStringContainsString('/workspace/students', $html);
        $this->assertStringContainsString('/workspace/applications', $html);
    }

    public function test_a_category_hub_lists_only_the_pages_the_person_may_open(): void
    {
        $teacher = $this->userFor('teaching_staff');

        // Setup & Structure holds Level, Subjects, Classrooms and Academic Years.
        $all = ['Level', 'Subjects', 'Classrooms', 'Academic Years'];

        $keys = [
            'Level' => 'academics.level.view',
            'Subjects' => 'academics.subjects.view',
            'Classrooms' => 'academics.classrooms.view',
            'Academic Years' => 'academics.academic_years.view',
        ];

        foreach ($keys as $label => $key) {
            $this->assertTrue(
                PermissionRegistry::isGranted($teacher->customRole->permissions, $key)
                || PermissionRegistry::userCan($teacher, $key),
                "Precondition: a teacher is expected to reach {$label}."
            );
        }

        $this->assertSame($all, $this->hubPageLabels());
    }

    public function test_a_category_hub_drops_a_page_the_person_may_not_open(): void
    {
        // A bespoke role rather than an edited catalogue default: the built-in
        // teaching role holds `academics.view_module`, which by design implies
        // every page inside academics, so no box on it can be unticked. This is
        // the shape an administrator actually builds — grant exactly the pages
        // one person needs.
        $school = School::create([
            'name' => 'Hub '.uniqid(),
            'subdomain' => 'hub'.uniqid(),
            'status' => 'active',
        ]);
        $this->ids[] = $school;

        $role = CustomRole::create([
            'school_id' => $school->id,
            'name' => 'Level & Subject Lead',
            'role_key' => null,
            'is_system' => false,
            'permissions' => [
                'academics.level.view',
                'academics.subjects.view',
                'academics.classrooms.view',
            ],
        ]);
        $this->ids[] = $role;

        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Hub '.uniqid(),
            'email' => uniqid().'@hub.test',
            'password' => bcrypt('secret1234'),
        ]);
        $this->ids[] = $user;
        $user->forceFill([
            'custom_role_id' => $role->id,
            'account_status' => User::STATUS_ACTIVE,
        ])->save();

        $this->actingAs($user);
        $this->actingAsTenant($school);

        $this->assertFalse(
            PermissionRegistry::userCan($user, 'academics.academic_years.view'),
            'Precondition: Academic Years has not been granted.'
        );

        $labels = $this->hubPageLabels();

        $this->assertSame(['Level', 'Subjects', 'Classrooms'], $labels);
        $this->assertNotContains('Academic Years', $labels);
    }

    /**
     * The teacher is trusted with marking work and holding the academic record,
     * but publishing results to the whole school is somebody else's decision.
     */
    public function test_a_teacher_may_not_publish_to_the_student_portal(): void
    {
        $teacher = $this->userFor('teaching_staff');

        $this->assertTrue(
            PermissionRegistry::userCan($teacher, 'exams.marks_entry.view'),
            'Precondition: a teacher still enters and sees marks.'
        );

        $this->assertFalse(
            PermissionRegistry::userCan($teacher, 'exams.publish_to_student_portal.view'),
            'A teacher must not be offered the publishing screen at all.'
        );
        $this->assertFalse(
            PermissionRegistry::userCan($teacher, 'exams.publish_to_student_portal.publish'),
            'A teacher must not be able to publish, even by posting the action.'
        );

        $html = $this->get('/workspace')->getContent();
        $this->assertStringNotContainsString('Publish to Student Portal', $html);
    }

    /**
     * The role that does own Exams keeps the screen, so the rule above narrows
     * the role rather than removing the feature from the school.
     */
    public function test_the_exams_role_still_publishes_to_the_student_portal(): void
    {
        $examOfficer = $this->userFor('procurement');

        $this->assertTrue(PermissionRegistry::userCan($examOfficer, 'exams.publish_to_student_portal.view'));
        $this->assertTrue(PermissionRegistry::userCan($examOfficer, 'exams.publish_to_student_portal.publish'));
    }

    /**
     * Exports hand out the whole register, so the button follows the permission
     * rather than the module switch.
     */
    public function test_exports_follow_the_export_permission(): void
    {
        $teacher = $this->userFor('teaching_staff');

        $this->assertFalse(
            PermissionRegistry::userCan($teacher, 'students.students.export'),
            'A teacher has no student register to export in the first place.'
        );
        $this->assertFalse(
            PermissionRegistry::userCan($teacher, 'hr.staff_attendance.export'),
            'Staff attendance is HR\'s register, not a teacher\'s.'
        );

        $hr = $this->userFor('hr');
        $this->assertTrue(PermissionRegistry::userCan($hr, 'hr.staff_attendance.export'));
    }

    /**
     * A school may invent roles and name them freely, so "school administrator"
     * has to be recognised by the catalogue identity rather than by a label.
     * Otherwise a departmental role somebody named Administrator — or renamed
     * later, innocently — quietly acquires the whole school.
     */
    public function test_a_role_named_administrator_is_not_the_administrator(): void
    {
        $school = $this->school();
        $user = $this->userIn($school, 'supporting_staff');

        $impostor = new CustomRole([
            'school_id' => $school->id,
            'name' => 'Administrator',
            'role_key' => 'impostor_admin',
            'permissions' => ['dashboard.view'],
            'permissions_customised' => true,
        ]);

        $impostor->save();

        $user->forceFill(['custom_role_id' => $impostor->id])->save();

        $this->actingAs($user->fresh());
        $this->actingAsTenant($school);

        $this->assertFalse(
            ModuleVisibilityManager::isSchoolAdmin(),
            'A custom role must not become the administrator by being called one.'
        );

        // And the consequence that mattered: no accidental reach.
        $this->assertFalse(PermissionRegistry::userCan($user->fresh(), 'hr.payroll_periods.view'));
    }

    /**
     * @return array<int, string>
     */
    private function hubPageLabels(): array
    {
        return array_map(
            fn (array $tab): string => $tab['label'],
            (new SetupStructureHub)->getCategoryPages()
        );
    }
}
