<?php

namespace App\Security;

use App\Filament\App\Pages\ApplicationSuccess;
use App\Filament\App\Pages\AssessmentAnalyticsPage;
use App\Filament\App\Pages\BillingDocumentSettingsPage;
use App\Filament\App\Pages\Dashboard;
use App\Filament\App\Pages\GamificationSettingsPage;
use App\Filament\App\Pages\ManualMarkingPage;
use App\Filament\App\Pages\MyDay;
use App\Filament\App\Pages\PromoteStudents;
use App\Filament\App\Pages\Schedule;
use App\Filament\App\Pages\VisualCmsBuilder;
use App\Filament\App\Pages\WebsiteTemplatesHub;
use App\Filament\App\Resources\AccountResource;
use App\Filament\App\Resources\EnterpriseReportTemplateResource;
use App\Filament\App\Resources\ExpenseCategoryResource;
use App\Filament\App\Resources\ExpenseTypeResource;
use App\Filament\App\Resources\JournalEntryResource;
use App\Filament\App\Resources\PromotionWorkflowResource;
use App\Filament\App\Resources\ReportingWorkflowResource;
use App\Filament\App\Resources\RevenueCategoryResource;
use App\Filament\App\Resources\SupplierResource;
use App\Filament\App\Resources\TimeSlotResource;
use App\Filament\App\Resources\TimetableLessonResource;
use App\Navigation\ModuleNavigation;
use App\Services\ModuleVisibilityManager;
use Illuminate\Support\Str;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Inventory\Filament\Resources\InventoryItemResource;

/**
 * The single source of truth for what the workspace can do and who may do it.
 *
 * The catalogue mirrors the real information architecture exposed by
 * {@see ModuleNavigation}: MODULE -> CATEGORY (group) -> PAGE. Every page also
 * carries the operations ("actions") a person may perform on it, so the whole
 * system can be described as a single, navigable tree:
 *
 *     Finance
 *       └─ Core Accounting & Setup
 *            └─ School Bank Accounts        finance.school_bank_accounts.view
 *                                           finance.school_bank_accounts.create
 *                                           finance.school_bank_accounts.edit
 *                                           ...
 *
 * Permission keys use two grammars, both of which resolve through
 * {@see PermissionRegistry::userCan()}:
 *
 *   - `<module>.<action>`             a module-wide capability that covers
 *                                     every page of the module.
 *   - `<module>.<page>.<action>`      a capability for one page only.
 *
 * Because navigation, the permission pickers, the sidebar filter and the
 * Filament resource gates are all generated from this one tree, a page can
 * never appear in the navigation while being forbidden, and a module can never
 * be listed without at least one reachable page.
 */
final class CapabilityCatalog
{
    /**
     * Operations offered on every page unless the page narrows the list.
     */
    public const DEFAULT_PAGE_ACTIONS = ['view', 'create', 'edit', 'delete', 'export'];

    /**
     * Module-wide capabilities. `view_module` is the master switch that keeps
     * the module in the sidebar and unlocks every page of that module.
     */
    public const DEFAULT_MODULE_ACTIONS = [
        'view_module', 'create', 'edit', 'delete', 'export', 'import',
    ];

    /**
     * Extra operations that only make sense on specific pages.
     */
    public const EXTRA_ACTIONS = [
        'publish', 'approve', 'configure', 'run', 'mark', 'issue',
        'return', 'waive', 'override', 'reset', 'assign', 'lock',
    ];

    /** @var array<string, array<int, array<string, mixed>>>|null */
    private static ?array $tree = null;

    /** @var array<string, array{module: string, page: string}>|null */
    private static ?array $classMap = null;

    // ---------------------------------------------------------------------
    // Documentation
    // ---------------------------------------------------------------------

    /**
     * Plain-English explanation for every action verb, shown behind the
     * question-mark helper in the permission pickers.
     *
     * @return array<string, string>
     */
    public static function actionHelp(): array
    {
        return [
            'view_module' => __('See this module in your sidebar and open its landing page.'),
            'view' => __('Open this page and read the records it lists.'),
            'create' => __('Add new records.'),
            'edit' => __('Change records that already exist.'),
            'delete' => __('Permanently remove records. This cannot be undone.'),
            'export' => __('Download the data on this page as CSV or PDF.'),
            'import' => __('Bulk upload records from an Excel or CSV file.'),
            'publish' => __('Release this content so it becomes visible to other people.'),
            'approve' => __('Give final sign-off on records waiting for authorisation.'),
            'configure' => __('Change the settings and defaults used by this area.'),
            'run' => __('Start a process, job or batch that this page offers.'),
            'mark' => __('Capture marks, scores or ratings for a class or individual.'),
            'issue' => __('Hand items, stock, books or equipment over to a person.'),
            'return' => __('Take issued items, stock or books back in.'),
            'waive' => __('Cancel or reduce an amount that would otherwise be owed.'),
            'override' => __('Step outside the normal workflow, e.g. skip or reset a step.'),
            'reset' => __('Return a workflow or record back to an earlier state.'),
            'assign' => __('Give work to other people, not just yourself.'),
            'lock' => __('Freeze results or records so they can no longer be changed.'),
        ];
    }

