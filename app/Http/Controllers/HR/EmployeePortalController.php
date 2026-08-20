<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\LeaveType;
use Modules\HR\Models\Payslip;
use Modules\HR\Services\LeaveWorkflowService;

class EmployeePortalController extends Controller
{
    /**
     * Retrieve the authenticated employee's profile.
     */
    public function getProfile(Request $request)
    {
        $employee = Employee::where('school_id', Auth::user()->school_id)
            ->where('user_id', Auth::id())
            ->with(['currentGrade'])
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $employee,
        ], 200);
    }

    /**
     * Submit a leave application.
     */
    public function submitLeave(Request $request)
    {
        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'file' => 'nullable|file|max:2048',
        ]);

        try {
            $employee = Employee::where('school_id', Auth::user()->school_id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $leaveType = LeaveType::findOrFail($request->leave_type_id);

            // Execute service compliance and balance validation checks
            $workflow = app(LeaveWorkflowService::class);
            $workflow->validateLeaveEligibility($employee, $leaveType, $request->start_date, $request->end_date);

            // Store files locally inside tenant space
            $filePath = $request->hasFile('file') ? $request->file('file')->store('hr/leaves') : null;

            $leave = LeaveRequest::create([
                'school_id' => $employee->school_id,
                'employee_id' => $employee->id,
                'leave_type_id' => $request->leave_type_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'reason' => $request->reason,
                'status' => 'pending',
                'supporting_document_path' => $filePath,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Your leave application has been submitted successfully.',
                'data' => $leave,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Retrieve all released payslips.
     */
    public function getPayslips(Request $request)
    {
        $employee = Employee::where('school_id', Auth::user()->school_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $payslips = Payslip::where('school_id', $employee->school_id)
            ->where('employee_id', $employee->id)
            ->where('status', 'released')
            ->with(['run.period'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $payslips,
        ], 200);
    }
}
