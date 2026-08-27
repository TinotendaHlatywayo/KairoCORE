<?php

namespace App\Filament\App\Concerns;

use App\Filament\App\Pages\AnalyticsExplorer;
use App\Filament\App\Pages\AssessmentWorkspace;
use App\Filament\App\Pages\CommunicationCenter;
use App\Filament\App\Pages\ExecutiveFinancialDashboard;
use App\Filament\App\Pages\IssueBook;
use App\Filament\App\Pages\ReportGeneratorPage;
use App\Filament\App\Pages\ReportingDashboard;
use App\Filament\App\Pages\TenantDataExportPage;
use App\Filament\App\Pages\VisualCmsBuilder;
use App\Filament\App\Pages\VisualTimetableBuilder;
use App\Filament\App\Pages\WebsiteContentManager;
use App\Filament\App\Pages\WebsiteTemplatesHub;
use App\Filament\App\Resources\AcademicReportResource;
use App\Filament\App\Resources\AccountResource;
use App\Filament\App\Resources\AnnouncementResource;
use App\Filament\App\Resources\AssessmentWorkflowResource;
use App\Filament\App\Resources\CampusResourceResource;
use App\Filament\App\Resources\CardTemplateResource;
use App\Filament\App\Resources\ChatThreadResource;
use App\Filament\App\Resources\DisciplinaryCaseResource;
use App\Filament\App\Resources\EmployeeAssetResource;
use App\Filament\App\Resources\EmployeeResource;
use App\Filament\App\Resources\EnterpriseReportTemplateResource;
use App\Filament\App\Resources\EventCalendarResource;
use App\Filament\App\Resources\ExpenseCategoryResource;
use App\Filament\App\Resources\ExpenseResource;
use App\Filament\App\Resources\ExpenseTypeResource;
use App\Filament\App\Resources\FeeCategoryResource;
use App\Filament\App\Resources\FeePaymentSubmissionResource;
use App\Filament\App\Resources\FeeStructureResource;
use App\Filament\App\Resources\FeeWaiverResource;
use App\Filament\App\Resources\FinanceDocumentTemplateResource;
use App\Filament\App\Resources\FixedAssetResource;
use App\Filament\App\Resources\GeneratedReportResource;
use App\Filament\App\Resources\HelpdeskTicketResource;
use App\Filament\App\Resources\HomeworkResource;
use App\Filament\App\Resources\HostelAllocationResource;
use App\Filament\App\Resources\HostelAttendanceResource;
use App\Filament\App\Resources\HostelInspectionResource;
use App\Filament\App\Resources\HostelOutPassResource;
use App\Filament\App\Resources\HostelResource;
use App\Filament\App\Resources\HostelRoomResource;
use App\Filament\App\Resources\InvoiceResource;
use App\Filament\App\Resources\JournalEntryResource;
use App\Filament\App\Resources\LeaveRequestResource;
use App\Filament\App\Resources\PayrollPeriodResource;
use App\Filament\App\Resources\PollResource;
use App\Filament\App\Resources\ReportingWorkflowResource;
use App\Filament\App\Resources\RevenueCategoryResource;
use App\Filament\App\Resources\RevenueStreamResource;
use App\Filament\App\Resources\SalaryGradeResource;
use App\Filament\App\Resources\StaffAttendanceResource;
use App\Filament\App\Resources\StaffLoanResource;
use App\Filament\App\Resources\StockAdjustmentResource;
use App\Filament\App\Resources\SupplierResource;
use App\Filament\App\Resources\TeacherAssignmentResource;
use App\Services\ModuleVisibilityManager;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Inventory\Filament\Resources\AssetMaintenanceResource;
use Modules\Inventory\Filament\Resources\GoodsReceivedResource;
use Modules\Inventory\Filament\Resources\InventoryIssuanceResource;
use Modules\Inventory\Filament\Resources\InventoryItemResource;
use Modules\Inventory\Filament\Resources\InventoryProcurementResource;
use Modules\Inventory\Filament\Resources\ProcurementRequestResource;
use Modules\Inventory\Filament\Resources\PurchaseOrderResource;
use Modules\Knowledge\Filament\Resources\KnowledgeAssetResource;
use Modules\Knowledge\Filament\Resources\KnowledgeGalleryResource;

/**
 * Permission-based access gate for tenant (workspace) resources and pages.
 *
 * Every resource/page that touches school data is mapped to a module slug and
 * a granular permission. Students (and other restricted roles) are then kept
 * out of modules they have not been granted, while the school Administrator
 * keeps full access automatically. Unmapped classes remain accessible so the
 * gate only ever tightens access where an explicit decision has been made.
 */
