<?php

namespace Tests\Feature;

use App\Filament\App\Pages\ApplicationSuccess;
use App\Filament\App\Resources\SubjectResource;
use App\Security\CapabilityCatalog;
use App\Security\RoleCatalogue;
use Modules\Admin\Services\PermissionRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The access rules, checked against the capability catalogue directly.
 *
 * These are the promises the system makes: a role owns the modules it was given
 * and nothing else, universal self-service reaches every member of staff without
 * leaking a whole module, granting a whole module covers pages added later, and
 * nobody can reach a screen the catalogue has not described.
 */
class CapabilityCatalogueTest extends TestCase
{
    public function test_every_role_resolves_to_a_concrete_permission_list(): void
    {
        foreach (RoleCatalogue::roles() as $key => $role) {
            $permissions = RoleCatalogue::permissionsFor($key);

            $this->assertNotEmpty($permissions, "{$key} resolved to no permissions");

            foreach ($permissions as $permission) {
                $this->assertTrue(
                    in_array($permission, PermissionRegistry::collectAllPermissionKeys(), true)
                        || $permission === PermissionRegistry::WILDCARD,
                    "{$key} holds '{$permission}', which does not exist",
                );
            }
        }
    }

    public function test_the_eleven_documented_roles_exist(): void
    {
        $this->assertCount(11, RoleCatalogue::roles());

        foreach ([
            'System Administrator', 'School Administrator', 'Teaching Staff',
            'Accounts / Finance', 'HR', 'Health', 'Procurement', 'Librarian',
            'Houseparent', 'Supporting Staff', 'Student',
        ] as $label) {
            $this->assertContains($label, array_column(RoleCatalogue::roles(), 'label'));
        }
    }

    public function test_students_are_not_offered_as_employees(): void
    {
        $labels = array_values(RoleCatalogue::employeeRoles());

        $this->assertNotContains('Student', $labels);
        $this->assertContains('Teaching Staff', $labels);
        $this->assertCount(10, $labels);
    }

    public function test_the_system_administrator_can_do_anything(): void
    {
        $permissions = RoleCatalogue::permissionsFor('administrator');

        $this->assertSame([PermissionRegistry::WILDCARD], $permissions);

        // The wildcard is the whole point: it covers keys that do not exist yet.
        $this->assertTrue(PermissionRegistry::isGranted($permissions, 'anything.at_all.goes'));
    }

    public function test_the_student_role_is_confined_to_the_student_portal(): void
    {
        $permissions = RoleCatalogue::permissionsFor('student');

        $this->assertSame(['student_portal.access'], $permissions);
        $this->assertFalse(PermissionRegistry::isGranted($permissions, 'academics.subjects.view'));
        $this->assertFalse(PermissionRegistry::isGranted($permissions, 'finance.invoices.view'));
    }

    #[DataProvider('roleModuleProvider')]
    public function test_a_role_reaches_all_of_its_own_modules_and_nothing_else(
        string $roleKey,
        array $owned,
    ): void {
        $permissions = RoleCatalogue::permissionsFor($roleKey);

        // Every non-staff role reaches some part of HR and Communication through
        // the universal self-service set, so those two are asserted on their own
        // below rather than being read as "the role owns this module".
        $shared = ['hr', 'communication'];

        $reached = [];

        foreach (CapabilityCatalog::modules() as $slug => $module) {
            if ($slug === 'universal') {
                continue;
            }

            $visible = 0;

            foreach ($module['pages'] as $key => $page) {
                if (PermissionRegistry::isGrantedAny(
                    $permissions,
                    CapabilityCatalog::accessKeysFor($slug, $key),
                )) {
                    $visible++;
                }
            }

            if ($visible > 0) {
                $reached[$slug] = $visible;
            }
        }

        $groups = RoleCatalogue::groupsFor($roleKey);
        $except = RoleCatalogue::excludedPagesFor($roleKey);

        foreach ($reached as $slug => $visible) {
            if (in_array($slug, $owned, true)) {
                // A page named in `except` is deliberately held back even from a
                // role that owns the module, so it is not counted as a miss.
                $expected = count(CapabilityCatalog::module($slug)['pages']) - $this->excludedCount($except, $slug);

                $this->assertSame(
                    $expected,
                    $visible,
                    "{$roleKey} owns {$slug} but cannot reach every page in it",
                );
            } elseif (isset($groups[$slug])) {
                // The role owns named categories of this module, and only those.
                $expected = $this->groupPageCount($slug, $groups[$slug], $except);

                $this->assertSame(
                    $expected,
                    $visible,
                    "{$roleKey} owns the named groups of {$slug} but reached {$visible} pages instead of {$expected}",
                );
            } elseif (! in_array($slug, $shared, true)) {
                $this->fail("{$roleKey} reached {$visible} page(s) of {$slug}, which it does not own");
            }
        }

        foreach ($owned as $slug) {
            $this->assertArrayHasKey($slug, $reached, "{$roleKey} owns {$slug} but reached none of it");
        }

        // Every module a role reaches only through named groups must actually be
        // reachable, otherwise a typo in a group name would silently drop access.
        foreach (array_keys($groups) as $slug) {
            $this->assertArrayHasKey(
                $slug,
                $reached,
                "{$roleKey} owns groups of {$slug} but reached none of it",
            );
        }

        // A student is the one role that gets no self-service: they are not
        // staff, they have the portal. Every other role owns no module yet must
        // still reach the shared screens.
        $this->assertSame(
            $roleKey === 'student',
            $reached === [],
            $roleKey === 'student'
                ? 'A student must reach nothing but the portal'
                : "{$roleKey} reached nothing at all, not even self-service",
        );
    }

