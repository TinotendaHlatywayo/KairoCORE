<?php

namespace App\Security;

use App\Models\User;
use Modules\Admin\Services\PermissionRegistry;

/**
 * The catalogue of job roles the school ships with, and what each one may do.
 *
 * Every entry pairs a role with the modules that role owns. When a role is
 * granted to somebody they receive full access to those modules — every page,
 * every operation — plus the self-service set that applies to every member of
 * staff. An administrator can then narrow a role, or hand one individual extra
 * modules, without editing code.
 *
 * A "full access" grant is stored as the module's own permission keys
 * (`finance.*`), so a role always reads as "Finance — everything" in the
 * permission editor and can be trimmed page by page afterwards.
 */
final class RoleCatalogue
{
    /**
     * Which navigation module owns each legacy permission namespace.
     *
     * Some screens still express a narrower, older capability (for example
     * "manage classrooms" or "receive payments"). Mapping them here means a role
     * granted a whole module also satisfies those finer checks, so nobody ends
     * up with a module in their sidebar whose own pages refuse to open.
     *
     * @var array<string, string|null>
     */
    private const LEGACY_MODULE_OF = [
        'student_portal' => null,
        'academics' => 'academics',
        'academic_ops' => 'academics',
        'exams' => 'exams',
        'digital_assessment' => 'exams',
        'attendance' => 'hr',
        'finance' => 'finance',
        'boarding' => 'boarding',
        'clinic' => 'health',
        'inventory' => 'inventory',
        'library' => 'library',
        'knowledge' => 'library',
        'communication' => 'communication',
        'admissions' => 'admissions',
        'lms' => 'lms',
        'website' => 'website',
        'reports' => 'reports',
        'hr' => 'hr',
        'users' => 'administration',
        'tasks' => 'communication',
        'administration' => 'administration',
        'saas' => 'saas',
    ];

    /**
     * Legacy capabilities every member of staff needs, whatever their role:
     * their own tasks, and access to their own profile.
     *
     * Deliberately no module-wide `view_module` key here. "Show this module"
     * implies every page inside it, so a self-service grant must be expressed
     * with the individual pages each role needs, or every member of staff would
     * be handed the whole module.
     *
     * @var array<int, string>
     */
    private const SELF_SERVICE_LEGACY = [
        'tasks.view',
        'tasks.create',
        'tasks.clear',
        'communication.contact_platform',
        'administration.manage_personal_details',
    ];