    /**
     * What each page is actually for. Anything not listed here gets a
     * generated sentence built from its module, category and label, so every
     * permission in the system still carries meaningful help text.
     *
     * @return array<string, string>
     */
    public static function pagePurpose(): array
    {
        return [
            // ---- Academics -------------------------------------------------
            'App\Filament\App\Pages\Academic\AcademicOperationsCenter' => __('The academic control room: enrolment, promotion, progression and curriculum tasks for the current year in one place.'),
            'App\Filament\App\Pages\Academic\SetupStructureHub' => __('Everything that defines the shape of the school year — levels, subjects, classrooms and academic years.'),
            'App\Filament\App\Pages\Academic\TimetablesTeachingHub' => __('Builds the weekly teaching timetable and assigns teachers to classes and subjects.'),
            'App\Filament\App\Pages\Academic\ProgressionHub' => __('Moves students from one class to the next, including promotion runs and screening decisions.'),
            'App\Filament\App\Resources\CourseResource' => __('The levels and forms of the school, e.g. Grade 1, Grade 2, Form 1. Levels are the top of the academic structure.'),
            'App\Filament\App\Resources\SubjectResource' => __('The subjects taught at each level and the number of periods each one is allocated.'),
            'App\Filament\App\Resources\ClassroomResource' => __('The physical rooms available for teaching, their capacity and which room a class uses.'),
            'App\Filament\App\Resources\AcademicYearResource' => __('The academic years and terms of the school. Changing the active year changes what every other academic screen shows.'),
            'App\Filament\App\Resources\TeacherAssignmentResource' => __('Which teacher teaches which subject to which class, and how many periods per week.'),
            'App\Filament\App\Resources\TimeSlotResource' => __('The daily period structure (start time, end time, break) that timetables are built from.'),
            'App\Filament\App\Resources\TimetableLessonResource' => __('The individual timetable entries: which subject, teacher and room sit in which period.'),
            'App\Filament\App\Pages\VisualTimetableBuilder' => __('A drag-and-drop editor for laying out the weekly timetable.'),
            'App\Filament\App\Pages\TimetableViewerPage' => __('A read-only view of the timetable by class, teacher or room.'),
            'App\Filament\App\Resources\PromotionRunResource' => __('Bulk promotion of a whole cohort into the next class at the end of a year.'),
            'App\Filament\App\Resources\ScreeningRunResource' => __('Placement and screening decisions used when admitting or moving students.'),
            'App\Filament\App\Resources\PromotionWorkflowResource' => __('The step-by-step approvals a promotion run must pass through before it takes effect.'),
            'App\Filament\App\Pages\PromoteStudents' => __('Promote an individual student to the next class without running a bulk promotion.'),

            // ---- Exams & Grading -------------------------------------------
            'App\Filament\App\Pages\Exams\AssessmentCenterHub' => __('Sets up how the school assesses students: assessment types, digital assessments and the question bank.'),
            'App\Filament\App\Pages\Exams\GradingMarksHub' => __('Enters and reviews marks, grading scales and performance analytics for every class.'),
            'App\Filament\App\Pages\Exams\ReportsPublishingHub' => __('Builds report cards, manages report templates and controls what is released to the student portal.'),
            'App\Filament\App\Resources\AssessmentMarkResource' => __('The individual marks captured for each student in each subject.'),
            'App\Filament\App\Resources\GradingScaleResource' => __('The grading boundaries (A1, B2, C3 …) that turn marks into grades.'),
            'App\Filament\App\Resources\AssessmentTypeResource' => __('The kinds of assessment used at the school, e.g. coursework, exam, quiz.'),
            'App\Filament\App\Resources\DigitalAssessmentResource' => __('Online assessments students sit from the portal, including publishing and manual marking.'),
            'App\Filament\App\Resources\QuestionBankResource' => __('The reusable pool of questions that online assessments are built from.'),
            'App\Filament\App\Pages\ManualMarkingPage' => __('Marks free-text and uploaded answers by hand for online assessments.'),
            'App\Filament\App\Pages\AssessmentAnalyticsPage' => __('Question-by-question results for one online assessment.'),
            'App\Filament\App\Pages\PerformanceAnalyticsPage' => __('Cohort performance: averages, spread and weakest subjects per class.'),
            'App\Filament\App\Resources\AcademicReportResource' => __('The report cards produced for students at the end of a term.'),
            'App\Filament\App\Resources\ReportTemplateResource' => __('The layouts and subject blocks that report cards are rendered from.'),
            'App\Filament\App\Resources\AssessmentWorkflowResource' => __('The approvals a set of results must pass through before they are published.'),
            'App\Filament\App\Pages\PortalReportsPublisher' => __('Chooses which report cards are released to students, and when.'),
            'App\Filament\App\Resources\ReportingWorkflowResource' => __('The approval steps a report card passes through before release.'),

            // ---- Finance ----------------------------------------------------
            'App\Filament\App\Pages\ExecutiveFinancialDashboard' => __('A one-screen view of income, expenditure, debtors and cash position.'),
            'App\Filament\App\Pages\Finance\FinancialStatementPage' => __('Trial balance, income statement, balance sheet and cash flow.'),
            'App\Filament\App\Pages\Finance\StudentBillingHub' => __('Fee structures, invoices, collections, waivers and the student billing ledger.'),
            'App\Filament\App\Pages\Finance\FeeCollectionsPage' => __('Takes payments at the counter and produces the daily cash-up figures.'),
            'App\Filament\App\Pages\Finance\StudentFinancialHistoryPage' => __('The complete billing and payment history for each student and guardian.'),
            'App\Filament\App\Pages\Finance\ExpensesPurchasingHub' => __('Records running costs and the purchasing requests that pay for them.'),
            'App\Filament\App\Pages\Finance\CoreAccountingHub' => __('The accounting foundation: chart of accounts, journals, bank accounts, revenue and document templates.'),
            'App\Filament\App\Pages\BillingDocumentSettingsPage' => __('Letterhead, numbering and wording used on invoices, receipts and statements.'),
            'App\Filament\App\Resources\FeeCategoryResource' => __('The kinds of fee charged, e.g. tuition, boarding, transport.'),
            'App\Filament\App\Resources\FeeStructureResource' => __('What each fee category costs for each level and class.'),
            'App\Filament\App\Resources\InvoiceResource' => __('The invoices raised against students and guardians.'),
            'App\Filament\App\Resources\FeePaymentSubmissionResource' => __('Proof of payment uploaded by parents, pending verification.'),
            'App\Filament\App\Resources\FeeWaiverResource' => __('Reductions and full waivers granted on a student\u2019s fees.'),
            'App\Filament\App\Resources\ExpenseResource' => __('Money spent by the school and who authorised it.'),
            'App\Filament\App\Resources\AccountResource' => __('The chart of accounts — every ledger the school posts to.'),
            'App\Filament\App\Resources\JournalEntryResource' => __('Double-entry journal records. Once posted these are the permanent financial history.'),
            'App\Filament\App\Resources\ExpenseCategoryResource' => __('The groupings expenses are reported under.'),
            'App\Filament\App\Resources\ExpenseTypeResource' => __('The specific kinds of expense within a category.'),
            'App\Filament\App\Resources\RevenueStreamResource' => __('The sources of school income, e.g. fees, donations, hire.'),
            'App\Filament\App\Resources\RevenueCategoryResource' => __('The groupings revenue is reported under.'),
            'App\Filament\App\Resources\SchoolBankAccountResource' => __('The school\u2019s bank accounts, used for reconciliation and payment instructions.'),
            'App\Filament\App\Resources\FinanceDocumentTemplateResource' => __('The branded layouts used for invoices, receipts and statements.'),

            // ---- HR & Payroll -----------------------------------------------
            'App\Filament\App\Pages\Hr\StaffDirectoryHub' => __('The staff record: employees, their assets, and any disciplinary cases.'),
            'App\Filament\App\Pages\Hr\PayrollCompensationHub' => __('Runs and approves payroll, and maintains salary grades and staff loans.'),
            'App\Filament\App\Pages\Hr\AttendanceLeaveHub' => __('Staff leave requests and daily staff attendance.'),
            'App\Filament\App\Resources\EmployeeResource' => __('The official staff record: contracts, designations, departments and system roles.'),
            'App\Filament\App\Resources\EmployeeAssetResource' => __('Equipment and assets issued to staff, and who is accountable for them.'),
            'App\Filament\App\Resources\DisciplinaryCaseResource' => __('Formal warnings and disciplinary cases raised against staff.'),
            'App\Filament\App\Resources\PayrollPeriodResource' => __('The pay periods payroll is run for, and the resulting payslips.'),
            'App\Filament\App\Resources\SalaryGradeResource' => __('The salary bands staff are placed on.'),
            'App\Filament\App\Resources\StaffLoanResource' => __('Advances and loans given to staff, and their repayment schedule.'),
            'App\Filament\App\Resources\LeaveRequestResource' => __('Leave applied for by staff, and the approvals that act on it.'),
            'App\Filament\App\Resources\StaffAttendanceResource' => __('The daily sign-in record for non-teaching and support staff.'),

            // ---- Inventory & Procurement ------------------------------------
            'App\Filament\App\Pages\Inventory\StockInventoryHub' => __('What the school holds: items, what has been issued out, and stock adjustments.'),
            'App\Filament\App\Pages\Inventory\ProcurementHub' => __('The purchasing cycle: requests, orders, suppliers and goods received.'),
            'App\Filament\App\Pages\Inventory\FixedAssetsHub' => __('Long-lived assets such as furniture, vehicles and equipment, and their maintenance.'),
            'Modules\Inventory\Filament\Resources\InventoryItemResource' => __('The master catalogue of everything the school can hold in stock.'),
            'Modules\Inventory\Filament\Resources\InventoryIssuanceResource' => __('Stock handed out to staff, students or departments.'),
            'Modules\Inventory\Filament\Resources\StockAdjustmentResource' => __('Corrections to stock levels after a physical count or damage.'),
            'Modules\Inventory\Filament\Resources\ProcurementRequestResource' => __('Requests raised when something needs buying.'),
            'Modules\Inventory\Filament\Resources\PurchaseOrderResource' => __('The formal order sent to a supplier.'),
            'Modules\Inventory\Filament\Resources\GoodsReceivedResource' => __('What actually arrived against a purchase order, and what was rejected.'),
            'Modules\Inventory\Filament\Resources\SupplierResource' => __('The suppliers the school buys from, with their terms and contacts.'),
            'App\Filament\App\Resources\SupplierResource' => __('The suppliers the school buys from, with their terms and contacts.'),
            'App\Filament\App\Resources\FixedAssetResource' => __('The register of long-lived assets and their depreciation schedules.'),
            'Modules\Inventory\Filament\Resources\AssetMaintenanceResource' => __('Repairs, servicing and condition checks for fixed assets.'),

            // ---- Library ----------------------------------------------------
            'App\Filament\App\Pages\Library\CatalogueHub' => __('The library catalogue: books and digital resources held.'),
            'App\Filament\App\Pages\CirculationHub' => __('Lending: issuing books to borrowers, taking returns and renewals.'),
            'App\Filament\App\Pages\IssueBook' => __('The counter screen for issuing a book to a student or staff member.'),
            'Modules\Library\Filament\Resources\LibraryBookResource' => __('The printed book catalogue, including copies and availability.'),
            'Modules\Library\Filament\Resources\EResourceResource' => __('Digital resources such as e-books, links and media.'),
            'Modules\Library\Filament\Resources\LibraryIssueResource' => __('Every loan, return and renewal recorded at the library desk.'),
            'App\Filament\App\Pages\Knowledge\KnowledgeHub' => __('The shared knowledge repository: reference documents, policies and galleries.'),
            'Modules\Knowledge\Filament\Resources\KnowledgeAssetResource' => __('Documents and files published to the knowledge repository.'),
            'Modules\Knowledge\Filament\Resources\KnowledgeGalleryResource' => __('Photo and media galleries attached to the knowledge repository.'),

            // ---- Boarding & Welfare -----------------------------------------
            'App\Filament\App\Pages\Boarding\AccommodationHub' => __('Hostels, rooms, bed allocation and occupancy.'),
            'App\Filament\App\Pages\Boarding\WelfareHub' => __('Daily welfare of boarders: roll call, out-passes and room inspections.'),
            'App\Filament\App\Resources\HostelResource' => __('The hostels the school runs.'),
            'App\Filament\App\Resources\HostelRoomResource' => __('Individual rooms within a hostel and their bed capacity.'),
            'App\Filament\App\Resources\HostelAllocationResource' => __('Which bed belongs to which boarder.'),
            'App\Filament\App\Resources\HostelOutPassResource' => __('Permission for a boarder to leave the grounds, and their return.'),
            'App\Filament\App\Resources\HostelAttendanceResource' => __('Nightly roll call for boarders.'),
            'App\Filament\App\Resources\HostelInspectionResource' => __('Scheduled checks of dormitory rooms for safety and upkeep.'),

            // ---- Health & Safety ---------------------------------------------
            'App\Filament\App\Pages\Health\HealthRecordsHub' => __('Student medical records and clinic visits.'),
            'App\Filament\App\Resources\StudentMedicalRecordResource' => __('The long-term medical and allergy history kept for each student.'),
            'App\Filament\App\Resources\ClinicVisitResource' => __('Each visit to the clinic and what treatment or medication was given.'),

            // ---- Communication Center -----------------------------------------
            'App\Filament\App\Pages\CommunicationCenter' => __('The communication landing page: announcements, conversations, polls and support in one view.'),
            'App\Filament\App\Pages\Communication\ScheduleTasksHub' => __('The shared calendar and the personal task list.'),
            'App\Filament\App\Pages\Schedule' => __('The school-wide calendar of events, deadlines and meetings.'),
            'App\Filament\App\Pages\MyDay' => __('A personal view of today: my tasks, my lessons and what is due.'),
            'App\Filament\App\Pages\Communication\CommunityHub' => __('Announcements, events, chat channels, shared resources and polls.'),
            'App\Filament\App\Pages\Communication\HelpInboxHub' => __('Support tickets raised by staff and messages from the platform team.'),
            'App\Filament\App\Resources\AnnouncementResource' => __('Notices published to staff, students and parents.'),
            'App\Filament\App\Resources\EventCalendarResource' => __('Entries on the school calendar.'),
            'App\Filament\App\Resources\ChatThreadResource' => __('Conversations between staff, and between staff and groups such as departments.'),
            'App\Filament\App\Resources\CampusResourceResource' => __('Shared documents and links made available to the school community.'),
            'App\Filament\App\Resources\PollResource' => __('Polls and surveys run with staff, students or parents.'),
            'App\Filament\App\Resources\HelpdeskTicketResource' => __('Requests for help raised from inside the workspace.'),
            'App\Filament\App\Resources\PlatformInboxResource' => __('Messages from the Kairo CORE platform team.'),

            // ---- LMS ----------------------------------------------------------
            'App\Filament\App\Pages\Lms\LmsHub' => __('Homework and lessons set for students to work through online.'),
            'App\Filament\App\Resources\HomeworkResource' => __('Homework and assignments given to classes.'),

            // ---- Website -------------------------------------------------------
            'App\Filament\App\Pages\Website\WebsiteTemplatesHub' => __('The design side of the public website: site templates, websites and page structure.'),
            'App\Filament\App\Pages\WebsiteTemplatesHub' => __('The design side of the public website: site templates, websites and page structure.'),
            'App\Filament\App\Pages\WebsiteContentManager' => __('Writing and publishing the actual content of the public website.'),
            'App\Filament\App\Pages\VisualCmsBuilder' => __('A drag-and-drop editor for laying out public website pages.'),
            'App\Filament\App\Resources\CmsWebsiteResource' => __('The public websites this school runs, and which template each uses.'),
            'App\Filament\App\Resources\CmsPageResource' => __('The individual pages of the public website.'),

            // ---- Reports -------------------------------------------------------
            'App\Filament\App\Pages\ReportingDashboard' => __('A summary of the reports available and the figures most often asked for.'),
            'App\Filament\App\Pages\Reports\ReportsHub' => __('Generate reports, browse what has been generated, and explore data.'),
            'App\Filament\App\Pages\ReportGeneratorPage' => __('Builds a report by choosing a template and the data to include.'),
            'App\Filament\App\Pages\AnalyticsExplorer' => __('Free-form querying across the school\u2019s data.'),
            'App\Filament\App\Resources\GeneratedReportResource' => __('Reports that have already been generated, and the files they produced.'),
            'App\Filament\App\Resources\EnterpriseReportTemplateResource' => __('The saved report layouts that the generator runs from.'),
            'App\Filament\App\Resources\ReportingWorkflowResource' => __('The approval steps a report passes through before it is circulated.'),

            // ---- System Administration -----------------------------------------
            'App\Filament\App\Pages\AdministrationDashboard' => __('The system administration landing page.'),
            'App\Filament\App\Pages\Administration\SystemSettingsHub' => __('School-wide settings: modules, branding, email sending, locale and maintenance.'),
            'App\Filament\App\Pages\Administration\UserManagementHub' => __('User accounts, roles and departments — who can do what.'),
            'App\Filament\App\Pages\Administration\AuditHub' => __('The audit trail and full data export for the tenant.'),
            'App\Filament\App\Pages\SystemSettingsPage' => __('The settings form behind the System Settings hub.'),
            'App\Filament\App\Pages\SystemSettingsHub' => __('School-wide settings: modules, branding, email sending, locale and maintenance.'),
            'App\Filament\App\Pages\EmailConfigurationPage' => __('Outgoing school email: sender identity, signatures and test messages.'),
            'App\Filament\App\Pages\TenantDataExportPage' => __('Download a complete copy of this school\u2019s data.'),
            'App\Filament\App\Resources\UserAccountResource' => __('Every user account in the school, and the approval queue for new registrations.'),
            'App\Filament\App\Resources\CustomRoleResource' => __('Roles and the exact permissions each one grants.'),
            'App\Filament\App\Resources\DepartmentResource' => __('Departments such as Finance or Clinic, and the permissions they carry.'),
            'App\Filament\App\Resources\SystemAuditLogResource' => __('A record of sensitive actions taken in the system.'),

            // ---- Subscription & Billing ------------------------------------------
            'App\Filament\App\Pages\SaaSBillingOverview' => __('The current plan, usage and renewal date.'),
            'App\Filament\App\Pages\SaaS\SaaSHub' => __('Subscription settings and payment history for this school.'),
            'App\Filament\App\Resources\SaaSMySubscriptionResource' => __('The subscription record this school is on.'),

            // ---- Students & Admissions ---------------------------------------------
            'App\Filament\App\Resources\StudentResource' => __('The student directory: admission, class, contact and status for every pupil.'),
            'App\Filament\App\Resources\CardTemplateResource' => __('The design of the student identity cards.'),
            'App\Filament\App\Resources\ApplicationResource' => __('Applications received from prospective students and their progress through admissions.'),
            'App\Filament\App\Pages\AdmissionSettingsPage' => __('The stages of the admissions pipeline and the forms applicants complete.'),

            // ---- Shared, always-available staff pages -------------------------------
            'App\Filament\App\Pages\Dashboard' => __('Your personal home page: what needs your attention today.'),
            'App\Filament\App\Pages\MyDay' => __('A personal view of today: my tasks, my lessons and what is due.'),
            'App\Filament\App\Pages\Schedule' => __('The school-wide calendar of events, deadlines and meetings.'),
            'App\Filament\App\Pages\ApplicationSuccess' => __('Confirmation shown after submitting a registration or application.'),
        ];
    }

