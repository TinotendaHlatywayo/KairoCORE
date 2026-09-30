<?php

namespace Modules\Admin\Services;

use App\Models\User;
use App\Security\CapabilityCatalog;
use App\Security\RoleCatalogue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\Department;

/**
 * Answers one question everywhere in the system: may this person do this?
 *
 * Resolution order for a single permission key:
 *
 *   1. Platform super-admins always may.
 *   2. A role or user holding the wildcard `*` may do anything, now and later.
 *   3. An exact match on the key always wins.
 *   4. A page key (`finance.fee_structures.edit`) is satisfied by the matching
 *      module-wide key (`finance.edit`), so granting a whole module covers every
 *      page inside it — including pages added later.
 *   5. A page's `view` is satisfied by the module's `view_module` master switch.
 *
 * A user's own list, when one has been saved, replaces their role's list
 * entirely. That is what lets an administrator widen a single teacher into HR
 * without touching anybody else, and narrow a role without touching the rest of
 * the team.
 */
class PermissionRegistry
{
    /**
     * The wildcard held by roles with unrestricted access. It is a permission
     * value like any other, so it survives round-tripping through a role record
     * or a per-user snapshot.
     */
    public const WILDCARD = '*';

    /**
     * Older, finer-grained capability namespaces. These predate the module →
     * category → page tree and are still asked for by a handful of screens, so
     * they remain valid permission keys and are granted alongside the modules
     * that own them.
     *
     * Every key returned here is a real, enforceable permission. Keys that are
     * referenced by application code MUST exist in this matrix, otherwise they
     * silently fail closed for every non-administrator account.
     */
    public static function getGranularMatrix(): array
    {
        return [
            'student_portal' => [
                'label' => __('Student Portal'),
                'actions' => [
                    'access' => 'Access Personal Student Portal',
                ],
            ],
            'academics' => [
                'label' => __('Academics & SIS'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'view_records' => 'View Academic Records',
                    'create' => 'Onboard Students / Setup Levels',
                    'edit' => 'Modify Enrolment Data',
                    'delete' => 'Remove Students',
                    'promote' => 'Process Academic Promotions & Screenings',
                    'import' => 'Bulk Import CSV Lists',
                    'export' => 'Export Academic Directories',
                ],
            ],
            'academic_ops' => [
                'label' => __('Academic Operations Workflow'),
                'actions' => [
                    'view' => 'Access Operations Center',
                    'manage_workflow' => 'Override Workflow Steps (Mark Complete / Skip / Reset)',
                    'manage_calendar' => 'Manage Academic Years & Terms',
                    'manage_curriculum' => 'Manage Levels, Forms & Streams',
                    'manage_subjects' => 'Manage Subjects',
                    'manage_classrooms' => 'Manage Classrooms',
                    'manage_timetable' => 'Manage Time Slots & Timetable',
                    'manage_assessments' => 'Manage Grading Scales & Assessments',
                    'manage_admissions' => 'Manage Applications',
                    'manage_enrolment' => 'Manage Student Enrolment',
                    'manage_reports' => 'Manage Report Templates',
                ],
            ],
            'exams' => [
                'label' => __('Exams & Grading'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'enter_marks' => 'Enter Assessment Marks',
                    'approve_results' => 'Approve Subject Results',
                    'generate_reports' => 'Generate Academic Report Cards',
                    'bypass_lock' => 'Modify Locked Grades',
                ],
            ],
            'attendance' => [
                'label' => __('Attendance & Conduct'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'record' => 'Record Daily Attendance',
                    'manage' => 'Approve / Override Attendance Records',
                    'export' => 'Export Attendance Registers',
                ],
            ],
            'finance' => [
                'label' => __('Finance & Cohort Billing'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'manage_fees' => 'Configure Fee Structures & Waivers',
                    'bill_cohorts' => 'Run Bulk Auto-Invoicing',
                    'receive_payments' => 'Record Cash & Bank Payments',
                    'reverse_payments' => 'Authorize Financial Reversals',
                    'manage_student_financial_history' => 'Record / Edit / Import Student Financial History',
                    'view_reports' => 'View Balance Sheets & Defaulters Ledger',
                ],
            ],
            'boarding' => [
                'label' => __('Boarding & Welfare'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'allocate_rooms' => 'Process Bed Assignments',
                    'take_roll_call' => 'Record Daily Attendances',
                    'issue_out_passes' => 'Verify Out-Pass Codes',
                    'inspect_dorms' => 'Record Room Inspections',
                ],
            ],
            'clinic' => [
                'label' => __('Clinic & Health'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'view_medical_profiles' => 'View Patient Files',
                    'record_visits' => 'Log Outpatient Visits',
                    'dispense_drugs' => 'Dispense Prescription Inventory',
                ],
            ],
            'inventory' => [
                'label' => __('Inventory, Assets & Procurement'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'manage_catalog' => 'Edit Master Catalog & Batches',
                    'issue_stock' => 'Issue Consumables & Equipment',
                    'audit_stock' => 'Run Physical Stocktakes',
                    'depreciate_assets' => 'Compute Fixed Asset Schedules',
                    'manage_procurement' => 'Manage LPOs & Goods Received (GRN)',
                ],
            ],
            'library' => [
                'label' => __('Library & Resources'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'issue_books' => 'Issue, Return & Renew Books',
                    'manage_catalog' => 'Manage Catalogue & Copies',
                    'manage_fines' => 'Waive / Adjust Library Fines',
                ],
            ],
            'communication' => [
                'label' => __('Communication & Engagement Hub'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'post_announcements' => 'Publish Notice Board Alerts',
                    'manage_helpdesk' => 'Process Service Tickets',
                    'create_polls' => 'Manage Polls & Surveys',
                    'contact_platform' => 'Communicate with Kairo CORE Platform Support',
                ],
            ],
            'admissions' => [
                'label' => __('Admissions'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'manage_applications' => 'Process Applications & Interviews',
                    'approve_applications' => 'Approve & Admit Applicants',
                    'export' => 'Export Application Pipeline',
                    'receive_notifications' => 'Receive New Application & Enrolment Notifications',
                ],
            ],
            'lms' => [
                'label' => __('LMS & E-Learning'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'manage_content' => 'Manage Lessons, Assignments & Resources',
                    'grade_submissions' => 'Grade & Comment on Submissions',
                    'export' => 'Export LMS Analytics',
                ],
            ],
            'knowledge' => [
                'label' => __('Knowledge Hub'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'contribute' => 'Add & Publish Resources',
                    'moderate' => 'Moderate & Approve Content',
                    'export' => 'Export Repository',
                ],
            ],
            'website' => [
                'label' => __('Website & CMS'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'manage_pages' => 'Publish & Edit Pages',
                    'preview' => 'Preview Published Content',
                    'manage_settings' => 'Configure Website Settings',
                ],
            ],
            'reports' => [
                'label' => __('Reports & Intelligence'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'generate' => 'Generate & Run Reports',
                    'manage_templates' => 'Create & Edit Report Templates',
                    'export' => 'Export Data Files',
                    'schedule' => 'Schedule & Distribute Reports',
                ],
            ],
            'hr' => [
                'label' => __('Human Resources & Payroll'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'manage_employees' => 'Manage Employee Records',
                    'manage_payroll' => 'Process & Approve Payroll',
                    'manage_leaves' => 'Approve Leave Requests',
                    'manage_disciplinary' => 'Manage Disciplinary Cases',
                    'view_reports' => 'View HR & Payroll Reports',
                ],
            ],
            'users' => [
                'label' => __('User Accounts & Registration Approval'),
                'actions' => [
                    'approve' => 'Approve New User Registrations',
                    'reject' => 'Reject New User Registrations',
                ],
            ],
            'tasks' => [
                'label' => __('Task Manager'),
                'actions' => [
                    'view' => 'View Personal Tasks',
                    'create' => 'Create Personal Tasks',
                    'assign' => 'Assign Tasks to Other Users',
                    'clear' => 'Clear Completed Tasks',
                ],
            ],
            'administration' => [
                'label' => __('System Administration'),
                'actions' => [
                    'view_module' => 'Access System Settings',
                    'manage_users' => 'Manage Directory Accounts',
                    'manage_settings' => 'Manage System Settings & Preferences',
                    'manage_security' => 'Configure Security & Auths',
                    'manage_branding' => 'Customize Themes & Logos',
                    'manage_email_config' => 'Configure School Email Sending',
                    'clear_caches' => 'Run Application Maintenance',
                    'manage_personal_details' => 'Edit Own Name / Email / Phone on Profile',
                ],
            ],
            'saas' => [
                'label' => __('Subscription & Billing'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'manage_subscription' => 'Manage Subscription & Billing',
                    'contact_billing' => 'Contact Billing Support',
                ],
            ],
            'digital_assessment' => [
                'label' => __('Digital Assessment & Gamification'),
                'actions' => [
                    'view_module' => 'Access Digital Assessment Module',
                    'manage_questions' => 'Create & Edit Question Bank',
                    'import_questions' => 'Import Question Banks',
                    'create_assessments' => 'Create Digital Assessments',
                    'publish_assessments' => 'Publish & Manage Assessments',
                    'view_results' => 'View Assessment Results',
                    'mark_assessments' => 'Mark Subjective Questions',
                    'export_results' => 'Export Assessment Results',
                    'manage_gamification' => 'Configure Gamification Settings',
                    'manage_badges' => 'Create & Manage Badges',
                    'manage_challenges' => 'Create & Manage Challenges',
                    'view_leaderboards' => 'View Leaderboards',
                ],
            ],
        ];
    }