    /**
     * The 11 roles a school starts with, in the order they are offered on the
     * employee registration form.
     *
     * `modules` grants full access to entire modules.
     * `groups` grants full access to the named page groups within a module,
     * so a role can own one part of a module without owning all of it.
     * `except` names individual pages to hold back from those grants.
     * `portal` marks the one role that lives in the student panel.
     *
     * @return array<string, array{label: string, modules: array<int, string>, groups?: array<string, array<int, string>>, except?: array<int, string>, everything?: bool, portal?: bool, summary: string}>
     */
    public static function roles(): array
    {
        return [
            'administrator' => [
                'label' => 'System Administrator',
                'modules' => [],
                'everything' => true,
                'summary' => __('Runs the whole platform for this school: every module, every page, every operation, plus user accounts, roles and system settings.'),
            ],
            'school_administrator' => [
                'label' => 'School Administrator',
                'modules' => [
                    'communication', 'inventory', 'library', 'admissions', 'students', 'academics',
                ],
                'summary' => __('Runs the day-to-day school: communications, stock and purchasing, the library, admissions, the student body and academics.'),
            ],
            'teaching_staff' => [
                'label' => 'Teaching Staff',
                'modules' => ['academics'],
                'groups' => [
                    // The three Exams & Grading areas a teacher works in daily.
                    'exams' => ['Assessment Center', 'Grading & Marks Management', 'Reports & Academic Publishing'],
                ],
                'except' => [
                    // Teachers produce report cards; publishing them to the
                    // student portal is a separate office, so the page and every
                    // operation on it stay out of their hands by default.
                    'exams.publish_to_student_portal',
                ],
                'summary' => __('Teaches classes: academic structure, timetables and progression, plus assessments, grading and report cards.'),
            ],
            'accounts_finance' => [
                'label' => 'Accounts / Finance',
                'modules' => ['finance'],
                'summary' => __('Owns the money side of the school: dashboards and statements, student billing, expenses and purchasing, and the core accounting setup.'),
            ],
            'hr' => [
                'label' => 'HR',
                'modules' => ['hr'],
                'summary' => __('Owns the staff record: the staff directory, payroll and compensation, and attendance and leave.'),
            ],
            'health' => [
                'label' => 'Health',
                'modules' => ['health'],
                'summary' => __('Keeps student health records and logs clinic visits.'),
            ],
            'procurement' => [
                'label' => 'Procurement',
                'modules' => [],
                'groups' => [
                    // Procurement is the office that runs the publishing step, so
                    // unlike teaching staff it keeps the student-portal page.
                    'exams' => ['Assessment Center', 'Grading & Marks Management', 'Reports & Academic Publishing'],
                ],
                'summary' => __('Runs assessment publishing: the assessment centre, grading and marks management, and academic report publishing.'),
            ],
            'librarian' => [
                'label' => 'Librarian',
                'modules' => ['website'],
                'summary' => __('Designs and publishes the public website, including its templates and content.'),
            ],
            'houseparent' => [
                'label' => 'Houseparent',
                'modules' => ['boarding'],
                'summary' => __('Looks after boarders: accommodation and allocations, and daily welfare and care.'),
            ],
            'supporting_staff' => [
                'label' => 'Supporting Staff',
                'modules' => [],
                'summary' => __('General duties: their own day, tasks, calendar and leave, with no specialist module of their own. An administrator can add a module to suit the job.'),
            ],
            'student' => [
                'label' => 'Student',
                'modules' => [],
                'portal' => true,
                'summary' => __('Uses only the student portal: own timetable, results, fees and announcements.'),
            ],
        ];
    }

    /**
     * The roles offered on the employee registration form. Students are
     * registered through the student portal, never as employees, so the
     * student role is deliberately absent.
     *
     * @return array<string, string>
     */
    public static function employeeRoles(): array
    {
        $options = [];

        foreach (self::roles() as $key => $role) {
            if (! empty($role['portal'])) {
                continue;
            }

            $options[self::label($key)] = $role['label'];
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::roles());
    }

    public static function label(string $key): string
    {
        return self::roles()[$key]['label'] ?? 'Generic';
    }