    /**
     * Per-page action lists, for pages whose real operations differ from the
     * default view/create/edit/delete/export set. The `actions` key replaces the
     * default set entirely; the `add` key appends to it.
     *
     * @return array<string, array{actions?: array<int, string>, add?: array<int, string>}>
     */
    public static function pageActions(): array
    {
        return [
            // Read-only reporting pages never offer create/edit/delete.
            'App\Filament\App\Pages\Finance\FinancialStatementPage' => ['actions' => ['view', 'export']],
            'App\Filament\App\Pages\Exams\PerformanceAnalyticsPage' => ['actions' => ['view', 'export']],
            'App\Filament\App\Pages\AssessmentAnalyticsPage' => ['actions' => ['view', 'export']],
            'App\Filament\App\Pages\AnalyticsExplorer' => ['actions' => ['view', 'export', 'run']],
            'App\Filament\App\Pages\ReportingDashboard' => ['actions' => ['view']],
            'App\Filament\App\Pages\AdministrationDashboard' => ['actions' => ['view']],
            'App\Filament\App\Pages\CommunicationCenter' => ['actions' => ['view', 'create']],
            'App\Filament\App\Pages\MyDay' => ['actions' => ['view', 'create', 'edit', 'delete']],
            'App\Filament\App\Pages\Schedule' => ['actions' => ['view', 'create', 'edit', 'delete', 'export']],
            'App\Filament\App\Pages\ApplicationSuccess' => ['actions' => ['view']],
            'App\Filament\App\Pages\TimetableViewerPage' => ['actions' => ['view', 'export']],

            // Hubs are landing pages; they hold the links, the CRUD lives below.
            'App\Filament\App\Pages\Academic\AcademicOperationsCenter' => ['actions' => ['view']],
            'App\Filament\App\Pages\Academic\SetupStructureHub' => ['actions' => ['view']],
            'App\Filament\App\Pages\Academic\TimetablesTeachingHub' => ['actions' => ['view', 'run']],
            'App\Filament\App\Pages\Academic\ProgressionHub' => ['actions' => ['view', 'run', 'approve']],
            'App\Filament\App\Pages\Exams\AssessmentCenterHub' => ['actions' => ['view', 'run']],
            'App\Filament\App\Pages\Exams\GradingMarksHub' => ['actions' => ['view', 'mark', 'approve']],
            'App\Filament\App\Pages\Exams\ReportsPublishingHub' => ['actions' => ['view', 'run']],
            'App\Filament\App\Pages\Finance\CoreAccountingHub' => ['actions' => ['view', 'configure']],
            'App\Filament\App\Pages\Finance\StudentBillingHub' => ['actions' => ['view', 'run']],
            'App\Filament\App\Pages\Finance\ExpensesPurchasingHub' => ['actions' => ['view', 'run']],
            'App\Filament\App\Pages\Hr\StaffDirectoryHub' => ['actions' => ['view']],
            'App\Filament\App\Pages\Hr\PayrollCompensationHub' => ['actions' => ['view', 'run', 'approve']],
            'App\Filament\App\Pages\Hr\AttendanceLeaveHub' => ['actions' => ['view', 'approve']],
            'App\Filament\App\Pages\Inventory\StockInventoryHub' => ['actions' => ['view', 'run']],
            'App\Filament\App\Pages\Inventory\ProcurementHub' => ['actions' => ['view', 'run', 'approve']],
            'App\Filament\App\Pages\Inventory\FixedAssetsHub' => ['actions' => ['view', 'run']],
            'App\Filament\App\Pages\Library\CatalogueHub' => ['actions' => ['view']],
            'App\Filament\App\Pages\Library\CirculationHub' => ['actions' => ['view', 'issue', 'return']],
            'App\Filament\App\Pages\Knowledge\KnowledgeHub' => ['actions' => ['view']],
            'App\Filament\App\Pages\Boarding\AccommodationHub' => ['actions' => ['view', 'assign']],
            'App\Filament\App\Pages\Boarding\WelfareHub' => ['actions' => ['view', 'run', 'override']],
            'App\Filament\App\Pages\Health\HealthRecordsHub' => ['actions' => ['view']],
            'App\Filament\App\Pages\Communication\ScheduleTasksHub' => ['actions' => ['view', 'create', 'edit', 'delete']],
            'App\Filament\App\Pages\Communication\CommunityHub' => ['actions' => ['view', 'create', 'edit', 'delete']],
            'App\Filament\App\Pages\Communication\HelpInboxHub' => ['actions' => ['view', 'create']],
            'App\Filament\App\Pages\Website\WebsiteTemplatesHub' => ['actions' => ['view', 'create', 'edit', 'delete', 'configure']],
            'App\Filament\App\Pages\WebsiteTemplatesHub' => ['actions' => ['view', 'create', 'edit', 'delete', 'configure']],
            'App\Filament\App\Pages\Administration\SystemSettingsHub' => ['actions' => ['view', 'configure']],
            'App\Filament\App\Pages\SystemSettingsHub' => ['actions' => ['view', 'configure']],
            'App\Filament\App\Pages\Administration\UserManagementHub' => ['actions' => ['view']],
            'App\Filament\App\Pages\Administration\AuditHub' => ['actions' => ['view', 'export']],
            'App\Filament\App\Pages\SaaS\SaaSHub' => ['actions' => ['view', 'configure']],
            'App\Filament\App\Pages\Reports\ReportsHub' => ['actions' => ['view', 'run']],
            'App\Filament\App\Pages\WebsiteContentManager' => ['actions' => ['view', 'create', 'edit', 'delete', 'publish']],
            'App\Filament\App\Pages\VisualCmsBuilder' => ['add' => ['publish', 'configure']],

            // Pages that carry a distinctly named operation of their own.
            'App\Filament\App\Pages\Exams\PortalReportsPublisher' => ['add' => ['publish', 'approve']],
            'App\Filament\App\Pages\ManualMarkingPage' => ['add' => ['mark', 'approve']],
            'App\Filament\App\Pages\PromoteStudents' => ['add' => ['run', 'approve']],
            'App\Filament\App\Pages\IssueBook' => ['add' => ['issue', 'return', 'waive']],
            'App\Filament\App\Pages\VisualTimetableBuilder' => ['add' => ['run', 'override']],
            'App\Filament\App\Pages\BillingDocumentSettingsPage' => ['add' => ['configure']],
            'App\Filament\App\Pages\AdmissionSettingsPage' => ['add' => ['configure']],
            'App\Filament\App\Pages\EmailConfigurationPage' => ['actions' => ['view', 'create', 'edit', 'delete', 'configure']],
            'App\Filament\App\Pages\GamificationSettingsPage' => ['actions' => ['view', 'edit', 'configure', 'override']],
            'App\Filament\App\Resources\JournalEntryResource' => ['add' => ['lock', 'override']],
            'App\Filament\App\Resources\AcademicReportResource' => ['add' => ['publish', 'approve', 'lock']],
            'App\Filament\App\Resources\AssessmentMarkResource' => ['add' => ['mark', 'lock', 'override']],
            'App\Filament\App\Resources\PayrollPeriodResource' => ['add' => ['run', 'approve', 'lock']],
            'App\Filament\App\Resources\LibraryIssueResource' => ['add' => ['issue', 'return', 'waive']],
            'App\Filament\App\Resources\HostelOutPassResource' => ['add' => ['issue', 'override']],
            'App\Filament\App\Resources\HostelAttendanceResource' => ['add' => ['run', 'override']],
            'App\Filament\App\Resources\LeaveRequestResource' => ['add' => ['approve', 'override']],
            'App\Filament\App\Resources\ProcurementRequestResource' => ['add' => ['approve', 'override']],
            'App\Filament\App\Resources\PurchaseOrderResource' => ['add' => ['approve', 'override']],
            'App\Filament\App\Resources\FeePaymentSubmissionResource' => ['add' => ['approve']],
            'App\Filament\App\Resources\FeeWaiverResource' => ['add' => ['waive', 'approve']],
            'App\Filament\App\Resources\PromotionRunResource' => ['add' => ['run', 'approve']],
            'App\Filament\App\Resources\ScreeningRunResource' => ['add' => ['run', 'approve']],
            'App\Filament\App\Resources\CardTemplateResource' => ['add' => ['configure']],
            'App\Filament\App\Resources\CustomRoleResource' => ['add' => ['configure']],
            'App\Filament\App\Resources\DepartmentResource' => ['add' => ['configure']],
            'App\Filament\App\Resources\SystemAuditLogResource' => ['add' => ['lock']],
            'App\Filament\App\Resources\QuestionBankResource' => ['add' => ['import', 'publish']],
            'App\Filament\App\Resources\DigitalAssessmentResource' => ['add' => ['publish', 'mark', 'override']],
            'App\Filament\App\Resources\GradingScaleResource' => ['add' => ['configure', 'lock']],
            'App\Filament\App\Resources\ApplicationResource' => ['add' => ['approve', 'override']],
            'App\Filament\App\Resources\UserAccountResource' => ['add' => ['approve', 'override']],
            'App\Filament\App\Resources\EmployeeResource' => ['add' => ['configure', 'override']],
            'App\Filament\App\Resources\SchoolBankAccountResource' => ['add' => ['configure', 'override']],
            'App\Filament\App\Resources\FixedAssetResource' => ['add' => ['run', 'override', 'lock']],
            'Modules\Inventory\Filament\Resources\AssetMaintenanceResource' => ['add' => ['run']],
            'Modules\Knowledge\Filament\Resources\KnowledgeAssetResource' => ['add' => ['publish', 'approve']],
            'App\Filament\App\Resources\ChatThreadResource' => ['add' => ['assign']],
            'App\Filament\App\Resources\HelpdeskTicketResource' => ['add' => ['assign', 'override']],
            'App\Filament\App\Resources\PollResource' => ['add' => ['publish']],
            'App\Filament\App\Resources\EventCalendarResource' => ['add' => ['assign']],
        ];
    }

