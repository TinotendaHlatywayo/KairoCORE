<?php

namespace Modules\Admin\Services;

use App\Models\School;
use Modules\Admin\Models\Department;

/**
 * Built-in department templates with their default permission bundles.
 *
 * When an administrator provisions a school's departments (or assigns a
 * non-teaching staff member to a department), the department's permission
 * bundle is applied to the member automatically — and can be further
 * customised per user at approval time.
 */
class DepartmentPermissionPresets
{
    /**
     * @return array<string, array{name: string, code: string, type: string, permissions: array<int, string>}>
     */
    public static function all(): array
    {
        return [
            'finance' => [
                'name' => 'Finance & Bursary',
                'code' => 'FIN',
                'type' => 'administrative',
                'permissions' => [
                    'finance.view_module', 'finance.manage_fees', 'finance.bill_cohorts',
                    'finance.receive_payments', 'finance.reverse_payments', 'finance.view_reports',
                    'reports.view_module', 'reports.generate', 'administration.view_module',
                ],
            ],
            'clinic' => [
                'name' => 'Clinic & Health',
                'code' => 'CLN',
                'type' => 'support',
                'permissions' => [
                    'clinic.view_module', 'clinic.view_medical_profiles', 'clinic.record_visits',
                    'clinic.dispense_drugs', 'inventory.view_module', 'inventory.issue_stock',
                    'reports.view_module', 'reports.generate',
                ],
            ],
            'inventory' => [
                'name' => 'Inventory & Assets',
                'code' => 'INV',
                'type' => 'administrative',
                'permissions' => [
                    'inventory.view_module', 'inventory.manage_catalog', 'inventory.issue_stock',
                    'inventory.audit_stock', 'inventory.depreciate_assets', 'inventory.manage_procurement',
                    'reports.view_module', 'reports.generate',
                ],
            ],
            'library' => [
                'name' => 'Library & Media',
                'code' => 'LIB',
                'type' => 'support',
                'permissions' => [
                    'library.view_module', 'library.issue_books', 'library.manage_catalog',
                    'library.manage_fines', 'reports.view_module',
                ],
            ],
            'boarding' => [
                'name' => 'Boarding & Welfare',
                'code' => 'BRD',
                'type' => 'support',
                'permissions' => [
                    'boarding.view_module', 'boarding.allocate_rooms', 'boarding.take_roll_call',
                    'boarding.issue_out_passes', 'boarding.inspect_dorms', 'reports.view_module',
                ],
            ],
            'academics' => [
                'name' => 'Academics & Curriculum',
                'code' => 'ACA',
                'type' => 'academic',
                'permissions' => [
                    'academics.view_module', 'academics.view_records', 'academics.create',
                    'academics.edit', 'academics.promote',
                    'academic_ops.view', 'academic_ops.manage_curriculum', 'academic_ops.manage_subjects',
                    'academic_ops.manage_enrolment', 'attendance.view_module', 'attendance.record',
                    'exams.view_module', 'exams.enter_marks', 'reports.view_module', 'reports.generate',
                ],
            ],
            'examinations' => [
                'name' => 'Examinations & Assessment',
                'code' => 'EXA',
                'type' => 'academic',
                'permissions' => [
                    'exams.view_module', 'exams.enter_marks', 'exams.approve_results',
                    'exams.generate_reports', 'exams.bypass_lock', 'reports.view_module', 'reports.generate',
                ],
            ],
            'ict' => [
                'name' => 'ICT & Systems',
                'code' => 'ICT',
                'type' => 'support',
                'permissions' => [
                    'administration.view_module', 'administration.manage_settings',
                    'administration.manage_security', 'administration.clear_caches',
                    'reports.view_module', 'reports.generate',
                ],
            ],
            'hr' => [
                'name' => 'Human Resources',
                'code' => 'HR',
                'type' => 'administrative',
                'permissions' => [
                    'hr.view_module', 'hr.manage_employees', 'hr.manage_payroll',
                    'hr.manage_leaves', 'hr.manage_disciplinary', 'hr.view_reports', 'reports.view_module',
                ],
            ],
            'communications' => [
                'name' => 'Communications & PR',
                'code' => 'COM',
                'type' => 'administrative',
                'permissions' => [
                    'communication.view_module', 'communication.post_announcements',
                    'communication.manage_helpdesk', 'communication.create_polls',
                    'website.view_module', 'website.manage_pages', 'website.preview', 'website.manage_settings',
                ],
            ],
            'admissions' => [
                'name' => 'Admissions & Enrolment',
                'code' => 'ADM',
                'type' => 'administrative',
                'permissions' => [
                    'admissions.view_module', 'admissions.manage_applications',
                    'admissions.approve_applications', 'admissions.export',
                    'academic_ops.manage_admissions', 'reports.view_module', 'reports.generate',
                ],
            ],
            'estates' => [
                'name' => 'Transport & Estates',
                'code' => 'EST',
                'type' => 'support',
                'permissions' => [
                    'inventory.view_module', 'inventory.issue_stock',
                    'boarding.view_module', 'boarding.inspect_dorms', 'reports.view_module',
                ],
            ],
            'sports' => [
                'name' => 'Sports & Co-curricular',
                'code' => 'SPT',
                'type' => 'support',
                'permissions' => [
                    'communication.view_module', 'communication.post_announcements',
                    'boarding.view_module', 'tasks.view', 'tasks.create',
                ],
            ],
            'welfare' => [
                'name' => 'Student Welfare & Guidance',
                'code' => 'WEL',
                'type' => 'support',
                'permissions' => [
                    'clinic.view_module', 'clinic.view_medical_profiles',
                    'boarding.view_module', 'boarding.take_roll_call',
                    'communication.view_module', 'communication.post_announcements',
                    'tasks.view', 'tasks.create',
                ],
            ],
        ];
    }

    /**
     * Create any missing preset departments for a school. Existing departments
     * are matched by code; their permission bundles are refreshed with any
     * newly-introduced matrix keys so older departments stay up to date.
     *
     * @return array<int, Department>
     */
    public static function provisionForSchool(School $school): array
    {
        $created = [];

        foreach (self::all() as $preset) {
            $department = Department::query()
                ->where('school_id', $school->id)
                ->where('code', $preset['code'])
                ->first();

            if ($department) {
                $merged = array_values(array_unique(array_merge(
                    is_array($department->permissions) ? $department->permissions : [],
                    $preset['permissions'],
                )));

                if ($merged !== ($department->permissions ?? [])) {
                    $department->permissions = $merged;
                    $department->save();
                }

                continue;
            }

            $created[] = Department::create([
                'school_id' => $school->id,
                'name' => $preset['name'],
                'code' => $preset['code'],
                'type' => $preset['type'],
                'permissions' => $preset['permissions'],
                'status' => 'active',
            ]);
        }

        return $created;
    }
}
