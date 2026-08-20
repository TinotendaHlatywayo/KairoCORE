<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AcademicWorkflowPermissionSeeder extends Seeder
{
    /**
     * Register the granular Academic Operations permission nodes and
     * grant the full set to the default administrator role.
     */
    public function run(): void
    {
        $permissions = [
            'academic_ops.view',
            'academic_ops.manage_workflow',
            'academic_ops.manage_calendar',
            'academic_ops.manage_curriculum',
            'academic_ops.manage_subjects',
            'academic_ops.manage_classrooms',
            'academic_ops.manage_timetable',
            'academic_ops.manage_assessments',
            'academic_ops.manage_admissions',
            'academic_ops.manage_enrolment',
            'academic_ops.manage_reports',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo($permissions);

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo($permissions);
    }
}