    /**
     * Pages that accept a spreadsheet upload, so they carry an `import`
     * operation that the CSV wizard checks before offering itself.
     *
     * Derived from a list rather than repeated in the per-class overrides above,
     * because importing is a property of the page's data entry, not of anything
     * the page does.
     *
     * @var array<int, string>
     */
    /**
     * Pages that a school can switch off individually, and the setting key that
     * holds that switch.
     *
     * These are tenant toggles — "is this part of my school switched on?" — and
     * are separate from permissions, which answer "may this person?". A page
     * listed here is only reachable when both say yes.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const PAGE_TENANT_TOGGLE = [
        'academics.classrooms' => ['academics', 'streams'],
        'academics.level' => ['academics', 'courses'],
        'academics.promotion_runs' => ['academics', 'promotion'],
        'academics.screening_runs' => ['academics', 'screening'],
        'academics.overview' => ['academics', 'operations'],
        'admissions.applications' => ['admissions', 'applications'],
        'admissions.admission_settings' => ['admissions', 'settings'],
    ];

    private const IMPORT_CAPABLE_PAGES = [
        'App\Filament\App\Resources\CourseResource',
        'App\Filament\App\Resources\SubjectResource',
        'App\Filament\App\Resources\ClassroomResource',
        'App\Filament\App\Resources\AcademicYearResource',
        'App\Filament\App\Resources\StudentResource',
        'App\Filament\App\Resources\DepartmentResource',
        'App\Filament\App\Resources\EmployeeResource',
        'App\Filament\App\Resources\LeaveRequestResource',
        'App\Filament\App\Resources\StaffAttendanceResource',
        'App\Filament\App\Resources\StaffLoanResource',
        'App\Filament\App\Resources\SalaryGradeResource',
        'App\Filament\App\Resources\FixedAssetResource',
        'App\Filament\App\Resources\StockAdjustmentResource',
        'App\Filament\App\Resources\HostelResource',
        'App\Filament\App\Resources\HostelRoomResource',
        'App\Filament\App\Resources\HostelAllocationResource',
        'App\Filament\App\Resources\FeeStructureResource',
        'App\Filament\App\Resources\FeeCategoryResource',
        'App\Filament\App\Resources\ExpenseResource',
        'App\Filament\App\Resources\ExpenseCategoryResource',
        'App\Filament\App\Resources\ExpenseTypeResource',
        'App\Filament\App\Resources\RevenueStreamResource',
        'App\Filament\App\Resources\RevenueCategoryResource',
        'App\Filament\App\Resources\SupplierResource',
        'App\Filament\App\Pages\Finance\StudentFinancialHistoryPage',
        'Modules\Inventory\Filament\Resources\InventoryItemResource',
        'Modules\Inventory\Filament\Resources\PurchaseOrderResource',
        'Modules\Inventory\Filament\Resources\GoodsReceivedResource',
        'Modules\Inventory\Filament\Resources\ProcurementRequestResource',
        'Modules\Inventory\Filament\Resources\AssetMaintenanceResource',
        'Modules\Library\Filament\Resources\LibraryBookResource',
        'Modules\Library\Filament\Resources\LibraryIssueResource',
    ];

    /**
     * Pages that exist in the panel but are reached from inside another page
     * rather than from the module navigation, so they are declared here
     * instead of in {@see ModuleNavigation}.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function additionalPages(): array
    {
        return [
            'academics' => [
                ['key' => 'time_slots', 'label' => __('Time Slots'), 'group' => __('Setup & Structure'), 'class' => TimeSlotResource::class],
                ['key' => 'timetable_lessons', 'label' => __('Timetable Entries'), 'group' => __('Timetables & Teaching'), 'class' => TimetableLessonResource::class],
                ['key' => 'promote_student', 'label' => __('Promote a Student'), 'group' => __('Progression'), 'class' => PromoteStudents::class],
                ['key' => 'promotion_workflow', 'label' => __('Promotion Workflow'), 'group' => __('Progression'), 'class' => PromotionWorkflowResource::class],
            ],
            'exams' => [
                ['key' => 'manual_marking', 'label' => __('Manual Marking'), 'group' => __('Assessment Center'), 'class' => ManualMarkingPage::class],
                ['key' => 'assessment_analytics', 'label' => __('Assessment Analytics'), 'group' => __('Assessment Center'), 'class' => AssessmentAnalyticsPage::class],
                ['key' => 'gamification_settings', 'label' => __('Gamification Settings'), 'group' => __('Assessment Center'), 'class' => GamificationSettingsPage::class],
            ],
            'finance' => [
                ['key' => 'chart_of_accounts', 'label' => __('Chart of Accounts'), 'group' => __('Core Accounting & Setup'), 'class' => AccountResource::class],
                ['key' => 'journal_entries', 'label' => __('Journal Entries'), 'group' => __('Core Accounting & Setup'), 'class' => JournalEntryResource::class],
                ['key' => 'revenue_categories', 'label' => __('Revenue Categories'), 'group' => __('Core Accounting & Setup'), 'class' => RevenueCategoryResource::class],
                ['key' => 'expense_categories', 'label' => __('Expense Categories'), 'group' => __('Expenses & Purchasing'), 'class' => ExpenseCategoryResource::class],
                ['key' => 'expense_types', 'label' => __('Expense Types'), 'group' => __('Expenses & Purchasing'), 'class' => ExpenseTypeResource::class],
                ['key' => 'billing_document_settings', 'label' => __('Billing Documents'), 'group' => __('Core Accounting & Setup'), 'class' => BillingDocumentSettingsPage::class],
                ['key' => 'suppliers', 'label' => __('Suppliers'), 'group' => __('Core Accounting & Setup'), 'class' => SupplierResource::class],
            ],
            'inventory' => [
                ['key' => 'inventory_items', 'label' => __('Inventory Items'), 'group' => __('Stock & Inventory'), 'class' => InventoryItemResource::class],
            ],
            'reports' => [
                ['key' => 'report_templates', 'label' => __('Report Templates'), 'group' => __('Reports'), 'class' => EnterpriseReportTemplateResource::class],
                ['key' => 'reporting_workflow', 'label' => __('Reporting Workflow'), 'group' => __('Reports'), 'class' => ReportingWorkflowResource::class],
            ],
            'website' => [
                // A second, older hub class that the content manager still
                // links to. Mapped to the same permissions as its replacement
                // so the two can never drift apart.
                ['key' => 'website_templates_legacy', 'label' => __('Website Templates'), 'group' => __('Templates & Design'), 'class' => WebsiteTemplatesHub::class],
                ['key' => 'visual_builder', 'label' => __('Visual Builder'), 'group' => __('Templates & Design'), 'class' => VisualCmsBuilder::class],
            ],
        ];
    }

    /**
     * Panels the whole system shares, kept outside the module tree because they
     * are not part of any module and are granted to every signed-in account.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function universalPages(): array
    {
        return [
            [
                'key' => 'dashboard',
                'label' => __('My Workspace'),
                'group' => __('Everyday'),
                'class' => Dashboard::class,
                'universal' => true,
            ],
            [
                'key' => 'my_day',
                'label' => __('My Day'),
                'group' => __('Everyday'),
                'class' => MyDay::class,
                'universal' => true,
            ],
            [
                'key' => 'schedule',
                'label' => __('Schedule'),
                'group' => __('Everyday'),
                'class' => Schedule::class,
                'universal' => true,
            ],
        ];
    }

    /**
     * Pages that are reachable but never listed in the permission pickers
     * because they are consequences of another page (confirmation screens,
     * child detail views). They are always open to any signed-in account.
     *
     * @return array<int, string>
     */
    public static function incidentalPages(): array
    {
        return [
            ApplicationSuccess::class,
        ];
    }