    /**
     * A role's reach is described by the modules it owns outright plus the page
     * groups it owns inside modules it only partly owns.
     *
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public static function roleModuleProvider(): array
    {
        return [
            'school administrator' => ['school_administrator', ['academics', 'admissions', 'communication', 'inventory', 'library', 'students', 'website']],
            // teaching_staff owns academics outright and three groups of exams.
            'teaching staff' => ['teaching_staff', ['academics']],
            'accounts' => ['accounts_finance', ['finance']],
            'hr' => ['hr', ['hr']],
            'health' => ['health', ['health']],
            // procurement runs purchasing and stock.
            'procurement' => ['procurement', ['inventory']],
            // librarian runs the library, not the public website.
            'librarian' => ['librarian', ['library']],
            'houseparent' => ['houseparent', ['boarding']],
            'supporting staff' => ['supporting_staff', []],
            'student' => ['student', []],
        ];
    }

    public function test_a_group_label_that_does_not_exist_is_reported(): void
    {
        $known = RoleCatalogue::groupsWithModules();

        $this->assertNotSame([], $known, 'no role declares a page group');

        foreach ($known as $moduleSlug => $labels) {
            foreach ($labels as $label) {
                $this->assertNotSame(
                    [],
                    CapabilityCatalog::pageKeysInGroup($moduleSlug, $label),
                    "group \"{$label}\" is declared for {$moduleSlug} but matches no page",
                );
            }
        }
    }

    public function test_every_excluded_page_is_a_real_page(): void
    {
        $found = false;

        foreach (RoleCatalogue::keys() as $roleKey) {
            foreach (RoleCatalogue::excludedPagesFor($roleKey) as $pageKey) {
                $found = true;

                [$moduleSlug, $page] = explode('.', $pageKey, 2);

                $this->assertNotNull(
                    CapabilityCatalog::page($moduleSlug, $page),
                    "{$roleKey} excludes \"{$pageKey}\", which is not a real page",
                );
            }
        }

        $this->assertTrue($found, 'no role declares an excluded page');
    }

    public function test_teaching_staff_cannot_reach_the_student_portal_publishing_page(): void
    {
        $permissions = RoleCatalogue::permissionsFor('teaching_staff');

        // The headline example from the requirements: a teacher may work with
        // reports but must never see or touch Publish to Student Portal.
        foreach (CapabilityCatalog::page('exams', 'publish_to_student_portal')['actions'] as $action) {
            $this->assertFalse(
                PermissionRegistry::isGranted(
                    $permissions,
                    CapabilityCatalog::pagePermissionKey('exams', 'publish_to_student_portal', $action),
                ),
                "teaching_staff must not be granted {$action} on Publish to Student Portal",
            );
        }

        $this->assertFalse(
            PermissionRegistry::isGrantedAny(
                $permissions,
                CapabilityCatalog::accessKeysFor('exams', 'publish_to_student_portal'),
            ),
            'teaching_staff must not reach Publish to Student Portal at all',
        );

        // ...while the rest of that category stays open to them.
        $this->assertTrue(
            PermissionRegistry::isGrantedAny(
                $permissions,
                CapabilityCatalog::accessKeysFor('exams', 'report_cards'),
            ),
            'teaching_staff must keep Report Cards',
        );
    }

    /**
     * Publishing results to the student portal is a school-level decision.
     *
     * Teachers are held back from it deliberately. Procurement used to hold it,
     * because procurement ran assessment publishing — but procurement now runs
     * purchasing and stock, which has nothing to do with marking, so the page
     * followed the job rather than staying with the old title. What is left is
     * the administrator, which is the correct answer: publishing to the whole
     * school is not a departmental task, and no single job title was ever a
     * convincing owner of it.
     *
     * This is worth pinning down, because "nobody but the administrator can do
     * this" reads like a gap rather than a decision unless something says so.
     */
    public function test_only_the_administrator_publishes_to_the_student_portal(): void
    {
        $keys = CapabilityCatalog::accessKeysFor('exams', 'publish_to_student_portal');

        // Teachers keep everything else in that category.
        $teacher = RoleCatalogue::permissionsFor('teaching_staff');
        $this->assertTrue(
            PermissionRegistry::isGrantedAny($teacher, CapabilityCatalog::accessKeysFor('exams', 'report_cards')),
            'teaching_staff must keep Report Cards',
        );
        $this->assertFalse(
            PermissionRegistry::isGrantedAny($teacher, $keys),
            'A teacher must not publish to the student portal.',
        );

        // Procurement looks after purchasing and stock now, so it does not reach
        // the publishing page either.
        $this->assertFalse(
            PermissionRegistry::isGrantedAny(RoleCatalogue::permissionsFor('procurement'), $keys),
            'Procurement runs purchasing and stock, not assessment publishing.',
        );

        // The administrator can.
        $this->assertTrue(
            PermissionRegistry::isGrantedAny(
                RoleCatalogue::permissionsFor('administrator'),
                $keys,
            ),
            'The administrator must be able to publish to the student portal.',
        );
    }