    /**
     * Flatten the legacy matrix into its permission keys.
     *
     * @return array<int, string>
     */
    public static function legacyPermissionKeys(): array
    {
        $permissions = [];

        foreach (self::getGranularMatrix() as $module => $config) {
            foreach ($config['actions'] as $action => $label) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        return $permissions;
    }

    /**
     * Every permission that exists in the system: the legacy matrix plus every
     * page and operation in the module → category → page tree.
     *
     * @return array<int, string>
     */
    public static function collectAllPermissionKeys(): array
    {
        return array_values(array_unique(array_merge(
            self::legacyPermissionKeys(),
            CapabilityCatalog::allPermissionKeys(),
        )));
    }

    /**
     * Flat `permission key => label` option map used by the role, department
     * and per-user permission pickers.
     *
     * @return array<string, string>
     */
    public static function permissionOptions(): array
    {
        $options = [];

        foreach (self::getGranularMatrix() as $module => $config) {
            foreach ($config['actions'] as $action => $label) {
                $options["{$module}.{$action}"] = strip_tags(__($config['label'])).' — '.$label;
            }
        }

        foreach (self::navigationPermissionOptions() as $key => $label) {
            $options[$key] = $label;
        }

        return $options;
    }

    /**
     * The module → category → page permissions, flattened for the pickers.
     *
     * @return array<string, string>
     */
    public static function navigationPermissionOptions(): array
    {
        $options = [];

        foreach (CapabilityCatalog::modules() as $module) {
            if ($module['key'] === 'universal') {
                continue;
            }

            foreach ($module['pages'] as $page) {
                $prefix = $module['label'].' — '.$page['group'].' — '.$page['label'];

                foreach ($page['actions'] as $index => $action) {
                    $options[$page['permissions'][$index]] = $prefix.' — '.CapabilityCatalog::actionLabel($action);
                }
            }
        }

        return $options;
    }

    /**
     * Detailed descriptions for every permission, displayed as helper tooltips
     * under checkboxes in System Administration. Page permissions explain what
     * the page is for and what the operation unlocks on it.
     *
     * @return array<string, string>
     */
    public static function permissionDescriptions(): array
    {
        $descriptions = [];

        foreach (self::getGranularMatrix() as $module => $config) {
            $modLabel = strip_tags(__($config['label']));
            foreach ($config['actions'] as $action => $label) {
                $descriptions["{$module}.{$action}"] = __('Grants authorization to :action in the :module module (CRUD & operational privilege).', [
                    'action' => mb_strtolower($label),
                    'module' => $modLabel,
                ]);
            }
        }

        foreach (CapabilityCatalog::allPermissionKeys() as $key) {
            $descriptions[$key] = CapabilityCatalog::describe($key);
        }

        return $descriptions;
    }

    /**
     * Centralized and self-healing permission verification system.
     */
    public static function checkPermission(string $permission): bool
    {
        return self::checkAny([$permission]);
    }

    /**
     * May the signed-in person do any one of these?
     *
     * Used for category hubs, where reaching the landing page means being able
     * to open at least one of the pages behind it.
     *
     * @param  array<int, string>  $permissions
     */
    public static function checkAny(array $permissions): bool
    {
        if (! Auth::check()) {
            return false;
        }

        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $schoolId = session('current_tenant')?->id ?? $user->school_id;

        // Auto-provision the Administrator role for a school's founder if it is
        // still missing (safe-guarded — never auto-promotes ordinary accounts).
        self::ensureAdminHasRole($user, $schoolId);

        return self::userCanAny($user, $permissions);
    }

    /**
     * Evaluate a permission for a specific user record without touching the
     * session. Used by notification routing and direct model checks.
     */
    public static function userCan(?User $user, string $permission): bool
    {
        return self::userCanAny($user, [$permission]);
    }

    /**
     * Evaluate several alternative permissions for a specific user record.
     *
     * @param  array<int, string>  $permissions
     */
    public static function userCanAny(?User $user, array $permissions): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return self::isGrantedAny(self::permissionsFor($user), $permissions);
    }