    // ---------------------------------------------------------------------
    // Tree construction
    // ---------------------------------------------------------------------

    /**
     * The full capability tree.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function modules(): array
    {
        if (self::$tree !== null) {
            return self::$tree;
        }

        $purposes = self::pagePurpose();
        $overrides = self::pageActions();

        $tree = [];

        foreach (ModuleNavigation::modules() as $module) {
            $slug = $module['slug'];

            $pages = [];

            foreach (array_merge($module['tabs'] ?? [], $module['more'] ?? []) as $tab) {
                $class = $tab['resource'] ?? $tab['page'] ?? null;
                if ($class === null) {
                    continue;
                }

                $key = $tab['key'] ?? self::uniqueKey($pages, (string) $tab['label']);

                $pages[$key] = self::makePage($slug, $key, $tab, $class, $purposes, $overrides);
            }

            foreach (self::additionalPages()[$slug] ?? [] as $page) {
                $key = $page['key'];

                $pages[$key] = self::makePage($slug, $key, $page, $page['class'], $purposes, $overrides);
            }

            $tree[$slug] = [
                'key' => $slug,
                'label' => (string) $module['label'],
                'description' => (string) ($module['description'] ?? ''),
                'actions' => self::DEFAULT_MODULE_ACTIONS,
                'pages' => $pages,
            ];
        }

        // Universal pages live in a synthetic module so they can be resolved by
        // the same class -> permission lookup without appearing in a module.
        $universal = [];
        foreach (self::universalPages() as $page) {
            $universal[$page['key']] = self::makePage(
                'universal',
                $page['key'],
                $page,
                $page['class'],
                $purposes,
                $overrides,
            );
        }
        $tree['universal'] = [
            'key' => 'universal',
            'label' => __('Everyday'),
            'description' => __('Pages every member of staff can reach regardless of role.'),
            'actions' => [],
            'pages' => $universal,
            'universal' => true,
        ];

        return self::$tree = $tree;
    }

    /**
     * @param  array<string, mixed>  $tab
     * @param  array<string, string>  $purposes
     * @param  array<string, array<string, mixed>>  $overrides
     * @return array<string, mixed>
     */
    private static function makePage(string $moduleSlug, string $key, array $tab, string $class, array $purposes, array $overrides): array
    {
        $class = ltrim($class, '\\');

        $override = $overrides[$class] ?? [];
        $extras = $override['add'] ?? [];

        if (in_array($class, self::IMPORT_CAPABLE_PAGES, true)) {
            $extras[] = 'import';
        }

        $actions = $override['actions'] ?? array_values(array_unique(
            array_merge(self::DEFAULT_PAGE_ACTIONS, $extras)
        ));

        $label = (string) $tab['label'];
        $group = (string) ($tab['group'] ?? $label);

        return [
            'key' => $key,
            'label' => $label,
            'group' => $group,
            'class' => $class,
            'hub' => (bool) ($tab['hub'] ?? false),
            'universal' => (bool) ($tab['universal'] ?? false),
            'actions' => $actions,
            'purpose' => $purposes[$class] ?? self::generatedPurpose($label, $group, $class),
            'permissions' => array_map(
                fn (string $action): string => self::pagePermissionKey($moduleSlug, $key, $action),
                $actions,
            ),
        ];
    }

