<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HrPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define granular permission nodes
        $permissions = [
            'hr.employees.view',
            'hr.employees.create',
            'hr.employees.edit',
            'hr.employees.delete',
            'hr.payroll.calculate',
            'hr.payroll.approve',
            'hr.payroll.release',
            'hr.leaves.approve',
            'hr.disciplinary.manage',
            'hr.assets.manage',
        ];

        // Ensure permission nodes exist
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // Configure HR Officer Role
        $hrOfficer = Role::firstOrCreate(['name' => 'HR Officer']);
        $hrOfficer->syncPermissions([
            'hr.employees.view',
            'hr.employees.create',
            'hr.employees.edit',
            'hr.leaves.approve',
            'hr.disciplinary.manage',
            'hr.assets.manage',
        ]);

        // Configure Payroll Officer Role
        $payrollOfficer = Role::firstOrCreate(['name' => 'Payroll Officer']);
        $payrollOfficer->syncPermissions([
            'hr.employees.view',
            'hr.payroll.calculate',
        ]);

        // Configure Principal / General Director Role
        $principal = Role::firstOrCreate(['name' => 'Principal']);
        $principal->syncPermissions([
            'hr.employees.view',
            'hr.payroll.approve',
            'hr.payroll.release',
            'hr.disciplinary.manage',
        ]);

        // Configure Accountant Role
        $accountant = Role::firstOrCreate(['name' => 'Accountant']);
        $accountant->syncPermissions([
            'hr.employees.view',
            'hr.payroll.calculate',
        ]);

        // Configure Employee (Self-Service Profile Default)
        $employee = Role::firstOrCreate(['name' => 'Employee']);
        $employee->syncPermissions([]); // Relies on basic session-scoped self endpoints
    }
}
