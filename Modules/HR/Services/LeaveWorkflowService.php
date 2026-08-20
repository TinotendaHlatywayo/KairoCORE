<?php

namespace Modules\HR\Services;

use Carbon\Carbon;
use Exception;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\LeaveType;

class LeaveWorkflowService
{
    /**
     * Validate leave request parameters against institutional constraints.
     */
    public function validateLeaveEligibility(Employee $employee, LeaveType $leaveType, string $startDate, string $endDate): bool
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $requestedDays = $start->diffInDays($end) + 1;

        // 1. Gender Restriction Check
        if (! empty($leaveType->gender_restriction)) {
            $userGender = strtolower($employee->gender);
            $ruleGender = strtolower($leaveType->gender_restriction);

            if ($userGender !== $ruleGender) {
                throw new Exception("Gender policy conflict: This leave type is restricted to {$leaveType->gender_restriction} staff only.");
            }
        }

        // 2. Service Length & Probation Check
        $serviceDays = Carbon::parse($employee->date_joined)->diffInDays(Carbon::now());
        if ($serviceDays < $leaveType->probation_restricted_days) {
            $remainingRequired = $leaveType->probation_restricted_days - $serviceDays;
            throw new Exception("Probation restriction: This leave type requires completing {$leaveType->probation_restricted_days} probation service days. You require {$remainingRequired} more days.");
        }

        // 3. Minimum Active Months Check
        if ($leaveType->service_length_months_required > 0) {
            $serviceMonths = Carbon::parse($employee->date_joined)->diffInMonths(Carbon::now());
            if ($serviceMonths < $leaveType->service_length_months_required) {
                throw new Exception("Service constraint: You have completed {$serviceMonths} months of service, but this leave type requires {$leaveType->service_length_months_required} months.");
            }
        }

        // 4. Annual Balance Utilization Check
        $currentYear = Carbon::parse($startDate)->year;
        $accruedUtilizedDays = LeaveRequest::where('school_id', $employee->school_id)
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $currentYear)
            ->get()
            ->sum('total_days');

        $remainingBalance = $leaveType->days_per_year - $accruedUtilizedDays;

        if ($requestedDays > $remainingBalance) {
            throw new Exception("Insufficient leave balance. You requested {$requestedDays} days, but your current remaining balance for the year is {$remainingBalance} days.");
        }

        return true;
    }
}
