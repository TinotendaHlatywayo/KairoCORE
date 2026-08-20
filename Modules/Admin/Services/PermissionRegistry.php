<?php

namespace Modules\Admin\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\Department;

class PermissionRegistry
{
    /**
     * Returns an explicit list of core modules and granular access privileges.
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
                    'contact_platform' => 'Communicate with SchoolCore Platform Support',
                ],
            ],
            'admissions' => [
                'label' => __('Admissions'),
                'actions' => [
                    'view_module' => 'Access Module',
                    'manage_applications' => 'Process Applications & Interviews',
                    'approve_applications' => 'Approve & Admit Applicants',
                    'export' => 'Export Application Pipeline',
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
        ];
    }

    /**
     * Flatten the granular matrix into the full list of permission keys.
     *
     * @return array<int, string>
     */
    public static function collectAllPermissionKeys(): array
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
     * Flat `module.action => "Module Label — Action Label"` option map used by
     * the role, department and per-user permission pickers. A single option map
     * guarantees the whole permission array round-trips through one field.
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

        return $options;
    }

    /**
     * Centralized and self-healing permission verification system.
     */
    public static function checkPermission(string $permission): bool
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

        return self::userCan($user, $permission);
    }

    /**
     * Evaluate a permission for a specific user record without touching the
     * session. Used by notification routing and direct model checks.
     */
    public static function userCan(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        // An explicit per-user permission snapshot (set by an administrator
        // during approval or user management) wins over the role entirely.
        if (is_array($user->permissions)) {
            return in_array($permission, $user->permissions, true);
        }

        if (! $user->custom_role_id) {
            return false;
        }

        // Fetch custom role safely without requiring relationships defined inside User.php
        $role = CustomRole::find($user->custom_role_id);
        if (! $role || ! $role->permissions) {
            return false;
        }

        // Master administrators bypass granular capability blocks cleanly [1]
        if ($role->name === 'Administrator') {
            return true;
        }

        return in_array($permission, $role->permissions, true);
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
            if ($role && $role->name === 'Administrator') {
                self::reconcileAdministratorPermissions($role);
            }

            return true;
        }

        $isAdministratorCategory = ($user->requested_role ?? null) === 'administrator';
        $isLegacyFounder = $user->requested_role === null
            && (int) User::query()->where('school_id', $schoolId)->min('id') === (int) $user->id;

        if (! $isAdministratorCategory && ! $isLegacyFounder) {
            return false;
        }

        // Check if "Administrator" role already exists for this school
        $adminRole = CustomRole::where('school_id', $schoolId)->where('name', 'Administrator')->first();

        if (! $adminRole) {
            $adminRole = CustomRole::create([
                'school_id' => $schoolId,
                'name' => 'Administrator',
                'description' => __('Platform-seeded administrative role with complete authorization clearance.'),
                'permissions' => self::collectAllPermissionKeys(),
                'is_system' => true,
            ]);
        } else {
            self::reconcileAdministratorPermissions($adminRole);
        }

        // Directly update user column to prevent relationship mapping crashes
        $user->custom_role_id = $adminRole->id;
        $user->save();

        return true;
    }

    /**
     * Merge any permissions added into the granular matrix since the role was
     * seeded into the Administrator role, preserving the original permissions.
     */
    protected static function reconcileAdministratorPermissions(CustomRole $role): void
    {
        $existing = $role->permissions ?? [];
        $missing = array_values(array_diff(self::collectAllPermissionKeys(), $existing));

        if (! empty($missing)) {
            $role->permissions = array_values(array_unique(array_merge($existing, $missing)));
            $role->save();
        }
    }

    /**
     * Sensible default permission bundle per requested registration category.
     * These are the checkboxes the approver sees pre-ticked; departments for
     * non-teaching staff extend them further.
     *
     * @return array<int, string>
     */
    public static function defaultPermissionsForRole(string $category): array
    {
        return match ($category) {
            'student' => [
                'student_portal.access',
            ],
            'teaching_staff' => [
                'academics.view_records',
                'academics.edit',
                'academic_ops.view',
                'academic_ops.manage_assessments',
                'attendance.view_module',
                'attendance.record',
                'exams.view_module',
                'exams.enter_marks',
                'exams.approve_results',
                'exams.generate_reports',
                'library.view_module',
                'communication.view_module',
                'communication.post_announcements',
                'lms.view_module',
                'lms.manage_content',
                'lms.grade_submissions',
                'knowledge.view_module',
                'knowledge.contribute',
                'reports.view_module',
                'reports.generate',
                'tasks.view',
                'tasks.create',
            ],
            'non_teaching_staff' => [
                'communication.view_module',
                'reports.view_module',
                'reports.generate',
                'tasks.view',
                'tasks.create',
            ],
            'administrator' => self::collectAllPermissionKeys(),
            default => self::collectAllPermissionKeys(),
        };
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
     * defaults for their requested category combined with any department defaults.
     * Used when configuring a new account before approval.
     *
     * @return array<int, string>
     */
    public static function defaultPermissionsForUser(User $user): array
    {
        $permissions = [];

        if ($user->requested_role) {
            $permissions = self::defaultPermissionsForRole($user->requested_role);
        }

        return array_values(array_unique(array_merge($permissions, self::departmentPermissions($user))));
    }

    /**
     * The effective permission list for a user for display: their explicit
     * snapshot when one exists, otherwise the role + department defaults.
     *
     * @return array<int, string>
     */
    public static function effectivePermissionsForUser(User $user): array
    {
        if (is_array($user->permissions)) {
            return $user->permissions;
        }

        return self::defaultPermissionsForUser($user);
    }

    /**
     * Merge a set of permission keys into a clean, de-duplicated array.
     *
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    public static function normalizePermissionList(array $permissions): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($p) => is_string($p) ? trim($p) : null,
            $permissions,
        ))));
    }
}