    private static function generatedPurpose(string $label, string $group, string $class): string
    {
        $short = class_basename($class);

        return __(':label belongs to :group. It is a working page in the :short screen, so permissions here control who may use it.', [
            'label' => $label,
            'group' => $group,
            'short' => $short,
        ]);
    }

    private static function uniqueKey(array $existing, string $label): string
    {
        $base = str_replace('-', '_', Str::slug($label)) ?: 'page';

        if (! array_key_exists($base, $existing)) {
            return $base;
        }

        $n = 2;
        while (array_key_exists($base.'_'.$n, $existing)) {
            $n++;
        }

        return $base.'_'.$n;
    }

    // ---------------------------------------------------------------------
    // Permission keys
    // ---------------------------------------------------------------------

    public static function modulePermissionKey(string $module, string $action): string
    {
        return $module.'.'.$action;
    }

    public static function pagePermissionKey(string $module, string $page, string $action): string
    {
        return $module.'.'.$page.'.'.$action;
    }

    // ---------------------------------------------------------------------
    // Lookups
    // ---------------------------------------------------------------------

    public static function module(string $slug): ?array
    {
        return self::modules()[$slug] ?? null;
    }

    public static function moduleForClass(string $class): ?string
    {
        foreach (self::modules() as $slug => $module) {
            foreach ($module['pages'] as $page) {
                if (strcasecmp($page['class'], ltrim($class, '\\')) === 0) {
                    return $slug;
                }
            }
        }

        return null;
    }