    public function test_a_role_never_reaches_a_page_outside_its_modules_and_groups(): void
    {
        foreach (RoleCatalogue::keys() as $roleKey) {
            if (RoleCatalogue::isFullAccess($roleKey) || RoleCatalogue::isPortalOnly($roleKey)) {
                continue;
            }

            $permissions = RoleCatalogue::permissionsFor($roleKey);
            $modules = RoleCatalogue::modulesFor($roleKey);

            // HR and Communication are shared: every member of staff is meant to
            // reach a few screens there, and the self-service test covers which.
            $shared = ['hr', 'communication'];

            foreach (CapabilityCatalog::modules() as $slug => $module) {
                if ($slug === 'universal'
                    || in_array($slug, $modules, true)
                    || in_array($slug, $shared, true)) {
                    continue;
                }

                foreach ($module['pages'] as $key => $page) {
                    $this->assertFalse(
                        PermissionRegistry::isGrantedAny(
                            $permissions,
                            CapabilityCatalog::accessKeysFor($slug, $key),
                        ),
                        "{$roleKey} does not own {$slug} but reached {$key}",
                    );
                }
            }
        }
    }

    /**
     * How many pages of a module a set of named categories covers, minus any of
     * them the role holds back through `except`.
     *
     * @param  array<int, string>  $groupLabels
     * @param  array<int, string>  $excluded  "module.page_key" strings.
     */
    private function groupPageCount(string $moduleSlug, array $groupLabels, array $excluded): int
    {
        $keys = [];

        foreach ($groupLabels as $label) {
            foreach (CapabilityCatalog::pageKeysInGroup($moduleSlug, $label) as $pageKey) {
                $keys[] = "{$moduleSlug}.{$pageKey}";
            }
        }

        return count(array_diff($keys, $excluded));
    }

    /**
     * @param  array<int, string>  $excluded  "module.page_key" strings.
     */
    private function excludedCount(array $excluded, string $moduleSlug): int
    {
        return count(array_filter(
            $excluded,
            static fn (string $pageKey): bool => str_starts_with($pageKey, $moduleSlug.'.'),
        ));
    }

