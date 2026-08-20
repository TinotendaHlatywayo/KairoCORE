<?php

namespace App\Navigation;

use App\Filament\App\Pages\Academic\AcademicOperationsCenter;
use App\Filament\App\Pages\AdministrationDashboard;
use App\Filament\App\Pages\AdmissionSettingsPage;
use App\Filament\App\Pages\AnalyticsExplorer;
use App\Filament\App\Pages\AssessmentWorkspace;
use App\Filament\App\Pages\CommunicationCenter;
use App\Filament\App\Pages\EmailConfigurationPage;
use App\Filament\App\Pages\ExecutiveFinancialDashboard;
use App\Filament\App\Pages\IssueBook;
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
use App\Filament\App\Resources\AnnouncementResource;
use App\Filament\App\Resources\ApplicationResource;
use App\Filament\App\Resources\AssessmentMarkResource;
use App\Filament\App\Resources\AssessmentTypeResource;
use App\Filament\App\Resources\AssessmentWorkflowResource;
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
use App\Filament\App\Resources\FixedAssetResource;
use App\Filament\App\Resources\GeneratedReportResource;
use App\Filament\App\Resources\GradingScaleResource;
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
                'label' => 'Admissions',
                'icon' => 'heroicon-o-clipboard-document-list',
                'description' => 'Process applications and configure the admission pipeline.',
                'tabs' => [
                    ['label' => 'Applications', 'resource' => ApplicationResource::class, 'icon' => 'heroicon-o-document-text'],
                    ['label' => 'Admission Settings', 'page' => AdmissionSettingsPage::class, 'icon' => 'heroicon-o-cog-6-tooth'],
                ],
                'more' => [],
            ],

            [
                'slug' => 'students',
                'label' => 'Students',
                'icon' => 'heroicon-o-user-group',
                'description' => 'Manage the student body and identification cards.',
                'tabs' => [
                    ['label' => 'Students', 'resource' => StudentResource::class],
                    ['label' => 'ID Card Templates', 'resource' => CardTemplateResource::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'academics',
                'label' => 'Academics',
                'icon' => 'heroicon-o-academic-cap',
                'description' => 'Level, classes, timetables and academic operations.',
                'tabs' => [
                    ['label' => 'Overview', 'page' => AcademicOperationsCenter::class],
                    ['label' => 'Level', 'resource' => CourseResource::class],
                    ['label' => 'Subjects', 'resource' => SubjectResource::class],
                    ['label' => 'Classes', 'resource' => ClassroomResource::class],
                    ['label' => 'Academic Years', 'resource' => AcademicYearResource::class],
                    ['label' => 'Timetables', 'page' => VisualTimetableBuilder::class],
                    ['label' => 'Lessons', 'resource' => TimetableLessonResource::class],
                    ['label' => 'Teacher Assignments', 'resource' => TeacherAssignmentResource::class],
                    ['label' => 'Promotions', 'resource' => PromotionWorkflowResource::class],
                ],
                'more' => [
                    ['label' => 'Time Slots', 'resource' => TimeSlotResource::class],
                ],
            ],

            [
                'slug' => 'exams',
                'label' => 'Exams & Grading',
                'icon' => 'heroicon-o-pencil-square',
                'description' => 'Assessments, grading scales, marks and report cards.',
                'tabs' => [
                    ['label' => 'Assessment Workspace', 'page' => AssessmentWorkspace::class],
                    ['label' => 'Assessment Types', 'resource' => AssessmentTypeResource::class],
                    ['label' => 'Grading Scales', 'resource' => GradingScaleResource::class],
                    ['label' => 'Marks Entry', 'resource' => AssessmentMarkResource::class],
                    ['label' => 'Report Cards', 'resource' => AcademicReportResource::class],
                ],
                'more' => [
                    ['label' => 'Report Templates', 'resource' => ReportTemplateResource::class],
                    ['label' => 'Workflows', 'resource' => AssessmentWorkflowResource::class],
                ],
            ],

            [
                'slug' => 'attendance',
                'label' => 'Attendance',
                'icon' => 'heroicon-o-clipboard-document-check',
                'description' => 'Track attendance for staff and students.',
                'tabs' => [
                    ['label' => 'Staff Attendance', 'resource' => StaffAttendanceResource::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'finance',
                'label' => 'Finance',
                'icon' => 'heroicon-o-banknotes',
                'description' => 'Fees, billing, payments, expenses and the general ledger.',
                'tabs' => [
                    ['label' => 'Overview', 'page' => ExecutiveFinancialDashboard::class],
                    ['label' => 'Fee Structures', 'resource' => FeeStructureResource::class],
                    ['label' => 'Invoices', 'resource' => InvoiceResource::class],
                    ['label' => 'Expenses', 'resource' => ExpenseResource::class],
                    ['label' => 'Ledger', 'resource' => AccountResource::class],
                ],
                'more' => [
                    ['label' => 'Fee Categories', 'resource' => FeeCategoryResource::class],
                    ['label' => 'Fee Waivers', 'resource' => FeeWaiverResource::class],
                    ['label' => 'Revenue Streams', 'resource' => RevenueStreamResource::class],
                    ['label' => 'Revenue Categories', 'resource' => RevenueCategoryResource::class],
                    ['label' => 'Expense Categories', 'resource' => ExpenseCategoryResource::class],
                    ['label' => 'Expense Types', 'resource' => ExpenseTypeResource::class],
                    ['label' => 'Journal Entries', 'resource' => JournalEntryResource::class],
                    ['label' => 'Payment Proofs', 'resource' => FeePaymentSubmissionResource::class],
                    ['label' => 'School Bank Accounts', 'resource' => SchoolBankAccountResource::class],
                ],
            ],

            [
                'slug' => 'hr',
                'label' => 'HR & Payroll',
                'icon' => 'heroicon-o-users',
                'description' => 'Employees, payroll, leave and staff administration.',
                'tabs' => [
                    ['label' => 'Employees', 'resource' => EmployeeResource::class],
                    ['label' => 'Payroll Periods', 'resource' => PayrollPeriodResource::class],
                    ['label' => 'Leave Requests', 'resource' => LeaveRequestResource::class],
                ],
                'more' => [
                    ['label' => 'Salary Grades', 'resource' => SalaryGradeResource::class],
                    ['label' => 'Staff Loans', 'resource' => StaffLoanResource::class],
                    ['label' => 'Staff Assets', 'resource' => EmployeeAssetResource::class],
                    ['label' => 'Disciplinary Cases', 'resource' => DisciplinaryCaseResource::class],
                ],
            ],

            [
                'slug' => 'inventory',
                'label' => 'Inventory & Procurement',
                'icon' => 'heroicon-o-cube',
                'description' => 'Stock, fixed assets, suppliers and procurement workflows.',
                'tabs' => [
                    ['label' => 'Inventory Items', 'resource' => InventoryItemResource::class],
                    ['label' => 'Procurement Requests', 'resource' => ProcurementRequestResource::class],
                    ['label' => 'Purchase Orders', 'resource' => PurchaseOrderResource::class],
                    ['label' => 'Goods Received', 'resource' => GoodsReceivedResource::class],
                ],
                'more' => [
                    ['label' => 'Suppliers', 'resource' => SupplierResource::class],
                    ['label' => 'Issuance', 'resource' => InventoryIssuanceResource::class],
                    ['label' => 'Stock Adjustments', 'resource' => StockAdjustmentResource::class],
                    ['label' => 'Fixed Assets', 'resource' => FixedAssetResource::class],
                    ['label' => 'Asset Maintenance', 'resource' => AssetMaintenanceResource::class],
                ],
            ],

            [
                'slug' => 'library',
                'label' => 'Library',
                'icon' => 'heroicon-o-book-open',
                'description' => 'Books, digital resources and circulation.',
                'tabs' => [
                    ['label' => 'Books', 'resource' => LibraryBookResource::class],
                    ['label' => 'eResources', 'resource' => EResourceResource::class],
                    ['label' => 'Issue Book', 'page' => IssueBook::class],
                    ['label' => 'Issues', 'resource' => LibraryIssueResource::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'boarding',
                'label' => 'Boarding & Welfare',
                'icon' => 'heroicon-o-home-modern',
                'description' => 'Hostels, room allocation and welfare oversight.',
                'tabs' => [
                    ['label' => 'Hostels', 'resource' => HostelResource::class],
                    ['label' => 'Rooms', 'resource' => HostelRoomResource::class],
                    ['label' => 'Allocations', 'resource' => HostelAllocationResource::class],
                    ['label' => 'Out Passes', 'resource' => HostelOutPassResource::class],
                ],
                'more' => [
                    ['label' => 'Attendance', 'resource' => HostelAttendanceResource::class],
                    ['label' => 'Inspections', 'resource' => HostelInspectionResource::class],
                ],
            ],

            [
                'slug' => 'health',
                'label' => 'Health & Safety',
                'icon' => 'heroicon-o-heart',
                'description' => 'Medical records and clinic visits.',
                'tabs' => [
                    ['label' => 'Medical Records', 'resource' => StudentMedicalRecordResource::class],
                    ['label' => 'Clinic Visits', 'resource' => ClinicVisitResource::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'communication',
                'label' => 'Communication Center',
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'description' => 'Connect, communicate and engage your school community.',
                'landing' => CommunicationCenter::class,
                'tabs' => [
                    ['label' => 'Overview', 'page' => CommunicationCenter::class, 'icon' => 'heroicon-o-squares-2x2'],
                    ['label' => 'Schedule', 'page' => Schedule::class, 'icon' => 'heroicon-o-calendar-days'],
                    ['label' => 'My Day', 'page' => MyDay::class, 'icon' => 'heroicon-o-check-circle'],
                    ['label' => 'Announcements', 'resource' => AnnouncementResource::class],
                    ['label' => 'Resources', 'resource' => CampusResourceResource::class],
                    ['label' => 'Chat', 'resource' => ChatThreadResource::class],
                    ['label' => 'Events', 'resource' => EventCalendarResource::class],
                ],
                'more' => [
                    ['label' => 'Helpdesk', 'resource' => HelpdeskTicketResource::class],
                    ['label' => 'Polls & Surveys', 'resource' => PollResource::class],
                    ['label' => 'SchoolCore Messages', 'resource' => PlatformInboxResource::class],
                ],
            ],

            [
                'slug' => 'lms',
                'label' => 'LMS',
                'icon' => 'heroicon-o-play-circle',
                'description' => 'Homework and online learning activities.',
                'tabs' => [
                    ['label' => 'Homework', 'resource' => HomeworkResource::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'knowledge',
                'label' => 'Knowledge Hub',
                'icon' => 'heroicon-o-light-bulb',
                'description' => 'Digital repository, documents and media galleries.',
                'tabs' => [
                    ['label' => 'Knowledge Assets', 'resource' => KnowledgeAssetResource::class],
                    ['label' => 'Galleries', 'resource' => KnowledgeGalleryResource::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'website',
                'label' => 'Website',
                'icon' => 'heroicon-o-globe-alt',
                'description' => 'Build and manage the public school website.',
                'tabs' => [
                    ['label' => 'Templates', 'page' => WebsiteTemplatesHub::class],
                    ['label' => 'Websites', 'resource' => CmsWebsiteResource::class],
                    ['label' => 'Pages', 'resource' => CmsPageResource::class],
                    ['label' => 'Content Manager', 'page' => WebsiteContentManager::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'reports',
                'label' => 'Reports & Intelligence',
                'icon' => 'heroicon-o-chart-bar',
                'description' => 'Compile reports, templates and explore analytics.',
                'tabs' => [
                    ['label' => 'Dashboard', 'page' => ReportingDashboard::class],
                    ['label' => 'Report Generator', 'page' => ReportGeneratorPage::class],
                    ['label' => 'Templates', 'resource' => EnterpriseReportTemplateResource::class],
                    ['label' => 'Generated Reports', 'resource' => GeneratedReportResource::class],
                    ['label' => 'Analytics Explorer', 'page' => AnalyticsExplorer::class],
                ],
                'more' => [],
            ],

            [
                'slug' => 'administration',
                'label' => 'System Administration',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'description' => 'Settings, roles, departments and audit trails.',
                'tabs' => [
                    ['label' => 'Overview', 'page' => AdministrationDashboard::class],
                    ['label' => 'System Settings', 'page' => SystemSettingsPage::class],
                    ['label' => 'Email Configuration', 'page' => EmailConfigurationPage::class],
                    ['label' => 'User Accounts', 'resource' => UserAccountResource::class],
                    ['label' => 'Roles', 'resource' => CustomRoleResource::class],
                    ['label' => 'Departments', 'resource' => DepartmentResource::class],
                    ['label' => 'Audit Log', 'resource' => SystemAuditLogResource::class],
                ],
                'more' => [
                    ['label' => 'Data Export', 'page' => TenantDataExportPage::class],
                ],
            ],

            [
                'slug' => 'saas',
                'label' => 'Subscription & Billing',
                'icon' => 'heroicon-o-credit-card',
                'description' => 'Your plan, invoices and payments.',
                'tabs' => [
                    ['label' => 'Overview & Billing', 'page' => SaaSBillingOverview::class],
                    ['label' => 'My Subscription', 'resource' => SaaSMySubscriptionResource::class],
                ],
                'more' => [],
            ],
        ];
    }
}
