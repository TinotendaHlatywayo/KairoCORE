<?php

namespace Tests\Feature;

use App\Security\RoleCatalogue;
use Modules\Admin\Services\PermissionRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * HR is the module where over-granting is most expensive and least obvious.
 *
 * Payroll, compensation, disciplinary cases and the staff register are the
 * school's most private records, and they are also the ones a person is most
 * likely to assume they already have because they can see *something* in HR —
 * which, for every member of staff, is their own leave.
 *
 * So this fixes the line precisely: inside HR, Leave Requests is everybody's,
 * and everything else belongs to the administrator and to HR. The test is
 * written from the specification rather than from the implementation, so a
 * future role that quietly widens the boundary fails here.
 */
class HrModuleIsolationTest extends TestCase
{
    /**
     * Every page inside HR & Payroll, grouped by the category the school
     * administrator sees them under.
     *
     * @var array<string, array<int, string>>
     */
    private const HR_PAGES = [
        'Staff Directory & HR' => [
            'staff_directory_hr', 'employees', 'staff_assets', 'disciplinary_cases',
        ],
        'Payroll & Compensation' => [
            'payroll_compensation', 'payroll_periods', 'salary_grades', 'staff_loans',
        ],
        'Attendance & Leave' => [
            'attendance_leave', 'leave_requests', 'staff_attendance',
        ],
    ];

    /**
     * The roles that run HR. Everything else is a member of staff filing leave.
     *
     * @var array<int, string>
     */
    private const HR_ROLES = ['hr'];

    /**
     * What every member of staff reaches inside HR: their own leave, and
     * nothing else.
     *
     * @var array<int, string>
     */
    private const EVERYONE_PAGES = ['leave_requests'];

