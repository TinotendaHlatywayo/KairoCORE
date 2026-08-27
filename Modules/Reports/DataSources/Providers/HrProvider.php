<?php

namespace Modules\Reports\DataSources\Providers;

use Modules\Reports\DataSources\AbstractDatasetProvider;

class HrProvider extends AbstractDatasetProvider
{
    public function module(): string
    {
        return __('HR & Payroll');
    }

    public function datasets(): array
    {
        return [
            $this->d('hr.employee', __('Employees'), 'employees', [
                $this->f('employee_number', __('Employee Number')),
                $this->f('national_id', __('National ID')),
                $this->f('first_name', __('First Name')),
                $this->f('last_name', __('Last Name')),
                $this->f('full_name', __('Full Name'), 'string', "CONCAT(hr_employee.first_name, ' ', hr_employee.last_name)"),
                $this->f('gender', __('Gender')),
                $this->f('marital_status', __('Marital Status')),
                $this->f('department', __('Department')),
                $this->f('designation', __('Designation')),
                $this->f('role', __('Role')),
                $this->f('employment_type', __('Employment Type')),
                $this->f('date_joined', __('Date Joined'), 'date'),
                $this->f('contract_end_date', __('Contract End'), 'date'),
                $this->f('status', __('Status')),
                $this->f('phone_number', __('Phone')),
                $this->f('email', __('Email')),
                $this->f('dependents', __('Dependents'), 'integer'),
            ], [
                'description' => __('Employee register with employment details.'),
                'connections' => [
                    $this->connect('hr.department', 'hr_employee.department', 'hr_department.name'),
                    $this->connect('hr.leave_request', 'hr_employee.id', 'hr_leave_request.employee_id'),
                    $this->connect('payroll.payslip', 'hr_employee.id', 'payroll_payslip.employee_id'),
                    $this->connect('discipline.case', 'hr_employee.id', 'discipline_case.employee_id'),
                ],
                'filters' => [
                    ['key' => 'gender', 'label' => __('Gender'), 'type' => 'select', 'options' => ['Male', 'Female', 'Other']],
                    ['key' => 'status', 'label' => __('Status'), 'type' => 'select', 'options' => ['active', 'on_leave', 'suspended', 'terminated']],
                    ['key' => 'employment_type', 'label' => __('Employment Type'), 'type' => 'select', 'options' => ['full_time', 'part_time', 'contract', 'casual']],
                ],
            ]),

            $this->d('hr.department', __('Departments'), 'departments', [
                $this->f('name', __('Department Name')),
                $this->f('code', __('Code')),
                $this->f('type', __('Type')),
                $this->f('budget_code', __('Budget Code')),
                $this->f('status', __('Status')),
            ], [
                'description' => __('Departments and budget codes.'),
                'connections' => [
                    $this->connect('hr.employee', 'hr_department.name', 'hr_employee.department'),
                    $this->connect('procurement.request', 'hr_department.id', 'procurement_request.department_id'),
                ],
            ]),

            $this->d('hr.leave_request', __('Leave Requests'), 'leave_requests', [
                $this->f('start_date', __('Start Date'), 'date'),
                $this->f('end_date', __('End Date'), 'date'),
                $this->f('total_days', __('Total Days'), 'decimal'),
                $this->f('status', __('Status')),
                $this->f('reason', __('Reason')),
                $this->f('employee_name', __('Employee Name'), 'string', "CONCAT(hr_leave_emp.first_name, ' ', hr_leave_emp.last_name)"),
                $this->f('department', __('Department'), 'string', 'hr_leave_emp.department'),
                $this->f('leave_type', __('Leave Type'), 'string', 'hr_leave_type.name'),
            ], [
                'description' => __('Leave requests with employee, department and leave type context.'),
                'autoJoins' => [
                    ['alias' => 'hr_leave_emp', 'table' => 'employees', 'type' => 'left', 'on' => [['hr_leave_emp.id', 'hr_leave_request.employee_id']]],
                    ['alias' => 'hr_leave_type', 'table' => 'leave_types', 'type' => 'left', 'on' => [['hr_leave_type.id', 'hr_leave_request.leave_type_id']]],
                ],
                'connections' => [
                    $this->connect('hr.employee', 'hr_leave_request.employee_id', 'hr_employee.id'),
                    $this->connect('hr.department', 'hr_leave_emp.department', 'hr_department.name'),
                ],
                'filters' => [
                    ['key' => 'status', 'label' => __('Status'), 'type' => 'select', 'options' => ['pending', 'approved', 'rejected', 'cancelled']],
                ],
            ]),

            $this->d('payroll.payslip', __('Payslips'), 'payslips', [
                $this->money('base_salary', __('Base Salary'), 'payroll_payslip.base_salary'),
                $this->money('gross_pay', __('Gross Pay'), 'payroll_payslip.gross_pay'),
                $this->money('total_deductions', __('Total Deductions'), 'payroll_payslip.total_deductions'),
                $this->money('net_pay', __('Net Pay'), 'payroll_payslip.net_pay'),
                $this->f('status', __('Status')),
                $this->f('payment_date', __('Payment Date'), 'date'),
                $this->f('employee_name', __('Employee Name'), 'string', "CONCAT(payroll_payslip_emp.first_name, ' ', payroll_payslip_emp.last_name)"),
                $this->f('department', __('Department'), 'string', 'payroll_payslip_emp.department'),
                $this->f('period_name', __('Payroll Period'), 'string', 'payroll_payslip_period.name'),
            ], [
                'description' => __('Payslips with employee and payroll period context.'),
                'autoJoins' => [
                    ['alias' => 'payroll_payslip_emp', 'table' => 'employees', 'type' => 'left', 'on' => [['payroll_payslip_emp.id', 'payroll_payslip.employee_id']]],
                    ['alias' => 'payroll_payslip_run', 'table' => 'payroll_runs', 'type' => 'left', 'on' => [['payroll_payslip_run.id', 'payroll_payslip.payroll_run_id']]],
                    ['alias' => 'payroll_payslip_period', 'table' => 'payroll_periods', 'type' => 'left', 'on' => [['payroll_payslip_period.id', 'payroll_payslip_run.payroll_period_id']]],
                ],
                'connections' => [
                    $this->connect('hr.employee', 'payroll_payslip.employee_id', 'hr_employee.id'),
                    $this->connect('hr.department', 'payroll_payslip_emp.department', 'hr_department.name'),
                ],
                'filters' => [
                    ['key' => 'status', 'label' => __('Status'), 'type' => 'select', 'options' => ['draft', 'paid', 'void']],
                ],
            ]),

            $this->d('payroll.run', __('Payroll Runs'), 'payroll_runs', [
                $this->f('status', __('Status')),
                $this->f('calculated_at', __('Calculated At'), 'datetime'),
                $this->f('approved_at', __('Approved At'), 'datetime'),
                $this->money('gross_total', __('Gross Total'), 'payroll_run.gross_total'),
                $this->money('deductions_total', __('Deductions Total'), 'payroll_run.deductions_total'),
                $this->money('net_total', __('Net Total'), 'payroll_run.net_total'),
                $this->f('period_name', __('Period'), 'string', 'payroll_run_period.name'),
            ], [
                'description' => __('Payroll run summaries with totals.'),
                'autoJoins' => [
                    ['alias' => 'payroll_run_period', 'table' => 'payroll_periods', 'type' => 'left', 'on' => [['payroll_run_period.id', 'payroll_run.payroll_period_id']]],
                ],
            ]),

            $this->d('discipline.case', __('Disciplinary Cases'), 'disciplinary_cases', [
                $this->f('offense', __('Offense')),
                $this->f('incident_date', __('Incident Date'), 'date'),
                $this->f('status', __('Status')),
                $this->f('severity', __('Severity')),
                $this->f('resolution_notes', __('Resolution Notes')),
                $this->f('employee_name', __('Employee Name'), 'string', "CONCAT(discipline_case_emp.first_name, ' ', discipline_case_emp.last_name)"),
            ], [
                'description' => __('Staff disciplinary cases.'),
                'autoJoins' => [
                    ['alias' => 'discipline_case_emp', 'table' => 'employees', 'type' => 'left', 'on' => [['discipline_case_emp.id', 'discipline_case.employee_id']]],
                ],
                'connections' => [
                    $this->connect('hr.employee', 'discipline_case.employee_id', 'hr_employee.id'),
                ],
            ]),
        ];
    }
}