    public static function summary(string $key): string
    {
        return self::roles()[$key]['summary'] ?? '';
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::roles());
    }

    /**
     * The role key that owns a role name, so a stored role can be matched back
     * to its catalogue entry regardless of how it was capitalised.
     */
    public static function keyForRoleName(?string $roleName): ?string
    {
        if (! $roleName) {
            return null;
        }

        $needle = mb_strtolower(trim($roleName));

        foreach (self::roles() as $key => $role) {
            if (mb_strtolower($role['label']) === $needle) {
                return $key;
            }
        }

        // Historic spellings kept working.
        return match ($needle) {
            'administrator', 'admin', 'system administrator' => 'administrator',
            'teacher', 'teachers', 'teaching', 'teaching staff' => 'teaching_staff',
            'accountant', 'accounts', 'accounts / finance', 'accounts/finance', 'finance' => 'accounts_finance',
            'non_teaching_staff', 'non-teaching staff', 'supporting', 'support staff' => 'supporting_staff',
            default => null,
        };
    }

    public static function isFullAccess(string $key): bool
    {
        return ! empty(self::roles()[$key]['everything']);
    }

    public static function isPortalOnly(string $key): bool
    {
        return ! empty(self::roles()[$key]['portal']);
    }

    /**
     * The default permissions for a role.
     *
     * Full-access roles receive the wildcard, which the permission resolver
     * treats as "everything, now and whatever is added later". Every other role
     * receives full access to the modules it owns, plus full access to the page
     * groups it is given within other modules, minus any page named in `except`,
     * plus the self-service set that every member of staff needs.
     *
     * @return array<int, string>
     */
    public static function permissionsFor(string $key): array
    {
        if (! self::exists($key)) {
            $key = 'supporting_staff';
        }

        if (self::isFullAccess($key)) {
            return ['*'];
        }

        if (self::isPortalOnly($key)) {
            return ['student_portal.access'];
        }

        return self::withoutExcludedPages(
            self::permissionsForWithoutExclusions($key),
            self::excludedPagesFor($key),
        );
    }

    /**
     * What a role would grant if its `except` list were empty.
     *
     * Exists so the role screen can name what is being deliberately held back.
     * Comparing the two sets is the only honest way to answer "what does this
     * role not get?", because a role can be denied a page it never owned anyway.
     *
     * @return array<int, string>
     */
    public static function permissionsForWithoutExclusions(string $key): array
    {
        $role = self::roles()[$key];

        return self::normalize(array_merge(
            CapabilityCatalog::fullAccessTo(...$role['modules']),
            self::groupKeys($role['groups'] ?? []),
            self::selfService(),
            self::legacyKeysFor($role['modules']),
            self::SELF_SERVICE_LEGACY,
        ));
    }

    /**
     * Full access to the named page groups of a module.
     *
     * A group is the unit the user actually thinks in ("Reports & Academic
     * Publishing"), and the permission editor shows groups as headings, so a
     * default role is described the same way an administrator would build it by
     * hand.
     *
     * @param  array<string, array<int, string>>  $groups
     * @return array<int, string>
     */
    private static function groupKeys(array $groups): array
    {
        $keys = [];

        foreach ($groups as $moduleSlug => $groupLabels) {
            foreach ($groupLabels as $groupLabel) {
                foreach (CapabilityCatalog::pageKeysInGroup($moduleSlug, $groupLabel) as $pageKey) {
                    $keys = array_merge($keys, CapabilityCatalog::fullAccessToPage($moduleSlug, $pageKey));
                }
            }
        }

        return $keys;
    }

    /**
     * Strip every key belonging to a held-back page.
     *
     * This has to happen after the keys are collected rather than by simply not
     * adding the page, because a module-wide grant implies the same operation on
     * every page of that module. The only dependable way to keep a page out of a
     * role that otherwise owns the module is to remove its keys afterwards.
     *
     * @param  array<int, string>  $keys
     * @param  array<int, string>  $excluded  "module.page_key" strings.
     * @return array<int, string>
     */
    private static function withoutExcludedPages(array $keys, array $excluded): array
    {
        if ($excluded === []) {
            return $keys;
        }

        $prefixes = [];

        foreach ($excluded as $pageKey) {
            $prefixes[] = $pageKey.'.';
        }

        return array_values(array_filter(
            $keys,
            static fn (string $key): bool => ! in_array(
                true,
                array_map(
                    static fn (string $prefix): bool => str_starts_with($key, $prefix),
                    $prefixes,
                ),
                true,
            ),
        ));
    }

    /**
     * The older, finer-grained capabilities that belong to the given modules,
     * so a whole-module grant also satisfies screens that still ask for one.
     *
     * @param  array<int, string>  $moduleSlugs
     * @return array<int, string>
     */
    public static function legacyKeysFor(array $moduleSlugs): array
    {
        $keys = [];

        foreach (PermissionRegistry::getGranularMatrix() as $namespace => $config) {
            $owner = self::LEGACY_MODULE_OF[$namespace] ?? null;

            if ($owner === null || ! in_array($owner, $moduleSlugs, true)) {
                continue;
            }

            foreach (array_keys($config['actions']) as $action) {
                $keys[] = $namespace.'.'.$action;
            }
        }

        return $keys;
    }

    /**
     * The legacy capabilities granted to every member of staff.
     *
     * @return array<int, string>
     */
    public static function selfServiceLegacy(): array
    {
        return self::SELF_SERVICE_LEGACY;
    }

    /**
     * The page groups a role owns inside modules it does not own outright, keyed by
     * module slug. Used by the role permission screen to show "Reports & Academic
     * Publishing" as the unit an administrator grants, rather than a page list.
     *
     * @return array<string, array<int, string>>
     */
    public static function groupsFor(string $key): array
    {
        $role = self::roles()[$key] ?? null;

        return $role['groups'] ?? [];
    }

    /**
     * Every module that any role reaches only through named groups, mapped to the
     * group labels used across the catalogue. A group label that does not exist in
     * its module can then be reported instead of quietly granting nothing.
     *
     * @return array<string, array<int, string>>
     */
    public static function groupsWithModules(): array
    {
        $map = [];

        foreach (self::roles() as $role) {
            foreach ($role['groups'] ?? [] as $moduleSlug => $labels) {
                foreach ($labels as $label) {
                    $map[$moduleSlug][] = $label;
                }
            }
        }

        foreach ($map as $moduleSlug => $labels) {
            $map[$moduleSlug] = array_values(array_unique($labels));
        }

        return $map;
    }

    /**
     * Pages a role is deliberately kept out of, as "module.page_key" strings.
     *
     * @return array<int, string>
     */
    public static function excludedPagesFor(string $key): array
    {
        $role = self::roles()[$key] ?? null;

        return $role['except'] ?? [];
    }

    /**
     * The navigation modules a role opens by default: whole modules plus any module
     * it reaches through named groups.
     *
     * @return array<int, string>
     */
    public static function modulesFor(string $key): array
    {
        $role = self::roles()[$key] ?? null;

        if (! $role) {
            return [];
        }

        if (! empty($role['everything'])) {
            return array_values(array_filter(
                array_keys(CapabilityCatalog::modules()),
                fn (string $slug): bool => $slug !== 'universal',
            ));
        }

        return array_values(array_unique(array_merge(
            $role['modules'],
            array_keys($role['groups'] ?? []),
        )));
    }

    /**
     * What every member of staff may do for themselves, whatever their role.
     *
     * A teacher who cannot open the HR module still has to be able to apply for
     * leave, work through their own day, run their own tasks and edit their own
     * profile, so these are granted to every non-student role.
     *
     * @return array<int, string>
     */
    public static function selfService(): array
    {
        return [
            // Their own day, tasks and calendar.
            'universal.dashboard.view',
            'universal.my_day.view',
            'universal.my_day.create',
            'universal.my_day.edit',
            'universal.my_day.delete',
            'universal.schedule.view',
            'universal.schedule.create',
            'universal.schedule.edit',
            'universal.schedule.delete',
            'universal.schedule.export',

            // Their own leave and attendance.
            'hr.leave_requests.view',
            'hr.leave_requests.create',
            'hr.leave_requests.edit',

            // Their own record in the staff directory, and their own attendance.
            'hr.employees.view',
            'hr.staff_attendance.view',
            'hr.staff_attendance.create',

            // Notices and conversations.
            'communication.announcements.view',
            'communication.chat.view',
            'communication.chat.create',
            'communication.helpdesk.view',
            'communication.helpdesk.create',
            'communication.schedule_tasks.view',
            'communication.schedule_tasks.create',
            'communication.schedule_tasks.edit',
            'communication.schedule_tasks.delete',
        ];
    }

    /**
     * A human summary of a permission list, used in the permission editor so an
     * administrator can see at a glance what a role covers.
     *
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    public static function summarise(array $permissions): array
    {
        if (in_array('*', $permissions, true)) {
            return [__('Everything — every module, page and operation.')];
        }

        if (in_array('student_portal.access', $permissions, true)) {
            return [__('Student portal only.')];
        }

        $granted = [];

        foreach (CapabilityCatalog::modules() as $module) {
            if ($module['key'] === 'universal') {
                continue;
            }

            foreach ($module['pages'] as $page) {
                $hasAny = false;

                foreach ($page['permissions'] as $key) {
                    if (in_array($key, $permissions, true)) {
                        $hasAny = true;
                        break;
                    }
                }

                if ($hasAny) {
                    $granted[] = $module['label'].' — '.$page['label'];
                }
            }
        }

        return $granted;
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    private static function normalize(array $permissions): array
    {
        return array_values(array_unique(array_filter($permissions)));
    }
}