    /**
     * The roles to check, listed literally.
     *
     * Data providers run before the framework is booted, so this cannot call
     * RoleCatalogue::keys() — reading a translated label needs the container.
     * Spelling the keys out also puts the specification in one readable place:
     * these ten are the roles the school starts with that live in the
     * workspace. Students register through the student portal instead.
     *
     * @return array<string, array<int, string>>
     */
    public static function roleProvider(): array
    {
        return [
            'administrator' => ['administrator'],
            'school administrator' => ['school_administrator'],
            'teaching staff' => ['teaching_staff'],
            'accounts / finance' => ['accounts_finance'],
            'hr' => ['hr'],
            'health' => ['health'],
            'procurement' => ['procurement'],
            'librarian' => ['librarian'],
            'houseparent' => ['houseparent'],
            'supporting staff' => ['supporting_staff'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function allRoleKeys(): array
    {
        return array_map(
            static fn (array $case): string => $case[0],
            array_values(self::roleProvider())
        );
    }

    /**
     * What a role is expected to reach inside HR.
     *
     * @return array<int, string>
     */
    private static function expectedFor(string $roleKey): array
    {
        $hrAndAdmin = [];

        foreach (self::HR_PAGES as $pages) {
            $hrAndAdmin = array_merge($hrAndAdmin, $pages);
        }

        return ($roleKey === 'administrator' || in_array($roleKey, self::HR_ROLES, true))
            ? $hrAndAdmin
            : self::EVERYONE_PAGES;
    }

    /**
     * The pages this role can actually open inside HR.
     *
     * @return array<int, string>
     */
    private function hrPagesFor(string $roleKey): array
    {
        $permissions = RoleCatalogue::permissionsFor($roleKey);
        $reachable = [];

        foreach (self::HR_PAGES as $pages) {
            foreach ($pages as $page) {
                if (PermissionRegistry::isGranted($permissions, "hr.{$page}.view")) {
                    $reachable[] = $page;
                }
            }
        }

        return $reachable;
    }

    /**
     * Every staff role reaches Leave Requests and nothing else in HR.
     */
    #[DataProvider('roleProvider')]
    public function test_each_default_role_reaches_exactly_the_intended_hr_pages(string $roleKey): void
    {
        if (RoleCatalogue::isPortalOnly($roleKey)) {
            $this->markTestSkipped('Students live in the student portal, not the workspace.');
        }

        $this->assertEqualsCanonicalizing(
            self::expectedFor($roleKey),
            $this->hrPagesFor($roleKey),
            'HR & Payroll must offer exactly these pages to this role.'
        );
    }

    /**
     * The payroll and personnel screens are not part of anyone's day job except
     * the two roles responsible for them.
     */
    #[DataProvider('roleProvider')]
    public function test_private_hr_records_are_withheld_from_ordinary_staff(string $roleKey): void
    {
        if (RoleCatalogue::isPortalOnly($roleKey)) {
            $this->markTestSkipped('Students live in the student portal, not the workspace.');
        }

        if (RoleCatalogue::isFullAccess($roleKey) || in_array($roleKey, self::HR_ROLES, true)) {
            $this->markTestSkipped('This role is responsible for HR.');
        }

        $private = [
            'payroll_compensation',
            'payroll_periods',
            'salary_grades',
            'staff_loans',
            'disciplinary_cases',
            'staff_directory_hr',
            'employees',
            'staff_attendance',
        ];

        $permissions = RoleCatalogue::permissionsFor($roleKey);

        foreach ($private as $page) {
            $this->assertFalse(
                PermissionRegistry::isGranted($permissions, "hr.{$page}.view"),
                "{$roleKey} must not reach {$page}."
            );
        }
    }

    /**
     * Nobody but the administrator and HR may approve somebody else's leave.
     */
    #[DataProvider('roleProvider')]
    public function test_only_hr_and_the_administrator_approve_leave(string $roleKey): void
    {
        if (RoleCatalogue::isPortalOnly($roleKey)) {
            $this->markTestSkipped('Students live in the student portal, not the workspace.');
        }

        $permissions = RoleCatalogue::permissionsFor($roleKey);

        $responsible = $roleKey === 'administrator' || in_array($roleKey, self::HR_ROLES, true);

        $this->assertSame(
            $responsible,
            PermissionRegistry::isGranted($permissions, 'hr.leave_requests.approve'),
            'Approving leave is HR\'s decision, not a self-service one.'
        );
    }

    /**
     * Filing one's own leave is universal, so a school cannot end up with staff
     * who have no way to ask for time off.
     */
    #[DataProvider('roleProvider')]
    public function test_every_staff_role_can_file_their_own_leave(string $roleKey): void
    {
        if (RoleCatalogue::isPortalOnly($roleKey)) {
            $this->markTestSkipped('Students live in the student portal, not the workspace.');
        }

        $permissions = RoleCatalogue::permissionsFor($roleKey);

        $this->assertTrue(
            PermissionRegistry::isGranted($permissions, 'hr.leave_requests.create'),
            'Everyone works, so everyone must be able to apply for leave.'
        );
    }

    /**
     * A role nobody grants view_module to cannot open a page by implication, so
     * the "no module-wide grant" rule that keeps HR narrow is itself protected.
     *
     * The administrator and HR are exempt, and necessarily so: their job *is*
     * the module, and `hr.view_module` is how the catalogue expresses that. The
     * risk this guards against is the opposite accident — a module-wide key
     * handed to a role that only meant to reach one screen, which would quietly
     * open payroll to a houseparent.
     */
    public function test_no_ordinary_role_is_handed_the_whole_hr_module(): void
    {
        foreach (array_keys(self::allRoleKeys()) as $roleKey) {
            if ($roleKey === 'administrator' || in_array($roleKey, self::HR_ROLES, true)) {
                continue;
            }

            $this->assertFalse(
                PermissionRegistry::isGranted(RoleCatalogue::permissionsFor($roleKey), 'hr.view_module'),
                "{$roleKey} holds hr.view_module, which would imply every HR page."
            );
        }
    }

    /**
     * The complementary check on the exempt role: HR must actually reach
     * everything in its own module, or the exemption above would be hiding a
     * role that cannot do its job.
     */
    public function test_hr_reaches_every_hr_page(): void
    {
        $permissions = RoleCatalogue::permissionsFor('hr');

        foreach (self::HR_PAGES as $pages) {
            foreach ($pages as $page) {
                $this->assertTrue(
                    PermissionRegistry::isGranted($permissions, "hr.{$page}.view"),
                    "HR must reach {$page}."
                );
            }
        }
    }
}
