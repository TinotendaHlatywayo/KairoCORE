<?php

namespace App\Navigation;

use App\Filament\App\Pages\Academic\AcademicOperationsCenter;
use App\Filament\App\Pages\AdministrationDashboard;
use App\Filament\App\Pages\AdmissionSettingsPage;
use App\Filament\App\Pages\AnalyticsExplorer;
use App\Filament\App\Pages\AssessmentAnalyticsPage;
use App\Filament\App\Pages\AssessmentWorkspace;
use App\Filament\App\Pages\CommunicationCenter;
use App\Filament\App\Pages\EmailConfigurationPage;
use App\Filament\App\Pages\ExecutiveFinancialDashboard;
use App\Filament\App\Pages\Finance\CoreAccountingHub;
use App\Filament\App\Pages\Finance\ExpensesPurchasingHub;
use App\Filament\App\Pages\Finance\StudentBillingHub;
use App\Filament\App\Pages\GamificationSettingsPage;
use App\Filament\App\Pages\IssueBook;
use App\Filament\App\Pages\ManualMarkingPage;
use App\Filament\App\Pages\MyDay;
use App\Filament\App\Pages\ReportGeneratorPage;
use App\Filament\App\Pages\ReportingDashboard;
use App\Filament\App\Pages\SaaSBillingOverview;
use App\Filament\App\Pages\Schedule;
use App\Filament\App\Pages\SystemSettingsPage;
use App\Filament\App\Pages\TenantDataExportPage;
use App\Filament\App\Pages\VisualTimetableBuilder;
use App\Filament\App\Pages\WebsiteContentManager;
use App\Filament\App\Pages\WebsiteTemplatesHub;
use App\Filament\App\Resources\AcademicReportResource;
use App\Filament\App\Resources\AcademicYearResource;
use App\Filament\App\Resources\AccountResource;
use App\Filament\App\Resources\AssessmentMarkResource;
use App\Filament\App\Resources\AssessmentTypeResource;
use App\Filament\App\Resources\AssessmentWorkflowResource;
use App\Filament\App\Resources\AnnouncementResource;
use App\Filament\App\Resources\ApplicationResource;
use App\Filament\App\Resources\CampusResourceResource;
use App\Filament\App\Resources\CardTemplateResource;
use App\Filament\App\Resources\ChatThreadResource;
use App\Filament\App\Resources\ClassroomResource;
use App\Filament\App\Resources\ClinicVisitResource;
use App\Filament\App\Resources\CmsPageResource;
use App\Filament\App\Resources\CmsWebsiteResource;
use App\Filament\App\Resources\CourseResource;
use App\Filament\App\Resources\CustomRoleResource;
use App\Filament\App\Resources\DepartmentResource;
use App\Filament\App\Resources\DigitalAssessmentResource;
use App\Filament\App\Resources\DisciplinaryCaseResource;
use App\Filament\App\Resources\EmployeeAssetResource;
use App\Filament\App\Resources\EmployeeResource;
use App\Filament\App\Resources\EnterpriseReportTemplateResource;
use App\Filament\App\Resources\EventCalendarResource;
use App\Filament\App\Resources\ExpenseCategoryResource;
use App\Filament\App\Resources\ExpenseResource;
use App\Filament\App\Resources\ExpenseTypeResource;
use App\Filament\App\Resources\FeeCategoryResource;
use App\Filament\App\Resources\FinanceDocumentTemplateResource;
use App\Filament\App\Resources\FeePaymentSubmissionResource;
use App\Filament\App\Resources\FeeStructureResource;
use App\Filament\App\Resources\FeeWaiverResource;
use App\Filament\App\Resources\FixedAssetResource;
use App\Filament\App\Resources\GradingScaleResource;
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
use App\Filament\App\Resources\PlatformInboxResource;
use App\Filament\App\Resources\PollResource;
use App\Filament\App\Resources\PromotionWorkflowResource;
use App\Filament\App\Resources\QuestionBankResource;
use App\Filament\App\Resources\ReportTemplateResource;
use App\Filament\App\Resources\RevenueCategoryResource;
use App\Filament\App\Resources\RevenueStreamResource;
use App\Filament\App\Resources\SaaSMySubscriptionResource;
use App\Filament\App\Resources\SalaryGradeResource;
use App\Filament\App\Resources\SchoolBankAccountResource;
use App\Filament\App\Resources\StaffAttendanceResource;
use App\Filament\App\Resources\StaffLoanResource;
use App\Filament\App\Resources\StockAdjustmentResource;
use App\Filament\App\Resources\StudentMedicalRecordResource;
use App\Filament\App\Resources\StudentResource;
use App\Filament\App\Resources\SubjectResource;
use App\Filament\App\Resources\SystemAuditLogResource;
use App\Filament\App\Resources\TeacherAssignmentResource;
use App\Filament\App\Resources\TimeSlotResource;
use App\Filament\App\Resources\TimetableLessonResource;
use App\Filament\App\Resources\UserAccountResource;
use Modules\Inventory\Filament\Resources\AssetMaintenanceResource;
use Modules\Inventory\Filament\Resources\GoodsReceivedResource;
use Modules\Inventory\Filament\Resources\InventoryIssuanceResource;
use Modules\Inventory\Filament\Resources\InventoryItemResource;
use Modules\Inventory\Filament\Resources\ProcurementRequestResource;
use Modules\Inventory\Filament\Resources\PurchaseOrderResource;
use Modules\Inventory\Filament\Resources\SupplierResource;
use Modules\Knowledge\Filament\Resources\KnowledgeAssetResource;
use Modules\Knowledge\Filament\Resources\KnowledgeGalleryResource;
use Modules\Library\Filament\Resources\EResourceResource;
use Modules\Library\Filament\Resources\LibraryBookResource;
use Modules\Library\Filament\Resources\LibraryIssueResource;