    public static function pageForClass(string $class): ?array
    {
        $class = ltrim($class, '\\');

        foreach (self::modules() as $slug => $module) {
            foreach ($module['pages'] as $page) {
                if (strcasecmp($page['class'], $class) === 0) {
                    return $page + ['module' => $slug, 'module_label' => $module['label']];
                }
            }
        }

        return null;
    }

    /**
     * class => [module slug, page key] for every page in the tree.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function classMap(): array
    {
        if (self::$classMap !== null) {
            return self::$classMap;
        }

        $map = [];

        foreach (self::modules() as $slug => $module) {
            foreach ($module['pages'] as $key => $page) {
                $map[$page['class']] = [$slug, $key];
            }
        }

        return self::$classMap = $map;
    }

    /**
     * Every permission key in the catalogue.
     *
     * @return array<int, string>
     */
    public static function allPermissionKeys(): array
    {
        $keys = [];

        foreach (self::modules() as $module) {
            foreach ($module['actions'] as $action) {
                $keys[] = self::modulePermissionKey($module['key'], $action);
            }

            foreach ($module['pages'] as $page) {
                foreach ($page['permissions'] as $key) {
                    $keys[] = $key;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Every permission key belonging to one module, page-scoped keys included.
     *
     * @return array<int, string>
     */
    public static function modulePermissionKeys(string $moduleSlug): array
    {
        $module = self::module($moduleSlug);

        if (! $module) {
            return [];
        }

        $keys = [];

        foreach ($module['actions'] as $action) {
            $keys[] = self::modulePermissionKey($module['key'], $action);
        }

        foreach ($module['pages'] as $page) {
            foreach ($page['permissions'] as $key) {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Full access to one module.
     *
     * Returns the module's own action keys plus, for each page, only the
     * operations a module-wide key cannot already express (`approve`,
     * `publish`, `mark`, `issue`, …). Keeping the list short matters: a
     * module-wide `edit` automatically covers a page that is added to the
     * module next year, so a role granted "Academics — everything" keeps
     * working without any migration.
     *
     * @return array<int, string>
     */
    public static function fullAccessTo(string ...$moduleSlugs): array
    {
        $keys = [];

        foreach ($moduleSlugs as $slug) {
            $module = self::module($slug);

            if (! $module) {
                continue;
            }

            foreach ($module['actions'] as $action) {
                $keys[] = self::modulePermissionKey($slug, $action);
            }

            foreach ($module['pages'] as $page) {
                foreach ($page['actions'] as $action) {
                    if (in_array($action, self::DEFAULT_MODULE_ACTIONS, true)) {
                        // Already covered by the module-wide key of the same
                        // name (view is covered by view_module).
                        continue;
                    }

                    $keys[] = self::pagePermissionKey($slug, $page['key'], $action);
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Every explicit permission key for one module, module-wide and
     * page-scoped alike. Used by the permission editor's "select all" so the
     * saved list and the displayed list never disagree.
     *
     * @return array<int, string>
     */
    public static function everyPermissionFor(string $moduleSlug): array
    {
        return self::modulePermissionKeys($moduleSlug);
    }

    /**
     * Everything a member of staff may do for themselves, regardless of role:
     * their own profile, their own leave, their own tasks, their own day.
     *
     * @return array<int, string>
     */
    public static function selfServicePermissionKeys(): array
    {
        $keys = [];

        foreach (self::modules() as $module) {
            if ($module['key'] === 'universal') {
                continue;
            }

            foreach ($module['pages'] as $page) {
                $prefix = $page['permissions'][0] ?? '';
                if ($prefix === '') {
                    continue;
                }

                $base = substr($prefix, 0, strrpos($prefix, '.'));

                foreach (['view', 'create', 'edit', 'delete'] as $action) {
                    $keys[] = $base.'.'.$action;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    // ---------------------------------------------------------------------
    // Documentation helpers
    // ---------------------------------------------------------------------

    /**
     * One paragraph explaining a single permission key, used for the
     * question-mark helper beside every checkbox.
     */
    public static function describe(string $permission): string
    {
        $parts = explode('.', $permission);

        if (count($parts) === 3) {
            [$moduleSlug, $pageKey, $action] = $parts;

            $page = self::page($moduleSlug, $pageKey);

            if ($page) {
                return __(':purpose — Grants: :action.', [
                    'purpose' => $page['purpose'],
                    'action' => self::actionHelp()[$action] ?? $action,
                ]);
            }
        }

        if (count($parts) === 2) {
            [$moduleSlug, $action] = $parts;

            $module = self::module($moduleSlug);

            if ($module) {
                if ($action === 'view_module') {
                    return __('Shows :module in your sidebar and unlocks every page inside it. Untick to remove the whole module from this person.', [
                        'module' => $module['label'],
                    ]);
                }

                return __('Module-wide: applies this operation to every page in :module. :action', [
                    'module' => $module['label'],
                    'action' => self::actionHelp()[$action] ?? $action,
                ]);
            }
        }

        return (string) $permission;
    }

    /**
     * The permission keys a page needs, expressed as "any of these will do".
     *
     * For a category hub that means the hub is open to anybody who may open at
     * least one page inside it. A hub is a doorway, not a destination: somebody
     * permitted to reach Student Billing must be able to land on that hub and be
     * shown the three things they can actually do, rather than being refused at
     * the door and left with no way in.
     *
     * @return array<int, string>
     */
    public static function accessKeysFor(string $moduleSlug, string $pageKey): array
    {
        $page = self::page($moduleSlug, $pageKey);

        if ($page === null) {
            return [];
        }

        if (! $page['hub']) {
            return [self::pagePermissionKey($moduleSlug, $pageKey, 'view')];
        }

        $keys = [self::pagePermissionKey($moduleSlug, $pageKey, 'view')];

        foreach (self::childPages($moduleSlug, $pageKey) as $child) {
            $keys[] = self::pagePermissionKey($moduleSlug, $child['key'], 'view');
        }

        return array_values(array_unique($keys));
    }

    /**
     * Has the school left this part of the module switched on?
     *
     * Most pages are governed by their module's master toggle alone and are
     * always on. The handful listed in {@see PAGE_TENANT_TOGGLE} have their own
     * switch in the module settings screen, because a school may run its
     * library without a visual CMS, or its timetable without promotion runs.
     */
    public static function isPageTenantEnabled(string $moduleSlug, string $pageKey): bool
    {
        $toggle = self::PAGE_TENANT_TOGGLE["{$moduleSlug}.{$pageKey}"] ?? null;

        return $toggle === null || ModuleVisibilityManager::isPageVisible($toggle[0], $toggle[1]);
    }

    /**
     * The pages a category hub stands for: every other page in the same
     * category of the same module.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function childPages(string $moduleSlug, string $pageKey): array
    {
        $page = self::page($moduleSlug, $pageKey);
        $module = self::module($moduleSlug);

        if ($page === null || $module === null) {
            return [];
        }

        $children = [];

        foreach ($module['pages'] as $key => $candidate) {
            if ($key !== $pageKey && $candidate['group'] === $page['group']) {
                $children[$key] = $candidate;
            }
        }

        return $children;
    }

    /**
     * A hub is worth showing when the whole category is hidden from this person.
     */
    public static function hubHasVisibleChildren(string $moduleSlug, string $pageKey, array $permissions): bool
    {
        foreach (self::childPages($moduleSlug, $pageKey) as $child) {
            if (PermissionRegistry::isGrantedAny(
                $permissions,
                [self::pagePermissionKey($moduleSlug, $child['key'], 'view')]
            )) {
                return true;
            }
        }

        return false;
    }

    public static function page(string $moduleSlug, string $pageKey): ?array
    {
        $page = self::module($moduleSlug)['pages'][$pageKey] ?? null;

        if (! $page) {
            return null;
        }

        return $page + ['module' => $moduleSlug];
    }

    /**
     * The catalogue's name for a screen, looked up by label.
     *
     * Used where code refers to a page the way a user would — a help message, a
     * test asserting on what someone can reach — so that renaming a page does
     * not leave a stale name behind in a string nobody compiles against.
     */
    public static function pageLabel(string $moduleSlug, string $label): ?string
    {
        foreach (self::module($moduleSlug)['pages'] ?? [] as $page) {
            if ($page['label'] === $label) {
                return $page['label'];
            }
        }

        return null;
    }

    /**
     * The tree shaped for the permission editor: modules, each with its
     * categories, each with its pages and their actions, every action carrying
     * its own help text.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forEditor(): array
    {
        $modules = [];
        $help = self::actionHelp();

        foreach (self::modules() as $module) {
            if ($module['key'] === 'universal') {
                continue;
            }

            $categories = [];

            foreach ($module['pages'] as $key => $page) {
                $category = $page['group'];

                $categories[$category] ??= [
                    'key' => str_replace('-', '_', Str::slug($category)) ?: 'general',
                    'label' => $category,
                    'pages' => [],
                ];

                $actions = [];
                foreach ($page['actions'] as $action) {
                    $actions[] = [
                        'key' => $page['permissions'][array_search($action, $page['actions'], true)],
                        'action' => $action,
                        'label' => self::actionLabel($action),
                        'help' => $help[$action] ?? $action,
                    ];
                }

                $categories[$category]['pages'][] = [
                    'key' => $key,
                    'label' => $page['label'],
                    'class' => $page['class'],
                    'hub' => $page['hub'],
                    'purpose' => $page['purpose'],
                    'actions' => $actions,
                ];
            }

            if ($categories === []) {
                continue;
            }

            $moduleActions = [];
            foreach ($module['actions'] as $action) {
                $moduleActions[] = [
                    'key' => self::modulePermissionKey($module['key'], $action),
                    'action' => $action,
                    'label' => self::actionLabel($action),
                    'help' => $help[$action] ?? $action,
                ];
            }

            $modules[] = [
                'key' => $module['key'],
                'label' => $module['label'],
                'description' => $module['description'],
                'actions' => $moduleActions,
                'categories' => array_values($categories),
            ];
        }

        return $modules;
    }

    public static function actionLabel(string $action): string
    {
        return match ($action) {
            'view_module' => __('Show this module'),
            'view' => __('View'),
            'create' => __('Create'),
            'edit' => __('Edit'),
            'delete' => __('Delete'),
            'export' => __('Export'),
            'import' => __('Import'),
            'publish' => __('Publish'),
            'approve' => __('Approve'),
            'configure' => __('Configure'),
            'run' => __('Run'),
            'mark' => __('Mark'),
            'issue' => __('Issue'),
            'return' => __('Return'),
            'waive' => __('Waive'),
            'override' => __('Override'),
            'reset' => __('Reset'),
            'assign' => __('Assign'),
            'lock' => __('Lock'),
            default => ucfirst($action),
        };
    }

    /**
     * Forget memoised state. Used by tests.
     */
    public static function flush(): void
    {
        self::$tree = null;
        self::$classMap = null;
    }
}
