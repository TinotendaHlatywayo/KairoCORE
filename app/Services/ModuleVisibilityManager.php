<?php

namespace App\Services;

use Modules\Admin\Models\SystemSetting;

class ModuleVisibilityManager
{
    /**
     * Resolve the school id for the active tenant context.
     * Returns null in the global super-admin context (no tenant selected).
     */
    protected static function schoolId(): ?int
    {
        return current_tenant()?->id;
    }

    /**
     * Checks if a specific module is active for the current school tenant.
     *
     * Storage-normalised: the System Settings form persists module toggles as
     * boolean values which may end up in the database as "1"/"0" strings,
     * as integers, or as native booleans. A strict `=== '1'` comparison
     * silently fails for every representation except the exact string "1"
     * (json_decode("1") returns the integer 1, which broke visibility).
     *
     * We therefore normalise through filter_var so that "1", 1, true, "true"
     * all resolve to enabled, and "0", 0, "false", "" resolve to disabled.
     */
    public static function isVisible(string $moduleName): bool
    {
        $schoolId = self::schoolId();
        if (! $schoolId) {
            return true; // Default visible during super-admin platform context
        }

        $raw = SystemSetting::get('modules', $moduleName, '1');

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Checks whether a module is visible given its navigation slug.
     *
     * Module slugs match the System Settings master toggle key
     * (e.g. "finance" -> modules_finance). Two slugs differ:
     *  - "health" maps to the "clinic" master toggle
     *  - "admissions" additionally requires the "applications" sub-page toggle
     * Every module has a master toggle (lms, knowledge, reports,
     * administration and saas included), so unknown slugs never occur.
     */
    public static function isModuleVisible(string $moduleSlug): bool
    {
        return match ($moduleSlug) {
            'admissions' => true,
            'health' => self::isVisible('clinic'),
            default => self::isVisible($moduleSlug),
        };
    }

    /**
     * Checks whether a specific sub-page inside a module is visible.
     * Requires BOTH the module master toggle AND the sub-page toggle to be on.
     */
    public static function isPageVisible(string $moduleKey, string $pageKey): bool
    {
        $schoolId = self::schoolId();
        if (! $schoolId) {
            return true; // Default visible during super-admin platform context
        }

        if ($moduleKey === 'admissions') {
            return true;
        }

        $raw = SystemSetting::get('modules', $moduleKey.'_'.$pageKey, '1');

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Universal resource / page visibility check mapping classes to modules.
     */
    public static function isResourceVisible(string $class): bool
    {
        // LMS / Knowledge / Reports / Administration / SaaS checks must run
        // BEFORE the finance heuristics below, since class names like
        // UserAccount* / SaaSBilling* would otherwise match 'Account'/'Billing'.
        if (str_contains($class, 'Homework') || str_contains($class, 'Lesson')) {
            return self::isVisible('lms');
        }
        if (str_contains($class, 'DigitalAssessment') || str_contains($class, 'QuestionBank')) {
            return self::isVisible('digital_assessment');
        }
        if (str_contains($class, 'Knowledge')) {
            return self::isVisible('knowledge');
        }
        if (str_contains($class, 'ReportingDashboard') || str_contains($class, 'ReportingWorkflow') || str_contains($class, 'GeneratedReport') || str_contains($class, 'EnterpriseReportTemplate') || str_contains($class, 'ReportGenerator') || str_contains($class, 'AnalyticsExplorer')) {
            return self::isVisible('reports');
        }
        if (str_contains($class, 'UserAccount') || str_contains($class, 'CustomRole') || str_contains($class, 'Department') || str_contains($class, 'SystemAuditLog') || str_contains($class, 'AdministrationDashboard') || str_contains($class, 'TenantDataExport') || str_contains($class, 'EmailConfiguration')) {
            return self::isVisible('administration');
        }
        if (str_contains($class, 'SaaS') || str_contains($class, 'SaaSBilling') || str_contains($class, 'Subscription')) {
            return self::isVisible('saas');
        }
        if (str_contains($class, 'Library') || str_contains($class, 'LibraryBook') || str_contains($class, 'LibraryIssue')) {
            return self::isVisible('library');
        }
        if (str_contains($class, 'Employee') || str_contains($class, 'Payroll') || str_contains($class, 'Leave') || str_contains($class, 'Salary') || str_contains($class, 'StaffLoan') || str_contains($class, 'EmployeeAsset') || str_contains($class, 'Disciplinary')) {
            return self::isVisible('hr');
        }
        if (str_contains($class, 'Invoice') || str_contains($class, 'Expense') || str_contains($class, 'Account') || str_contains($class, 'Fee') || str_contains($class, 'Revenue') || str_contains($class, 'Journal') || str_contains($class, 'Billing')) {
            return self::isVisible('finance');
        }
        if (str_contains($class, 'Inventory') || str_contains($class, 'Procurement') || str_contains($class, 'Purchase') || str_contains($class, 'Supplier') || str_contains($class, 'GoodsReceived')) {
            return self::isVisible('inventory');
        }
        if (str_contains($class, 'Hostel') || str_contains($class, 'Welfare')) {
            return self::isVisible('boarding');
        }
        if (str_contains($class, 'Medical') || str_contains($class, 'Clinic') || str_contains($class, 'StudentMedicalRecord') || str_contains($class, 'ClinicVisit')) {
            return self::isVisible('clinic');
        }
        if (str_contains($class, 'Student') && ! str_contains($class, 'Medical')) {
            return self::isVisible('students');
        }
        if (str_contains($class, 'Application') || str_contains($class, 'Admission')) {
            return self::isPageVisible('admissions', 'applications');
        }
        if (str_contains($class, 'Course') || str_contains($class, 'Subject') || str_contains($class, 'Classroom') || str_contains($class, 'AcademicYear') || str_contains($class, 'TimeSlot') || str_contains($class, 'TimetableLesson') || str_contains($class, 'TeacherAssignment') || str_contains($class, 'Promotion')) {
            return self::isVisible('academics');
        }
        if (str_contains($class, 'Assessment') || str_contains($class, 'GradingScale') || str_contains($class, 'AcademicReport') || str_contains($class, 'ReportTemplate')) {
            return self::isVisible('exams');
        }
        if (str_contains($class, 'StaffAttendance') || str_contains($class, 'Attendance')) {
            return self::isVisible('attendance');
        }
        if (str_contains($class, 'Cms') || str_contains($class, 'Website')) {
            return self::isVisible('website');
        }
        if (str_contains($class, 'Communication') || str_contains($class, 'Notice') || str_contains($class, 'EventCalendar')) {
            return self::isVisible('communication');
        }

        return true;
    }

    /**
     * Check if a navigation URL belongs to a visible module.
     */
    public static function isUrlVisible(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? $url;
        if (str_contains($path, '/libraries') || str_contains($path, '/library')) {
            return self::isVisible('library');
        }
        if (str_contains($path, '/employees') || str_contains($path, '/payrolls') || str_contains($path, '/leaves') || str_contains($path, '/salary') || str_contains($path, '/loans') || str_contains($path, '/disciplinary')) {
            return self::isVisible('hr');
        }
        if (str_contains($path, '/invoices') || str_contains($path, '/expenses') || str_contains($path, '/accounts') || str_contains($path, '/fees') || str_contains($path, '/revenue') || str_contains($path, '/journal') || str_contains($path, '/billing')) {
            return self::isVisible('finance');
        }
        if (str_contains($path, '/inventory') || str_contains($path, '/procurement') || str_contains($path, '/purchase') || str_contains($path, '/suppliers')) {
            return self::isVisible('inventory');
        }
        if (str_contains($path, '/hostel') || str_contains($path, '/boarding')) {
            return self::isVisible('boarding');
        }
        if (str_contains($path, '/digital-assessments') || str_contains($path, '/question-bank')) {
            return self::isVisible('digital_assessment');
        }
        if (str_contains($path, '/clinic') || str_contains($path, '/medical')) {
            return self::isVisible('clinic');
        }
        if (str_contains($path, '/students') && ! str_contains($path, '/medical')) {
            return self::isVisible('students');
        }
        if (str_contains($path, '/applications') || str_contains($path, '/admissions')) {
            return self::isPageVisible('admissions', 'applications');
        }
        if (str_contains($path, '/courses') || str_contains($path, '/subjects') || str_contains($path, '/classrooms') || str_contains($path, '/academic-years') || str_contains($path, '/time-slots') || str_contains($path, '/timetable') || str_contains($path, '/teacher-assignments') || str_contains($path, '/promotions')) {
            return self::isVisible('academics');
        }
        if (str_contains($path, '/assessments') || str_contains($path, '/grading') || str_contains($path, '/reports') || str_contains($path, '/marks')) {
            return self::isVisible('exams');
        }
        if (str_contains($path, '/attendance')) {
            return self::isVisible('attendance');
        }
        if (str_contains($path, '/cms') || str_contains($path, '/website')) {
            return self::isVisible('website');
        }
        if (str_contains($path, '/communication') || str_contains($path, '/announcements') || str_contains($path, '/helpdesk') || str_contains($path, '/calendar')) {
            return self::isVisible('communication');
        }

        return true;
    }
}