/**
 * Central registry of the application's information architecture.
 *
 * Each "module" maps to a major area of the school ERP. The sidebar shows one
 * entry per module; the module header (rendered on every page inside that
 * module) exposes the module's pages as contextual tabs.
 *
 * Tabs reference real Filament Resource/Page classes so that URLs, permissions
 * and active-state detection are always derived from the live application
 * rather than hard-coded strings.
 */
class ModuleNavigation
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function modules(): array
    {
        return [
            [
                'slug' => 'admissions',
                'label' => __('Admissions'),
                'icon' => 'heroicon-o-clipboard-document-list',
                'description' => __('Process applications and configure the admission pipeline.'),
                'tabs' => [
                    ['label' => __('Applications'), 'resource' => ApplicationResource::class, 'icon' => 'heroicon-o-document-text'],
                    ['label' => __('Admission Settings'), 'page' => AdmissionSettingsPage::class, 'icon' => 'heroicon-o-cog-6-tooth'],
                ],
                'more' => [],
            ],

            [
                'slug' => 'students',
                'label' => __('Students'),
                'icon' => 'heroicon-o-user-group',
                'description' => __('Manage the student body and identification cards.'),
                'tabs' => [
                    ['label' => __('Students'), 'resource' => StudentResource::class],
                    ['label' => __('ID Card Templates'), 'resource' => CardTemplateResource::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'academics',
                'label' => __('Academics'),
                'icon' => 'heroicon-o-academic-cap',
                'description' => __('Level, classes, timetables and academic operations.'),
                'tabs' => [
                    ['label' => __('Overview'), 'page' => AcademicOperationsCenter::class],
                    ['label' => __('Level'), 'resource' => CourseResource::class],
                    ['label' => __('Subjects'), 'resource' => SubjectResource::class],
                    ['label' => __('Classes'), 'resource' => ClassroomResource::class],
                    ['label' => __('Academic Years'), 'resource' => AcademicYearResource::class],
                    ['label' => __('Timetables'), 'page' => VisualTimetableBuilder::class],
                    ['label' => __('Lessons'), 'resource' => TimetableLessonResource::class],
                    ['label' => __('Teacher Assignments'), 'resource' => TeacherAssignmentResource::class],
                    ['label' => __('Promotions'), 'resource' => PromotionWorkflowResource::class],
                ],
                'more' => [
                    ['label' => __('Time Slots'), 'resource' => TimeSlotResource::class],
                ],
            ],

            [
                'slug' => 'exams',
                'label' => __('Exams & Grading'),
                'icon' => 'heroicon-o-pencil-square',
                'description' => __('Assessments, grading scales, marks and report cards.'),
                'tabs' => [
                    ['label' => __('Assessment Workspace'), 'page' => AssessmentWorkspace::class],
                    ['label' => __('Report Cards'), 'resource' => AcademicReportResource::class],
                    ['label' => __('Digital Assessments'), 'resource' => DigitalAssessmentResource::class],
                    ['label' => __('Assessment Types'), 'resource' => AssessmentTypeResource::class],
                    ['label' => __('Grading Scales'), 'resource' => GradingScaleResource::class],
                    ['label' => __('Marks Entry'), 'resource' => AssessmentMarkResource::class],
                    ['label' => __('Manual Marking'), 'page' => ManualMarkingPage::class],
                    ['label' => __('Assessment Analytics'), 'page' => AssessmentAnalyticsPage::class],
                    ['label' => __('Gamification'), 'page' => GamificationSettingsPage::class],
                    ['label' => __('Question Bank'), 'resource' => QuestionBankResource::class],
                    ['label' => __('Report Templates'), 'resource' => ReportTemplateResource::class],
                    ['label' => __('Workflows'), 'resource' => AssessmentWorkflowResource::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'attendance',
                'label' => __('Attendance'),
                'icon' => 'heroicon-o-clipboard-document-check',
                'description' => __('Track attendance for staff and students.'),
                'tabs' => [
                    ['label' => __('Staff Attendance'), 'resource' => StaffAttendanceResource::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'finance',
                'label' => __('Finance'),
                'icon' => 'heroicon-o-banknotes',
                'description' => __('Fees, billing, payments, expenses and the general ledger.'),
                'tabs' => [
                    ['label' => __('Overview'), 'page' => ExecutiveFinancialDashboard::class, 'group' => __('Dashboard & Analytics')],
                    ['label' => __('Student Billing & Revenue'), 'page' => StudentBillingHub::class, 'group' => __('Student Billing & Revenue'), 'hub' => true],
                    ['label' => __('Fee Structures'), 'resource' => FeeStructureResource::class, 'group' => __('Student Billing & Revenue')],
                    ['label' => __('Fee Categories'), 'resource' => FeeCategoryResource::class, 'group' => __('Student Billing & Revenue')],
                    ['label' => __('Invoices'), 'resource' => InvoiceResource::class, 'group' => __('Student Billing & Revenue')],
                    ['label' => __('Payment Proofs'), 'resource' => FeePaymentSubmissionResource::class, 'group' => __('Student Billing & Revenue')],
                    ['label' => __('Fee Waivers'), 'resource' => FeeWaiverResource::class, 'group' => __('Student Billing & Revenue')],
                    ['label' => __('Expenses & Purchasing'), 'page' => ExpensesPurchasingHub::class, 'group' => __('Expenses & Purchasing'), 'hub' => true],
                    ['label' => __('Expenses'), 'resource' => ExpenseResource::class, 'group' => __('Expenses & Purchasing')],
                    ['label' => __('Expense Types'), 'resource' => ExpenseTypeResource::class, 'group' => __('Expenses & Purchasing')],
                    ['label' => __('Expense Categories'), 'resource' => ExpenseCategoryResource::class, 'group' => __('Expenses & Purchasing')],
                    ['label' => __('Core Accounting & Setup'), 'page' => CoreAccountingHub::class, 'group' => __('Core Accounting & Setup'), 'hub' => true],
                    ['label' => __('Ledger'), 'resource' => AccountResource::class, 'group' => __('Core Accounting & Setup')],
                    ['label' => __('Journal Entries'), 'resource' => JournalEntryResource::class, 'group' => __('Core Accounting & Setup')],
                    ['label' => __('Revenue Streams'), 'resource' => RevenueStreamResource::class, 'group' => __('Core Accounting & Setup')],
                    ['label' => __('Revenue Categories'), 'resource' => RevenueCategoryResource::class, 'group' => __('Core Accounting & Setup')],
                    ['label' => __('School Bank Accounts'), 'resource' => SchoolBankAccountResource::class, 'group' => __('Core Accounting & Setup')],
                    ['label' => __('Document Templates'), 'resource' => FinanceDocumentTemplateResource::class, 'group' => __('Core Accounting & Setup')],
                ],
                'more' => [],
            ],

            [
                'slug' => 'hr',
                'label' => __('HR & Payroll'),
                'icon' => 'heroicon-o-users',
                'description' => __('Employees, payroll, leave and staff administration.'),
                'tabs' => [
                    ['label' => __('Employees'), 'resource' => EmployeeResource::class],
                    ['label' => __('Payroll Periods'), 'resource' => PayrollPeriodResource::class],
                    ['label' => __('Leave Requests'), 'resource' => LeaveRequestResource::class],
                ],
                'more' => [
                    ['label' => __('Salary Grades'), 'resource' => SalaryGradeResource::class],
                    ['label' => __('Staff Loans'), 'resource' => StaffLoanResource::class],
                    ['label' => __('Staff Assets'), 'resource' => EmployeeAssetResource::class],
                    ['label' => __('Disciplinary Cases'), 'resource' => DisciplinaryCaseResource::class],
                ],
            ],

            [
                'slug' => 'inventory',
                'label' => __('Inventory & Procurement'),
                'icon' => 'heroicon-o-cube',
                'description' => __('Stock, fixed assets, suppliers and procurement workflows.'),
                'tabs' => [
                    ['label' => __('Inventory Items'), 'resource' => InventoryItemResource::class],
                    ['label' => __('Procurement Requests'), 'resource' => ProcurementRequestResource::class],
                    ['label' => __('Purchase Orders'), 'resource' => PurchaseOrderResource::class],
                    ['label' => __('Goods Received'), 'resource' => GoodsReceivedResource::class],
                ],
                'more' => [
                    ['label' => __('Suppliers'), 'resource' => SupplierResource::class],
                    ['label' => __('Issuance'), 'resource' => InventoryIssuanceResource::class],
                    ['label' => __('Stock Adjustments'), 'resource' => StockAdjustmentResource::class],
                    ['label' => __('Fixed Assets'), 'resource' => FixedAssetResource::class],
                    ['label' => __('Asset Maintenance'), 'resource' => AssetMaintenanceResource::class],
                ],
            ],

            [
                'slug' => 'library',
                'label' => __('Library'),
                'icon' => 'heroicon-o-book-open',
                'description' => __('Books, digital resources and circulation.'),
                'tabs' => [
                    ['label' => __('Books'), 'resource' => LibraryBookResource::class],
                    ['label' => __('eResources'), 'resource' => EResourceResource::class],
                    ['label' => __('Issue Book'), 'page' => IssueBook::class],
                    ['label' => __('Issues'), 'resource' => LibraryIssueResource::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'boarding',
                'label' => __('Boarding & Welfare'),
                'icon' => 'heroicon-o-home-modern',
                'description' => __('Hostels, room allocation and welfare oversight.'),
                'tabs' => [
                    ['label' => __('Hostels'), 'resource' => HostelResource::class],
                    ['label' => __('Rooms'), 'resource' => HostelRoomResource::class],
                    ['label' => __('Allocations'), 'resource' => HostelAllocationResource::class],
                    ['label' => __('Out Passes'), 'resource' => HostelOutPassResource::class],
                ],
                'more' => [
                    ['label' => __('Attendance'), 'resource' => HostelAttendanceResource::class],
                    ['label' => __('Inspections'), 'resource' => HostelInspectionResource::class],
                ],
            ],

            [
                'slug' => 'health',
                'label' => __('Health & Safety'),
                'icon' => 'heroicon-o-heart',
                'description' => __('Medical records and clinic visits.'),
                'tabs' => [
                    ['label' => __('Medical Records'), 'resource' => StudentMedicalRecordResource::class],
                    ['label' => __('Clinic Visits'), 'resource' => ClinicVisitResource::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'communication',
                'label' => __('Communication Center'),
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'description' => __('Connect, communicate and engage your school community.'),
                'landing' => CommunicationCenter::class,
                'tabs' => [
                    ['label' => __('Overview'), 'page' => CommunicationCenter::class, 'icon' => 'heroicon-o-squares-2x2'],
                    ['label' => __('Schedule'), 'page' => Schedule::class, 'icon' => 'heroicon-o-calendar-days'],
                    ['label' => __('My Day'), 'page' => MyDay::class, 'icon' => 'heroicon-o-check-circle'],
                    ['label' => __('Announcements'), 'resource' => AnnouncementResource::class],
                    ['label' => __('Resources'), 'resource' => CampusResourceResource::class],
                    ['label' => __('Chat'), 'resource' => ChatThreadResource::class],
                    ['label' => __('Events'), 'resource' => EventCalendarResource::class],
                ],
                'more' => [
                    ['label' => __('Helpdesk'), 'resource' => HelpdeskTicketResource::class],
                    ['label' => __('Polls & Surveys'), 'resource' => PollResource::class],
                    ['label' => __('Kairo CORE Messages'), 'resource' => PlatformInboxResource::class],
                ],
            ],

            [
                'slug' => 'lms',
                'label' => __('LMS'),
                'icon' => 'heroicon-o-play-circle',
                'description' => __('Homework and online learning activities.'),
                'tabs' => [
                    ['label' => __('Homework'), 'resource' => HomeworkResource::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'knowledge',
                'label' => __('Knowledge Hub'),
                'icon' => 'heroicon-o-light-bulb',
                'description' => __('Digital repository, documents and media galleries.'),
                'tabs' => [
                    ['label' => __('Knowledge Assets'), 'resource' => KnowledgeAssetResource::class],
                    ['label' => __('Galleries'), 'resource' => KnowledgeGalleryResource::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'website',
                'label' => __('Website'),
                'icon' => 'heroicon-o-globe-alt',
                'description' => __('Build and manage the public school website.'),
                'tabs' => [
                    ['label' => __('Templates'), 'page' => WebsiteTemplatesHub::class],
                    ['label' => __('Websites'), 'resource' => CmsWebsiteResource::class],
                    ['label' => __('Pages'), 'resource' => CmsPageResource::class],
                    ['label' => __('Content Manager'), 'page' => WebsiteContentManager::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'reports',
                'label' => __('Reports & Intelligence'),
                'icon' => 'heroicon-o-chart-bar',
                'description' => __('Compile reports, templates and explore analytics.'),
                'tabs' => [
                    ['label' => __('Dashboard'), 'page' => ReportingDashboard::class],
                    ['label' => __('Report Generator'), 'page' => ReportGeneratorPage::class],
                    ['label' => __('Templates'), 'resource' => EnterpriseReportTemplateResource::class],
                    ['label' => __('Generated Reports'), 'resource' => GeneratedReportResource::class],
                    ['label' => __('Analytics Explorer'), 'page' => AnalyticsExplorer::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'administration',
                'label' => __('System Administration'),
                'icon' => 'heroicon-o-wrench-screwdriver',
                'description' => __('Settings, roles, departments and audit trails.'),
                'tabs' => [
                    ['label' => __('Overview'), 'page' => AdministrationDashboard::class],
                    ['label' => __('System Settings'), 'page' => SystemSettingsPage::class],
                    ['label' => __('Email Configuration'), 'page' => EmailConfigurationPage::class],
                    ['label' => __('User Accounts'), 'resource' => UserAccountResource::class],
                    ['label' => __('Roles'), 'resource' => CustomRoleResource::class],
                    ['label' => __('Departments'), 'resource' => DepartmentResource::class],
                    ['label' => __('Audit Log'), 'resource' => SystemAuditLogResource::class],
                ],
                'more' => [
                    ['label' => __('Data Export'), 'page' => TenantDataExportPage::class],
                ],
            ],

            [
                'slug' => 'saas',
                'label' => __('Subscription & Billing'),
                'icon' => 'heroicon-o-credit-card',
                'description' => __('Your plan, invoices and payments.'),
                'tabs' => [
                    ['label' => __('Overview & Billing'), 'page' => SaaSBillingOverview::class],
                    ['label' => __('My Subscription'), 'resource' => SaaSMySubscriptionResource::class],
                ],
                'more' => [],
            ],
        ];
    }
}
