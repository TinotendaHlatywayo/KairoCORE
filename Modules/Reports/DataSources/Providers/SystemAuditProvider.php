<?php

namespace Modules\Reports\DataSources\Providers;

use Modules\Reports\DataSources\AbstractDatasetProvider;

class SystemAuditProvider extends AbstractDatasetProvider
{
    public function module(): string
    {
        return 'Administration';
    }

    public function datasets(): array
    {
        return [
            $this->d('system.audit_log', 'System Audit Log', 'system_audit_logs', [
                $this->f('action', 'Action'),
                $this->f('module', 'Module'),
                $this->f('outcome', 'Outcome'),
                $this->f('ip_address', 'IP Address'),
                $this->f('user_agent', 'User Agent'),
                $this->f('created_at', 'Logged At', 'datetime'),
                $this->f('user_name', 'User', 'string', 'system_audit_user.name'),
                $this->f('user_email', 'User Email', 'string', 'system_audit_user.email'),
            ], [
                'description' => __('Trail of administrative actions across the platform.'),
                'autoJoins' => [
                    ['alias' => 'system_audit_user', 'table' => 'users', 'type' => 'left', 'on' => [['system_audit_user.id', 'system_audit_log.user_id']]],
                ],
                'filters' => [
                    ['key' => 'outcome', 'label' => __('Outcome'), 'type' => 'select', 'options' => ['success', 'failure', 'denied']],
                    ['key' => 'action', 'label' => __('Action'), 'type' => 'text'],
                ],
                'default_order' => 'created_at|desc',
            ]),

            $this->d('system.department', 'Departments', 'departments', [
                $this->f('name', 'Department Name'),
                $this->f('code', 'Code'),
                $this->f('type', 'Type'),
                $this->f('budget_code', 'Budget Code'),
                $this->f('status', 'Status'),
                $this->f('head_name', 'Head of Department', 'string', 'system_dept_head.name'),
            ], [
                'description' => __('Organisational departments and their heads.'),
                'autoJoins' => [
                    ['alias' => 'system_dept_head', 'table' => 'users', 'type' => 'left', 'on' => [['system_dept_head.id', 'system_department.head_user_id']]],
                ],
                'connections' => [
                    $this->connect('hr.employee', 'system_department.id', 'hr_employee.department_id'),
                ],
                'filters' => [
                    ['key' => 'status', 'label' => __('Status'), 'type' => 'select', 'options' => ['active', 'inactive']],
                ],
            ]),

            $this->d('system.user', 'Users', 'users', [
                $this->f('name', 'Name'),
                $this->f('email', 'Email'),
                $this->f('created_at', 'Created At', 'datetime'),
                $this->f('role_name', 'Role', 'string', 'system_user_role.name'),
            ], [
                'description' => __('User accounts and roles.'),
                'autoJoins' => [
                    ['alias' => 'system_user_role', 'table' => 'custom_roles', 'type' => 'left', 'on' => [['system_user_role.id', 'system_user.custom_role_id']]],
                ],
            ]),
        ];
    }
}