trait ModulePermissionAccess
{
    /**
     * class => [moduleSlug, permissionKey]
     */
    protected static array $moduleAccessMap = [
        // ---- Academics / SIS ----
        CampusResourceResource::class => ['academics', 'academics.view_module'],
        CardTemplateResource::class => ['academics', 'academics.view_records'],
        EventCalendarResource::class => ['academics', 'academics.view_module'],
        TeacherAssignmentResource::class => ['academics', 'academic_ops.manage_curriculum'],
        AssessmentWorkflowResource::class => ['academics', 'academic_ops.manage_assessments'],
        ReportingWorkflowResource::class => ['academics', 'academic_ops.manage_reports'],
        AcademicReportResource::class => ['exams', 'exams.generate_reports'],

        // ---- Finance ----
        AccountResource::class => ['finance', 'finance.view_reports'],
        InvoiceResource::class => ['finance', 'finance.view_module'],
        JournalEntryResource::class => ['finance', 'finance.view_reports'],
        RevenueStreamResource::class => ['finance', 'finance.view_reports'],
        RevenueCategoryResource::class => ['finance', 'finance.view_module'],
        ExpenseResource::class => ['finance', 'finance.view_module'],
        ExpenseCategoryResource::class => ['finance', 'finance.view_module'],
        ExpenseTypeResource::class => ['finance', 'finance.view_module'],
        FeeStructureResource::class => ['finance', 'finance.manage_fees'],
        FeeCategoryResource::class => ['finance', 'finance.manage_fees'],
        FeeWaiverResource::class => ['finance', 'finance.manage_fees'],
        SupplierResource::class => ['finance', 'finance.view_module'],
        FeePaymentSubmissionResource::class => ['finance', 'finance.receive_payments'],
        FinanceDocumentTemplateResource::class => ['finance', 'finance.view_module'],

        // ---- Human Resources ----
        EmployeeResource::class => ['hr', 'hr.manage_employees'],
        EmployeeAssetResource::class => ['hr', 'hr.view_module'],
        DisciplinaryCaseResource::class => ['hr', 'hr.manage_disciplinary'],
        LeaveRequestResource::class => ['hr', 'hr.manage_leaves'],
        PayrollPeriodResource::class => ['hr', 'hr.manage_payroll'],
        SalaryGradeResource::class => ['hr', 'hr.manage_payroll'],
        StaffAttendanceResource::class => ['hr', 'hr.view_module'],
        StaffLoanResource::class => ['hr', 'hr.view_module'],

        // ---- Inventory / Assets ----
        FixedAssetResource::class => ['inventory', 'inventory.view_module'],
        StockAdjustmentResource::class => ['inventory', 'inventory.audit_stock'],
        AssetMaintenanceResource::class => ['inventory', 'inventory.view_module'],
        GoodsReceivedResource::class => ['inventory', 'inventory.manage_procurement'],
        InventoryIssuanceResource::class => ['inventory', 'inventory.issue_stock'],
        InventoryItemResource::class => ['inventory', 'inventory.manage_catalog'],
        InventoryProcurementResource::class => ['inventory', 'inventory.manage_procurement'],
        ProcurementRequestResource::class => ['inventory', 'inventory.manage_procurement'],
        PurchaseOrderResource::class => ['inventory', 'inventory.manage_procurement'],
        \Modules\Inventory\Filament\Resources\SupplierResource::class => ['inventory', 'inventory.manage_procurement'],

        // ---- Knowledge Hub ----
        KnowledgeAssetResource::class => ['knowledge', 'knowledge.contribute'],
        KnowledgeGalleryResource::class => ['knowledge', 'knowledge.view_module'],

        // ---- Communication ----
        AnnouncementResource::class => ['communication', 'communication.post_announcements'],
        ChatThreadResource::class => ['communication', 'communication.view_module'],
        HelpdeskTicketResource::class => ['communication', 'communication.manage_helpdesk'],
        PollResource::class => ['communication', 'communication.create_polls'],

        // ---- Boarding ----
        HostelResource::class => ['boarding', 'boarding.view_module'],
        HostelRoomResource::class => ['boarding', 'boarding.view_module'],
        HostelAllocationResource::class => ['boarding', 'boarding.allocate_rooms'],
        HostelAttendanceResource::class => ['boarding', 'boarding.take_roll_call'],
        HostelInspectionResource::class => ['boarding', 'boarding.inspect_dorms'],
        HostelOutPassResource::class => ['boarding', 'boarding.issue_out_passes'],

        // ---- LMS ----
        HomeworkResource::class => ['lms', 'lms.manage_content'],

        // ---- Digital Assessment ----
        \App\Filament\App\Resources\DigitalAssessmentResource::class => ['digital_assessment', 'digital_assessment.create_assessments'],
        \App\Filament\App\Resources\QuestionBankResource::class => ['digital_assessment', 'digital_assessment.manage_questions'],

        // ---- Reports ----
        EnterpriseReportTemplateResource::class => ['reports', 'reports.manage_templates'],
        GeneratedReportResource::class => ['reports', 'reports.view_module'],

        // ---- App Pages ----
        AnalyticsExplorer::class => ['reports', 'reports.generate'],
        ReportGeneratorPage::class => ['reports', 'reports.generate'],
        ReportingDashboard::class => ['reports', 'reports.view_module'],
        TenantDataExportPage::class => ['reports', 'reports.export'],
        AssessmentWorkspace::class => ['academics', 'academic_ops.manage_assessments'],
        CommunicationCenter::class => ['communication', 'communication.view_module'],
        ExecutiveFinancialDashboard::class => ['finance', 'finance.view_reports'],
        VisualCmsBuilder::class => ['website', 'website.manage_pages'],
        WebsiteContentManager::class => ['website', 'website.manage_pages'],
        WebsiteTemplatesHub::class => ['website', 'website.manage_settings'],
        VisualTimetableBuilder::class => ['academics', 'academic_ops.manage_timetable'],
        IssueBook::class => ['library', 'library.view_module'],
    ];

    public static function canAccess(): bool
    {
        $mapping = static::$moduleAccessMap[static::class] ?? null;

        if ($mapping === null) {
            return true;
        }

        [$module, $permission] = $mapping;

        if ($module !== '' && ! ModuleVisibilityManager::isModuleVisible($module)) {
            return false;
        }

        return PermissionRegistry::checkPermission($permission);
    }
}