    /**
     * The permission list that actually applies to a user: their own snapshot
     * when one has been saved, otherwise their role's list.
     *
     * An empty snapshot is treated as "no personal override" rather than
     * "no permissions at all", so an account can never be silently locked out
     * of its own workspace by an approval flow that saved nothing.
     *
     * @return array<int, string>
     */
    public static function permissionsFor(User $user): array
    {
        if (is_array($user->permissions) && $user->permissions !== []) {
            return array_values($user->permissions);
        }

        if (! $user->custom_role_id) {
            return [];
        }

        $role = CustomRole::find($user->custom_role_id);

        if (! $role) {
            return [];
        }

        return array_values($role->permissions ?? []);
    }

    /**
     * Does a permission list satisfy a permission key?
     *
     * This is the single place the implication rules live, so the permission
     * editor and the runtime gate can never disagree about what a role grants.
     *
     * @param  array<int, string>  $permissions
     */
    public static function isGranted(array $permissions, string $permission): bool
    {
        if ($permissions === [] || $permission === '') {
            return false;
        }

        if (in_array(self::WILDCARD, $permissions, true)) {
            return true;
        }

        if (in_array($permission, $permissions, true)) {
            return true;
        }

        // A page capability is covered by the same capability granted module-wide.
        $segments = explode('.', $permission);

        if (count($segments) === 3) {
            [$module, $page, $action] = $segments;

            if (in_array("{$module}.{$action}", $permissions, true)) {
                return true;
            }

            // "Show this module" means "show every page inside it".
            if ($action === 'view' && in_array("{$module}.view_module", $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does a permission list satisfy any of the given keys?
     *
     * @param  array<int, string>  $permissions
     * @param  array<int, string>  $candidates
     */
    public static function isGrantedAny(array $permissions, array $candidates): bool
    {
        foreach ($candidates as $candidate) {
            if (self::isGranted($permissions, $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Auto-provisions the Administrator role and assigns it to the user only when
     * the account is legitimately a school administrator [1].
     *
     * Safety: an account is only auto-promoted if it was registered under the
     * "administrator" category, or it is the very first account created for the
     * school (the pre-registration-workflow founder). Every other account must be
     * granted a role by an administrator during approval — never implicitly.
     */
    public static function ensureAdminHasRole(mixed $user, mixed $schoolId): bool
    {
        if (! $user || ! $schoolId) {
            return false;
        }

        if ($user->custom_role_id !== null) {
            $role = CustomRole::find($user->custom_role_id);
            if ($role && self::roleHasUnrestrictedAccess($role)) {
                self::reconcileAdministratorPermissions($role);
            }

            return true;
        }

        $isAdministratorCategory = in_array($user->requested_role ?? null, ['administrator'], true);
        $isLegacyFounder = $user->requested_role === null
            && (int) User::query()->where('school_id', $schoolId)->min('id') === (int) $user->id;

        if (! $isAdministratorCategory && ! $isLegacyFounder) {
            return false;
        }

        // Check if the administrator role already exists for this school
        $adminRole = CustomRole::where('school_id', $schoolId)
            ->whereIn('name', ['Administrator', 'System Administrator'])
            ->first();

        if (! $adminRole) {
            $adminRole = CustomRole::create([
                'school_id' => $schoolId,
                'name' => 'Administrator',
                'description' => __('Platform-seeded administrative role with complete authorization clearance.'),
                'permissions' => [self::WILDCARD],
                'is_system' => true,
            ]);
        }

        // Directly update user column to prevent relationship mapping crashes
        $user->custom_role_id = $adminRole->id;
        $user->save();

        return true;
    }

    /**
     * Whether a role is an administrator role — it holds the wildcard, or it is
     * the school's system administrator by name.
     */
    public static function roleHasUnrestrictedAccess(?CustomRole $role): bool
    {
        if (! $role) {
            return false;
        }

        if (in_array(self::WILDCARD, $role->permissions ?? [], true)) {
            return true;
        }

        return RoleCatalogue::keyForRoleName($role->name) === 'administrator';
    }

    /**
     * Bring a system role up to date with the capability catalogue.
     *
     * Full-access roles only ever need the wildcard. Every other system role is
     * left alone apart from the capabilities implied by the module-wide keys it
     * already holds, which is why a role granted "Academics — everything"
     * automatically covers pages added to Academics later.
     */
    protected static function reconcileAdministratorPermissions(CustomRole $role): void
    {
        if (in_array(self::WILDCARD, $role->permissions ?? [], true)) {
            return;
        }

        if (RoleCatalogue::keyForRoleName($role->name) === 'administrator') {
            $role->permissions = [self::WILDCARD];
            $role->save();

            return;
        }

        $existing = $role->permissions ?? [];
        $missing = array_values(array_diff(self::collectAllPermissionKeys(), $existing));

        if (! empty($missing)) {
            $role->permissions = array_values(array_unique(array_merge($existing, $missing)));
            $role->save();
        }
    }

    public static function moduleKeys(string $module): array
    {
        return CapabilityCatalog::modulePermissionKeys($module);
    }

    /**
     * The default permission bundle for a role category.
     *
     * These are the capabilities a person is given when the role is first
     * assigned. An administrator may then widen or narrow them for the role, or
     * for one individual, without any of this changing.
     *
     * @return array<int, string>
     */
    public static function defaultPermissionsForRole(string $category): array
    {
        if (! RoleCatalogue::exists($category)) {
            $category = 'supporting_staff';
        }

        return RoleCatalogue::permissionsFor($category);
    }

    /**
     * The full default bundle for a brand-new school, so the platform
     * administrator role can be created with full clearance.
     *
     * @return array<int, string>
     */
    public static function fullAccessPermissions(): array
    {
        return [self::WILDCARD];
    }

    /**
     * Union of the default permissions contributed by every department the user
     * is a member of (e.g. Clinic, Inventory & Assets, Finance). Empty for users
     * with no department membership.
     *
     * @return array<int, string>
     */
    public static function departmentPermissions(User $user): array
    {
        $permissions = [];

        foreach ($user->departments()->get() as $department) {
            foreach ($department->permissions ?? [] as $permission) {
                $permissions[] = $permission;
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * The default permission set that should be pre-ticked for a user: the role
     * defaults for their requested category combined with any department
     * defaults. Used when configuring a new account before approval.
     *
     * @return array<int, string>
     */
    public static function defaultPermissionsForUser(User $user): array
    {
        $permissions = [];

        if ($user->requested_role) {
            $permissions = self::defaultPermissionsForRole($user->requested_role);
        }

        return self::normalizePermissionList(array_merge($permissions, self::departmentPermissions($user)));
    }

    /**
     * The effective permission list for a user for display: their explicit
     * snapshot when one exists, otherwise the role + department defaults.
     *
     * @return array<int, string>
     */
    public static function effectivePermissionsForUser(User $user): array
    {
        if (is_array($user->permissions) && $user->permissions !== []) {
            return $user->permissions;
        }

        return self::defaultPermissionsForUser($user);
    }

    /**
     * Merge a set of permission keys into a clean, de-duplicated array, keeping
     * only keys that exist in the catalogue or the legacy matrix so a role can
     * never carry a typo that silently grants nothing.
     *
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    public static function normalizePermissionList(array $permissions): array
    {
        $known = array_flip(self::collectAllPermissionKeys());

        $clean = [];

        foreach ($permissions as $permission) {
            if (! is_string($permission)) {
                continue;
            }

            $permission = trim($permission);

            if ($permission === self::WILDCARD || isset($known[$permission])) {
                $clean[] = $permission;
            }
        }

        return array_values(array_unique($clean));
    }

    /**
     * Turn a permission key into the page it belongs to, for grouping and for
     * deciding whether a URL is reachable.
     *
     * @return array{0: string, 1: string}|null [module slug, page key]
     */
    public static function pageKeyFor(string $permission): ?array
    {
        $segments = explode('.', $permission);

        if (count($segments) !== 3) {
            return null;
        }

        return [$segments[0], $segments[1]];
    }

    /**
     * The module a permission key belongs to, or null for a legacy key whose
     * namespace does not match a navigation module.
     */
    public static function moduleKeyFor(string $permission): ?string
    {
        $module = explode('.', $permission)[0] ?? null;

        return $module !== null && CapabilityCatalog::module($module) !== null ? $module : null;
    }

    /**
     * A tidy label for a permission key, safe to show anywhere.
     */
    public static function labelFor(string $permission): string
    {
        if ($permission === self::WILDCARD) {
            return __('Everything');
        }

        return PermissionRegistry::permissionOptions()[$permission]
            ?? PermissionRegistry::permissionDescriptions()[$permission]
            ?? $permission;
    }
}