    /**
     * Everyone reaches the two shared modules, but only the handful of screens
     * that belong to every member of staff.
     *
     * In HR that is Leave Requests and nothing else. Every other HR screen —
     * the staff register, the attendance register, payroll — describes the
     * school rather than the person using it, and belongs to the administrator
     * and to HR. See HrModuleIsolationTest for the same boundary stated
     * directly against the navigation.
     */
    #[DataProvider('sharedModulePageProvider')]
    public function test_self_service_reaches_only_the_universal_screens(
        string $roleKey,
        string $moduleKey,
        array $expected,
    ): void {
        $permissions = RoleCatalogue::permissionsFor($roleKey);

        $reached = [];

        foreach (CapabilityCatalog::module($moduleKey)['pages'] as $key => $page) {
            if (PermissionRegistry::isGrantedAny(
                $permissions,
                CapabilityCatalog::accessKeysFor($moduleKey, $key),
            )) {
                $reached[] = $page['label'];
            }
        }

        sort($reached);
        $expected = array_map(fn (string $label) => CapabilityCatalog::pageLabel($moduleKey, $label), $expected);
        sort($expected);

        $this->assertSame($expected, $reached);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: array<int, string>}>
     */
    public static function sharedModulePageProvider(): array
    {
        // The HUBs appear because a page inside them is viewable, which is the
        // behaviour asserted in test_a_category_hub_opens_for_anyone_who_can_reach_one_page_in_it.
        //
        // HR carries 'Attendance & Leave' only because Leave Requests lives
        // under it — the hub has no content of its own to gate.
        $ownLeave = ['Attendance & Leave', 'Leave Requests'];

        return [
            'supporting staff, HR' => [
                'supporting_staff', 'hr',
                $ownLeave,
            ],
            'supporting staff, Communication' => [
                'supporting_staff', 'communication',
                ['Community & Engagement', 'Announcements', 'Chat', 'Help & Inbox', 'Helpdesk', 'Events', 'Polls & Surveys', 'Resources'],
            ],
            'teaching staff, HR' => [
                'teaching_staff', 'hr',
                $ownLeave,
            ],
            'accounts, HR' => [
                'accounts_finance', 'hr',
                $ownLeave,
            ],
            'houseparent, HR' => [
                'houseparent', 'hr',
                $ownLeave,
            ],
        ];
    }

    /**
     * The rest of Communication belongs to ranges the whole school should not
     * see. The Community & Engagement set (Events, Resources, Polls & Surveys)
     * is deliberately open to every role; the platform's Kairo CORE inbox and
     * the module Overview remain peripheral to a supporting-staff member.
     */
    #[DataProvider('sharedModuleClosedPageProvider')]
    public function test_self_service_leaves_the_school_wide_screens_closed(
        string $roleKey,
        string $moduleKey,
        string $label,
    ): void {
        $permissions = RoleCatalogue::permissionsFor($roleKey);

        $pages = CapabilityCatalog::module($moduleKey)['pages'];

        $target = null;

        foreach ($pages as $page) {
            if ($page['label'] === $label) {
                $target = $page;
            }
        }

        $this->assertNotNull($target, "{$moduleKey} has no page labelled '{$label}'");

        $this->assertFalse(
            PermissionRegistry::isGrantedAny(
                $permissions,
                CapabilityCatalog::accessKeysFor($moduleKey, $target['key']),
            ),
            "{$roleKey} should not reach {$moduleKey}.{$target['key']}",
        );
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    public static function sharedModuleClosedPageProvider(): array
    {
        return [
            ['supporting_staff', 'communication', 'Kairo CORE Messages'],
            ['supporting_staff', 'hr', 'Payroll Periods'],
            ['supporting_staff', 'hr', 'Salary Grades'],
            ['supporting_staff', 'hr', 'Staff Loans'],
            ['supporting_staff', 'hr', 'Disciplinary Cases'],
            ['supporting_staff', 'hr', 'Staff Assets'],
            ['supporting_staff', 'communication', 'Overview'],
        ];
    }

    public function test_a_teacher_cannot_reach_any_finance_page(): void
    {
        $permissions = RoleCatalogue::permissionsFor('teaching_staff');

        $reached = [];

        foreach (CapabilityCatalog::module('finance')['pages'] as $key => $page) {
            if (PermissionRegistry::isGrantedAny(
                $permissions,
                CapabilityCatalog::accessKeysFor('finance', $key),
            )) {
                $reached[] = $page['label'];
            }
        }

        $this->assertSame([], $reached, 'A teacher reached a finance page');
    }

    public function test_every_member_of_staff_can_manage_their_own_leave_without_the_hr_module(): void
    {
        foreach (RoleCatalogue::keys() as $key) {
            if ($key === 'student') {
                continue;
            }

            $permissions = RoleCatalogue::permissionsFor($key);

            $this->assertTrue(
                PermissionRegistry::isGranted($permissions, 'hr.leave_requests.view'),
                "{$key} cannot see their own leave requests",
            );
            $this->assertTrue(
                PermissionRegistry::isGranted($permissions, 'hr.leave_requests.create'),
                "{$key} cannot apply for leave",
            );
        }
    }

    public function test_self_service_does_not_hand_staff_the_whole_hr_or_communication_module(): void
    {
        // A supporting-staff member owns no specialist module, so anything HR
        // they can reach came from the universal self-service set. If that set
        // were expressed as a module-wide key, they would have the lot.
        $permissions = RoleCatalogue::permissionsFor('supporting_staff');

        $reached = [];

        foreach (CapabilityCatalog::module('hr')['pages'] as $key => $page) {
            if (PermissionRegistry::isGrantedAny(
                $permissions,
                CapabilityCatalog::accessKeysFor('hr', $key),
            )) {
                $reached[] = $page['label'];
            }
        }

        $this->assertNotContains('Payroll Periods', $reached);
        $this->assertNotContains('Salary Grades', $reached);
        $this->assertNotContains('Disciplinary Cases', $reached);
        $this->assertContains('Leave Requests', $reached);
    }

    public function test_a_whole_module_grant_covers_pages_added_later(): void
    {
        // 'finance.edit' is the module-wide form. A page nobody has configured
        // yet, sitting inside Finance, must be covered by it.
        $this->assertTrue(PermissionRegistry::isGranted(['finance.edit'], 'finance.some_future_page.edit'));
        $this->assertTrue(PermissionRegistry::isGranted(['finance.view_module'], 'finance.some_future_page.view'));

        // ...but only the operation that was actually granted.
        $this->assertFalse(PermissionRegistry::isGranted(['finance.view_module'], 'finance.some_future_page.delete'));
    }

    public function test_a_page_grant_does_not_leak_into_another_module(): void
    {
        $this->assertFalse(PermissionRegistry::isGranted(['finance.invoices.view'], 'academics.subjects.view'));
        $this->assertFalse(PermissionRegistry::isGranted(['hr.view_module'], 'finance.invoices.view'));
    }

    public function test_viewing_a_page_does_not_imply_editing_it(): void
    {
        $this->assertTrue(PermissionRegistry::isGranted(['academics.subjects.view'], 'academics.subjects.view'));
        $this->assertFalse(PermissionRegistry::isGranted(['academics.subjects.view'], 'academics.subjects.edit'));
        $this->assertFalse(PermissionRegistry::isGranted(['academics.subjects.view'], 'academics.subjects.delete'));
    }

    public function test_naming_inexistent_permissions_is_dropped_rather_than_silently_kept(): void
    {
        $clean = PermissionRegistry::normalizePermissionList([
            'academics.subjects.view',
            'academics.not_a_page.view',
            '  finance.invoices.view  ',
            '',
            '*',
        ]);

        $this->assertSame(['academics.subjects.view', 'finance.invoices.view', '*'], $clean);
    }

    /**
     * The example the requirements describe: Setup & Structure with Level,
     * Subjects and Classrooms granted, but not Academic Years.
     */
    public function test_a_group_stays_visible_when_only_some_of_its_pages_are_granted(): void
    {
        $permissions = [
            'academics.level.view',
            'academics.subjects.view',
            'academics.classrooms.view',
        ];

        $reached = [];

        foreach (CapabilityCatalog::module('academics')['pages'] as $key => $page) {
            if (PermissionRegistry::isGrantedAny(
                $permissions,
                CapabilityCatalog::accessKeysFor('academics', $key),
            )) {
                $reached[] = $page['label'];
            }
        }

        $this->assertContains('Level', $reached);
        $this->assertContains('Subjects', $reached);
        $this->assertContains('Classrooms', $reached);
        $this->assertNotContains('Academic Years', $reached);
    }

    public function test_a_category_hub_opens_for_anyone_who_can_reach_one_page_in_it(): void
    {
        $teacher = RoleCatalogue::permissionsFor('teaching_staff');

        // Teachers own all of Academics, so every academic hub is open to them.
        $this->assertTrue(PermissionRegistry::isGrantedAny(
            $teacher,
            CapabilityCatalog::accessKeysFor('academics', 'setup_structure'),
        ));

        // And no finance hub, because they own none of Finance.
        $this->assertFalse(PermissionRegistry::isGrantedAny(
            $teacher,
            CapabilityCatalog::accessKeysFor('finance', 'student_billing_revenue'),
        ));
        $this->assertFalse(PermissionRegistry::isGrantedAny(
            $teacher,
            CapabilityCatalog::accessKeysFor('finance', 'core_accounting_setup'),
        ));
    }

    public function test_a_teacher_reaches_their_own_leave_but_not_the_payroll_hub(): void
    {
        $teacher = RoleCatalogue::permissionsFor('teaching_staff');

        $this->assertTrue(PermissionRegistry::isGrantedAny(
            $teacher,
            CapabilityCatalog::accessKeysFor('hr', 'attendance_leave'),
        ));
        $this->assertFalse(PermissionRegistry::isGrantedAny(
            $teacher,
            CapabilityCatalog::accessKeysFor('hr', 'payroll_compensation'),
        ));
    }

    public function test_the_page_labels_asserted_by_the_tests_are_the_real_ones(): void
    {
        // The self-service tests above name screens as a user would look for
        // them. If a page is renamed, those tests should fail loudly rather than
        // quietly stop asserting anything.
        $this->assertSame('Leave Requests', CapabilityCatalog::pageLabel('hr', 'Leave Requests'));
        $this->assertSame('Schedule & Tasks', CapabilityCatalog::pageLabel('communication', 'Schedule & Tasks'));
        $this->assertSame('Payroll Periods', CapabilityCatalog::pageLabel('hr', 'Payroll Periods'));
        $this->assertSame('Kairo CORE Messages', CapabilityCatalog::pageLabel('communication', 'Kairo CORE Messages'));
    }

    public function test_every_catalogue_page_names_a_class_that_exists(): void
    {
        foreach (CapabilityCatalog::modules() as $module) {
            foreach ($module['pages'] as $page) {
                $this->assertTrue(
                    class_exists($page['class']),
                    "{$module['key']}.{$page['key']} points at missing class {$page['class']}",
                );
            }
        }
    }

    public function test_no_two_catalogue_pages_share_a_permission_key(): void
    {
        $keys = CapabilityCatalog::allPermissionKeys();

        $this->assertSame(
            count($keys),
            count(array_unique($keys)),
            'The catalogue contains a duplicate permission key',
        );
    }

    public function test_every_catalogue_action_is_described(): void
    {
        foreach (CapabilityCatalog::allPermissionKeys() as $key) {
            $description = CapabilityCatalog::describe($key);

            $this->assertNotSame('', trim($description), "'{$key}' has no help text");
        }
    }

    public function test_every_catalogue_page_explains_what_it_is_for(): void
    {
        foreach (CapabilityCatalog::modules() as $module) {
            foreach ($module['pages'] as $key => $page) {
                $this->assertNotSame('', trim((string) $page['purpose']));
            }
        }
    }

    public function test_a_catalogue_page_is_found_from_its_class(): void
    {
        $page = CapabilityCatalog::pageForClass(SubjectResource::class);

        $this->assertNotNull($page);
        $this->assertSame('academics', $page['module']);
        $this->assertSame('subjects', $page['key']);
    }

    public function test_a_class_outside_the_catalogue_is_denied(): void
    {
        // ApplicationSuccess is reachable only after submitting a form; it is
        // deliberately not a navigable page and has no capabilities of its own.
        $this->assertNull(CapabilityCatalog::pageForClass(ApplicationSuccess::class));
    }
}
