<?php

namespace App\Support;

use App\Filament\App\Resources\AcademicReportResource;
use App\Filament\App\Resources\AcademicYearResource;
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
use App\Filament\App\Resources\DigitalAssessmentResource;
use App\Filament\App\Resources\DisciplinaryCaseResource;
use App\Filament\App\Resources\EmployeeAssetResource;
use App\Filament\App\Resources\EmployeeResource;
use App\Filament\App\Resources\EventCalendarResource;
use App\Filament\App\Resources\ExpenseResource;
use App\Filament\App\Resources\FeeCategoryResource;
use App\Filament\App\Resources\FeePaymentSubmissionResource;
use App\Filament\App\Resources\FeeStructureResource;
use App\Filament\App\Resources\FeeWaiverResource;
use App\Filament\App\Resources\FinanceDocumentTemplateResource;
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
use App\Filament\App\Resources\LeaveRequestResource;
use App\Filament\App\Resources\PayrollPeriodResource;
use App\Filament\App\Resources\PlatformInboxResource;
use App\Filament\App\Resources\PollResource;
use App\Filament\App\Resources\PromotionRunResource;
use App\Filament\App\Resources\QuestionBankResource;
use App\Filament\App\Resources\ReportTemplateResource;
use App\Filament\App\Resources\RevenueStreamResource;
use App\Filament\App\Resources\SaaSMySubscriptionResource;
use App\Filament\App\Resources\SalaryGradeResource;
use App\Filament\App\Resources\SchoolBankAccountResource;
use App\Filament\App\Resources\ScreeningRunResource;
use App\Filament\App\Resources\StaffAttendanceResource;
use App\Filament\App\Resources\StaffLoanResource;
use App\Filament\App\Resources\StockAdjustmentResource;
use App\Filament\App\Resources\StudentMedicalRecordResource;
use App\Filament\App\Resources\StudentResource;
use App\Filament\App\Resources\SubjectResource;
use App\Filament\App\Resources\SystemAuditLogResource;
use App\Filament\App\Resources\TeacherAssignmentResource;
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

class HelpContent
{
    public static function for(string $pageOrResourceClass): array
    {
        $class = ltrim($pageOrResourceClass, '\\');

        return match ($class) {
            // =========================================================
            // ACADEMICS MODULE
            // =========================================================
            'App\Filament\App\Pages\Academic\AcademicOperationsCenter' => [
                'title' => 'Academic Operations Center',
                'summary' => 'Central control dashboard for managing the school academic rhythm, term transitions, and operational readiness.',
                'workflow' => [
                    '1. Review Active Term Status: Check current enrollment counts, active academic year, and term dates at a glance.',
                    '2. Monitor Teaching Progress: Track timetable completion rates and staff assignment coverage across levels.',
                    '3. Operational Actions: Quick-launch into Setup and Structure, Timetables, or Progression runs directly from summary cards.',
                ],
                'details' => [
                    'Relationship' => 'Serves as the high-level home page for the Academics module, linking directly to Setup and Structure, Timetables, and Student Progression.',
                    'Best Practice' => 'Check this dashboard at the start of every term to ensure all foundational setup steps are complete before students attend classes.',
                ],
            ],
            AcademicYearResource::class => [
                'title' => 'Academic Years & Terms (Setup & Structure)',
                'summary' => 'Establish the temporal foundation of the school calendar. You can register multiple historical or future years, but exactly one year must be marked as active.',
                'workflow' => [
                    '1. Step 1 - Register Academic Year: Click New Academic Year, enter the year title (e.g., 2027), and set start and end dates.',
                    '2. Step 2 - Configure Terms: Add operating terms (Term 1, Term 2, Term 3) with precise grading periods.',
                    '3. Step 3 - Set Active Year: Mark the current operating year as active. Crucial: All student enrollments, attendance records, fee billing, timetables, and exam grading attach automatically to this active year.',
                ],
                'details' => [
                    'Prerequisite Relationship' => 'This is the very first page you must configure in Setup and Structure. Without an active academic year, classrooms, subjects, and timetables cannot be linked.',
                    'Cross-Module Impact' => 'When you change the active academic year, the Finance module links fee structures, and the Student module anchors enrollments to that year.',
                ],
            ],
            ClassroomResource::class => [
                'title' => 'Classrooms & Facilities (Setup & Structure)',
                'summary' => 'Register physical rooms, lecture halls, laboratories, and outdoor spaces with strict capacity limits to prevent overcrowding.',
                'workflow' => [
                    '1. Step 1 - Add Classroom: Click New Classroom and specify the room name or number (e.g., Lab-01 or Room 12), building block, and maximum student seating capacity.',
                    '2. Step 2 - Timetable Assignment: Classrooms created here become available in the Timetable module for lesson scheduling.',
                    '3. Step 3 - Boarding Integration: Rooms can also be cross-referenced when allocating hostel accommodation.',
                ],
                'details' => [
                    'Relationship' => 'Classrooms relate directly to Timetables and Teaching, ensuring no two classes are assigned to the same physical room at the same time.',
                    'Best Practice' => 'Keep capacity numbers accurate; the system uses them during exam seating arrangements and hostel room allocation.',
                ],
            ],
            SubjectResource::class => [
                'title' => 'Subjects Curriculum (Setup & Structure)',
                'summary' => 'Define the institutional curriculum of subjects offered across the school (e.g., Mathematics, English, Physics, Shona).',
                'workflow' => [
                    '1. Step 1 - Create Subject: Enter the subject full name and unique subject code (e.g., MATH3, ENG-SUP).',
                    '2. Step 2 - Departmental Grouping: Associate subjects with appropriate academic departments (e.g., Sciences, Humanities, Languages).',
                    '3. Step 3 - Grading Scales: Link subjects to appropriate grading scales so assessment marks compute correctly in the Exams module.',
                ],
                'details' => [
                    'Relationship' => 'Subjects must exist before you can assign teachers in Teacher Assignments or schedule lessons in Timetable Lessons.',
                    'Workflow Flow' => 'Academic Years -> Classrooms -> Subjects -> Levels -> Teacher Assignments.',
                ],
            ],
            CourseResource::class => [
                'title' => 'Grade Levels (Setup & Structure)',
                'summary' => 'Define the grade levels or forms offered by your school (e.g., Grade 1, Grade 4, Form 1, Form 4, Lower Sixth).',
                'workflow' => [
                    '1. Step 1 - Create Level: Click New Level, enter level name, level ranking order, and associate tuition fees if applicable.',
                    '2. Step 2 - Stream Setup: Group students into class streams under each level.',
                    '3. Step 3 - Progression Linking: Levels determine how students promote from year to year in the Progression module.',
                ],
                'details' => [
                    'Relationship' => 'Levels tie Student enrollments, Fee Structures, and Timetable class streams together into coherent educational cohorts.',
                ],
            ],
            'App\Filament\App\Pages\Academic\TimetablesTeachingHub' => [
                'title' => 'Timetables & Teaching Hub',
                'summary' => 'Central hub for building templates, managing time slots, scheduling lessons, and viewing teaching timetables.',
                'workflow' => [
                    '1. Configure Time Slots: Set up daily operating periods (e.g., Period 1, Break, Period 2).',
                    '2. Teacher Assignments: Allocate teachers to subjects and class streams.',
                    '3. Lesson Scheduling: Launch the visual builder or timetable list to arrange weekly lessons.',
                ],
                'details' => [
                    'Relationship' => 'Connects Setup and Structure (Classrooms, Subjects, Academic Years) with daily classroom instruction.',
                ],
            ],
            'App\Filament\App\Pages\VisualTimetableBuilder' => [
                'title' => 'Visual Timetable Builder',
                'summary' => 'Interactive drag-and-drop builder for arranging weekly school timetables with automated conflict checks.',
                'workflow' => [
                    '1. Select Template: Choose the active timetable template for the term.',
                    '2. Drag & Drop: Drag lessons across days and time slots. The system instantly verifies teacher, room, and class availability.',
                    '3. Save & Publish: Commit changes and publish the timetable for teacher and student portal viewing.',
                ],
                'details' => [
                    'Relationship' => 'Prevents teacher, classroom, and student-class double bookings automatically.',
                ],
            ],
            'App\Filament\App\Pages\TimetableViewerPage' => [
                'title' => 'View Timetable',
                'summary' => 'Interactive grid viewer for school, stream, and class timetables with printing capabilities.',
                'workflow' => [
                    '1. Select Scope: Choose between School-wide, Stream, or specific Class views.',
                    '2. Inspect Matrix: Review weekly lesson distribution across time slots.',
                    '3. Print & Export: Print clean timetable schedules for staff noticeboards or student distribution.',
                ],
                'details' => [
                    'Relationship' => 'Provides read-only access for staff and students based on their active timetable generation.',
                ],
            ],
            TeacherAssignmentResource::class => [
                'title' => 'Teacher Assignments (Timetables & Teaching)',
                'summary' => 'Allocate teaching staff to specific subjects, classes, and designate Form Teachers for pastoral care and attendance.',
                'workflow' => [
                    '1. Select Employee: Choose a registered staff member from the HR directory.',
                    '2. Assign Role: Link the teacher to a specific Subject and Class/Stream.',
                    '3. Form Teacher Designation: Check the box if this teacher is the primary Form Teacher for pastoral care and daily attendance tracking.',
                ],
                'details' => [
                    'Relationship' => 'Connects HR and Payroll staff records with Academics and grants teachers grading permissions for their assigned student rosters.',
                ],
            ],
            TimetableLessonResource::class => [
                'title' => 'Timetable Lessons (Timetables & Teaching)',
                'summary' => 'Schedule and manage weekly teaching timetables with automated conflict detection preventing teacher and room double-booking.',
                'workflow' => [
                    '1. Configure Time Slots: Ensure operating time slots and templates are configured.',
                    '2. Lesson Scheduling: Place lessons into the weekly matrix specifying day, time slot, room, teacher, and subject.',
                    '3. Drag & Drop / Swapping: Use the visual timetable grid to drag or swap lessons between slots instantly.',
                ],
                'details' => [
                    'Relationship' => 'Relies entirely on Classrooms, Subjects, and Teacher Assignments being set up first.',
                ],
            ],
            PromotionRunResource::class => [
                'title' => 'Student Promotion Runs (Progression)',
                'summary' => 'Execute end-of-year batch student promotions, repeating, or graduations based on final exam performance.',
                'workflow' => [
                    '1. Select Source Year: Choose the concluding academic year.',
                    '2. Set Criteria: Review student pass/fail thresholds and terminal level graduations.',
                    '3. Execute Run: Process batch promotions; promoted students automatically transition to the next grade level for the new academic year.',
                ],
                'details' => [
                    'Relationship' => 'Ties Exams and Grading performance analytics directly to Students enrollment updates for the upcoming academic year.',
                ],
            ],
            ScreeningRunResource::class => [
                'title' => 'Student Screening Runs (Progression)',
                'summary' => 'Evaluate student academic eligibility and prerequisites before progression or subject streaming.',
                'workflow' => [
                    '1. Define Rules: Set academic prerequisites or grade cutoffs.',
                    '2. Select Cohort: Choose target student groups for screening.',
                    '3. Run Evaluation: System automatically filters and flags qualified learners.',
                ],
                'details' => [
                    'Relationship' => 'Works in tandem with Promotion Runs to ensure academic standards are maintained.',
                ],
            ],

            // =========================================================
            // STUDENTS MODULE
            // =========================================================
            StudentResource::class => [
                'title' => 'Student Directory & Profiles',
                'summary' => 'Complete lifecycle manager for all enrolled learners, covering personal details, enrollment streams, guardians, and bulk tools.',
                'workflow' => [
                    '1. New Student: Click New Student to manually register an individual learner with personal details, guardian contacts, medical notes, and initial class/stream assignment.',
                    '2. Download Excel Template & Import: Click Download Excel Template, fill the template with student details (bold fields are required, optional fields are regular), and use Import Students from Excel or CSV to batch import learners with automated validation and error logs.',
                    '3. Viewing & Editing: Click View on any student to inspect Student Information, photo, and current enrollment details (Academic Year, Form/Grade Level, and Stream/Class). Click Edit to update records.',
                    '4. Export All: Export the complete student roster as a nicely formatted Excel spreadsheet (Export as Excel) or a fit-to-page PDF report (Export as PDF).',
                    '5. Row Actions: Use table row buttons to Duplicate student records, Edit details, or Delete records securely.',
                ],
                'details' => [
                    'Cross-Module Link' => 'Students registered here are automatically linked to Finance for invoicing, Attendance tracking, and Exam report cards.',
                ],
            ],
            CardTemplateResource::class => [
                'title' => 'ID Card Designer & Templates',
                'summary' => 'Design, layout, preview, and activate official student and staff identification card templates with secure QR verification hashes.',
                'workflow' => [
                    '1. Create Template: Design card dimensions, upload school badges, and position student portraits and details.',
                    '2. Preview: Click Preview to test how the card renders with sample student data before final printing.',
                    '3. Bulk Print ID Cards: Generate high-resolution printable PDF ID cards for all students or filtered cohorts instantly.',
                    '4. Activation & Row Actions: Mark your preferred template active. Use table row actions to Duplicate, Edit, or Delete card templates.',
                ],
                'details' => [
                    'Relationship' => 'Pulls photo and identity data directly from active Student and Employee records.',
                ],
            ],

            // =========================================================
            // ADMISSIONS MODULE
            // =========================================================
            ApplicationResource::class => [
                'title' => 'Online Admissions Applications',
                'summary' => 'Manage incoming student applications submitted through the public admissions portal.',
                'workflow' => [
                    '1. Review Submissions: Inspect applicant details, previous school records, and uploaded documents.',
                    '2. Screening & Evaluation: Run entrance screening criteria or interview evaluations.',
                    '3. Approval & Conversion: Approve qualified applicants. Once approved, click convert to instantly generate an official Student Profile in the Student Directory.',
                ],
                'details' => [
                    'Relationship' => 'Acts as the bridge between public website inquiries and internal Student enrollment records.',
                ],
            ],
            'App\Filament\App\Pages\AdmissionSettingsPage' => [
                'title' => 'Admission Settings & Portal Configuration',
                'summary' => 'Configure public application forms, opening/closing dates, and admission screening rules.',
                'workflow' => [
                    '1. Portal Status: Open or close the public admissions portal for the current academic year.',
                    '2. Form Customization: Set required fields and upload document checklists for prospective parents.',
                    '3. Screening Rules: Define automated acceptance or review thresholds.',
                ],
                'details' => [
                    'Relationship' => 'Controls how prospective parents interact with the online application forms on the public school website.',
                ],
            ],

            // =========================================================
            // EXAMS & GRADING MODULE
            // =========================================================
            'App\Filament\App\Pages\Exams\AssessmentCenterHub' => [
                'title' => 'Assessment Center',
                'summary' => 'Landing page for building assessments. Create paper-based tests, author reusable digital assessments, and maintain the central question bank that both draw from.',
                'workflow' => [
                    '1. Build your question bank: store every question once, tagged by subject, topic, difficulty and curriculum reference, so assessments can be assembled by reuse rather than retyping.',
                    '2. Define assessment types: register each test or exam with its maximum mark and its weighting towards the final term grade.',
                    '3. Create assessments: either record a paper test for teacher-entered marks, or build a digital assessment that students sit online under timed, auto-marked conditions.',
                    '4. Review coverage: check question usage counts to spot under-used topics and over-repeated questions.',
                ],
                'details' => [
                    'Relationship' => 'Digital Assessments and Question Bank are the two working pages behind this hub. Marks captured here flow into Marks Entry, then Performance Analytics and Report Cards.',
                    'Best Practice' => 'Author and tag questions centrally before building assessments. Reuse keeps question quality consistent across terms and makes revision of a weak topic far easier.',
                ],
            ],
            DigitalAssessmentResource::class => [
                'title' => 'Digital Assessments',
                'summary' => 'Build and deliver online assessments that students sit directly in the student portal, with timed sessions, automatic multiple-choice marking and configurable attempt limits.',
                'workflow' => [
                    '1. Create the assessment: give it a title, subject, class stream, academic term and assessment type, then set duration in minutes, total marks, pass mark and allowed attempts.',
                    '2. Choose the mode: a timed online test auto-marks objective questions, while a non-timed assessment can be used for practice or revision.',
                    '3. Set the rules: optionally randomize questions and options, shuffle the question pool, allow backward navigation or question skipping, and decide whether feedback is shown on submission.',
                    '4. Add questions: pull items from the Question Bank filtered by subject and topic so each sitting gets a fresh but syllabus-aligned paper.',
                    '5. Publish: move the assessment from draft to published, then use analytics to review attempts and scores.',
                ],
                'details' => [
                    'Grading Impact' => 'Mark "Contributes to Grade" to have scores counted towards the final term grade. Leave it off for practice papers so they never affect transcripts.',
                    'Availability Window' => 'Set availability start and end dates to open the assessment to students for a specific window rather than indefinitely.',
                ],
            ],
            QuestionBankResource::class => [
                'title' => 'Question Bank',
                'summary' => 'Central repository of reusable exam and assignment questions, categorized by subject, type, difficulty, topic and curriculum reference.',
                'workflow' => [
                    '1. Add a question: enter the question text, select its type (multiple choice, multiple select, true/false, short answer, numeric or matching), and set its marks.',
                    '2. Define the answer: add options and mark the correct one for objective types, or supply an accepted answer, matching pairs or rubric notes for types marked for manual marking.',
                    '3. Categorize it: attach the subject, topic, subtopic, grade level, learning objective, competency, curriculum reference and tags so the item can be found and filtered later.',
                    '4. Enrich it: add images and a written explanation that students can see as feedback after a digital sitting.',
                    '5. Manage the pool: duplicate an item to vary it, publish to make it available to assessments, archive to retire it, and use bulk actions for large sets.',
                ],
                'details' => [
                    'Usage Tracking' => 'Every question records how many times it has been used, which reveals which topics are over-tested and which are being neglected.',
                    'Best Practice' => 'Tag consistently. Filtering and random selection both depend on accurate subject and topic tagging, and reporting suffers when tags are missing.',
                ],
            ],
            'App\Filament\App\Pages\Exams\GradingMarksHub' => [
                'title' => 'Grading & Marks Management',
                'summary' => 'Control how assessment results are captured, weighted and interpreted: define grading scales and assessment types, enter marks, and analyse performance.',
                'workflow' => [
                    '1. Define grading scales: set letter grades with minimum and maximum score bands and descriptors, so raw marks convert into meaningful grades automatically.',
                    '2. Define assessment types: register each test or exam with its maximum mark and the percentage weight it carries towards the final term grade.',
                    '3. Enter marks: populate a class marks sheet and record results, or import a completed marks sheet from Excel or CSV.',
                    '4. Publish: release marks to the student portal once entry is complete and verified.',
                    '5. Analyse: use Performance Analytics to compare cohorts, subjects and streams before the term closes.',
                ],
                'details' => [
                    'Order Matters' => 'Grading scales and assessment weights must exist before marks are entered, otherwise marks cannot be graded or weighted correctly.',
                    'Relationship' => 'Everything configured here is consumed by Report Cards and by the student portal results view.',
                ],
            ],
            'App\Filament\App\Pages\Exams\PerformanceAnalyticsPage' => [
                'title' => 'Performance Analytics',
                'summary' => 'Analyse student performance across the school, by grade or form level, or for an individual class stream, with subject-level breakdowns of averages, pass rates and grade distributions.',
                'workflow' => [
                    '1. Scope the view: pick the academic term, then the grade or form level, the class stream and the subject you want to examine.',
                    '2. Read the summary: review class average, highest and lowest scores, pass rate against the pass mark, and the spread of letter grades.',
                    '3. Identify outliers: use the ranked student list to spot learners who need intervention and those who are ready for extension work.',
                    '4. Compare streams: switch between class streams to see whether a weak subject is a teaching issue or a cohort issue.',
                ],
                'details' => [
                    'Data Requirement' => 'Analytics only include marks that have been entered and published. Blank dashboards usually mean marking is still in progress rather than a fault.',
                    'Best Practice' => 'Review analytics before publishing report cards. It is the last reliable point to catch an underperforming class while marks can still be moderated.',
                ],
            ],
            AssessmentMarkResource::class => [
                'title' => 'Marks Entry',
                'summary' => 'Record, import and publish student marks for assessments, converting raw scores into grades and terminal results.',
                'workflow' => [
                    '1. Populate the sheet: choose an academic year, term, assessment type, grade or form level and class stream to load the enrolled students, or download the Excel template for offline entry.',
                    '2. Enter marks: type each score and the teacher initials. Marks are graded against the active grading scale automatically.',
                    '3. Import in bulk: use Import Marks from Excel or CSV to upload a completed marks sheet, with validation and an error log for rejected rows.',
                    '4. Verify: confirm the entered count and check for students missing marks before publishing.',
                    '5. Publish to Portal: release the marks so students can see their results in the portal and on their report card.',
                ],
                'details' => [
                    'Weighting' => 'Each assessment contributes to the final grade according to the weighting on its assessment type, so verify weights before publishing.',
                    'Audit Trail' => 'Teacher initials are stored with every mark entry to identify who recorded it if a result is later disputed.',
                ],
            ],
            GradingScaleResource::class => [
                'title' => 'Grading Scales',
                'summary' => 'Define how raw marks convert into grades: letter grades with score bands, grade points and the descriptors that explain each band.',
                'workflow' => [
                    '1. Create a scale: name it, then define each grade level with its symbol or letter grade, grade points, minimum score percentage and maximum score percentage.',
                    '2. Write the descriptor: give each band a comment such as "Distinction" or "Needs Improvement" so report cards explain the grade in words.',
                    '3. Import in bulk: use Import Grading Scales from Excel or CSV, or download the template first to see the expected columns.',
                    '4. Apply it: the active scale is used automatically when marks are graded on the Performance Analytics, Report Cards and portal results pages.',
                ],
                'details' => [
                    'Score Bands' => 'Bands should cover the full range without gaps or overlaps, otherwise a mark can fail to match any grade and remain ungraded.',
                    'Best Practice' => 'Keep scales consistent across terms. Changing a scale mid-year makes earlier and later results hard to compare.',
                ],
            ],
            AssessmentTypeResource::class => [
                'title' => 'Assessment Types',
                'summary' => 'Register each test or exam once, with its maximum attainable mark and the weight it carries towards the final term grade.',
                'workflow' => [
                    '1. Create the assessment type: name it as staff will refer to it, for example Test 1, Exercise 2 or Final Exam.',
                    '2. Set the term and maximum mark: the maximum mark is the ceiling for scores entered against this assessment.',
                    '3. Set the weighting: enter the percentage this assessment contributes to the final term grade. All weights across a subject should total 100.',
                    '4. Restrict scope if needed: limit the type to a specific subject, grade or form, or class stream when a paper only applies to part of a cohort.',
                    '5. Import in bulk: use Import Assessment Types from Excel or CSV, downloading the template first if you need the exact columns.',
                ],
                'details' => [
                    'Weighting Check' => 'Weights are what make the final grade meaningful. If the weights for a subject do not total 100, computed results will be misleading.',
                    'Relationship' => 'Assessment types are referenced by Marks Entry, Digital Assessments and Report Templates.',
                ],
            ],
            'App\Filament\App\Pages\Exams\ReportsPublishingHub' => [
                'title' => 'Reports & Academic Publishing',
                'summary' => 'Turn recorded marks into finished reports and release them to students and parents, from template design through to portal publication.',
                'workflow' => [
                    '1. Design the layout: build a report card template, choose a theme, set fonts and colours, and preview the result before use.',
                    '2. Scope the template: assign it to the whole school, a grade or form level, or a single class stream, and activate the one to use.',
                    '3. Choose the content: select which tests and assessments appear, and whether to show the overall subject mark column.',
                    '4. Generate: produce the report cards once marks have been entered and published.',
                    '5. Publish to the student portal so students and parents can view their results.',
                ],
                'details' => [
                    'Prerequisite' => 'Marks must be entered and published before report cards can be generated; an empty report almost always means marking is incomplete.',
                    'Relationship' => 'Report Templates define the layout, Portal Reports Publisher controls release, and Report Cards holds the generated per-student documents.',
                ],
            ],
            AcademicReportResource::class => [
                'title' => 'Report Cards',
                'summary' => 'Generated per-student academic reports, combining assessment marks, grades and teacher comments into a printable or downloadable document.',
                'workflow' => [
                    '1. Generate reports: create a report for a student once their marks for the term are complete and published.',
                    '2. Review the document: check grades, subject comments and the overall position before release.',
                    '3. Add teacher remarks and conduct notes where the template calls for them.',
                    '4. Share: print for physical distribution or release through the student portal.',
                ],
                'details' => [
                    'Depends On' => 'Report cards are built from published marks using the active Report Template, so changes to marks must be published before reports are regenerated.',
                    'Best Practice' => 'Regenerate rather than editing by hand. A report is a snapshot; reissuing after moderation keeps parents and the portal consistent.',
                ],
            ],
            ReportTemplateResource::class => [
                'title' => 'Report Templates',
                'summary' => 'Design and manage the visual layout of academic report cards, including theme, typography, page setup and which assessments appear.',
                'workflow' => [
                    '1. Create a template: name it and choose a pre-designed theme as a starting point.',
                    '2. Customize typography and colour: set the header title size and colour, body text colour, table header background and font family.',
                    '3. Configure the page: choose portrait or landscape orientation, set margins, page border thickness and colour, row height and cell padding.',
                    '4. Add branding: toggle the school logo, motto, contact phone, email and physical address, and adjust the logo width.',
                    '5. Choose the content: select the specific tests and assessments to include and decide whether to show the overall subject mark column.',
                    '6. Assign and activate: scope the template to a level or class stream and set it as the active layout.',
                ],
                'details' => [
                    'Live Preview' => 'Use the live preview while editing to confirm the layout before activating, since changes affect every student who receives a report card.',
                    'Scope Matters' => 'A template scoped to a single stream is the usual way to keep junior and senior report cards looking different.',
                ],
            ],
            AssessmentWorkflowResource::class => [
                'title' => 'Assessment Workflows',
                'summary' => 'Track each assessment through its lifecycle, from creation and marking through to result publication.',
                'workflow' => [
                    '1. Create the assessment: name it and attach the subject, class stream and term, with its maximum mark and weighting towards the final grade.',
                    '2. Define the scope: restrict the assessment to a specific grade or form level where the paper only applies to part of a cohort.',
                    '3. Open for marking: move the workflow into a marking state so teachers can enter marks against it.',
                    '4. Publish results: once marking is complete and moderated, publish so students can see the outcome.',
                ],
                'details' => [
                    'Bulk Actions' => 'Open or publish several assessments at once with the bulk actions when a whole cohort moves to the same state.',
                    'Relationship' => 'A workflow is the operational counterpart of an Assessment Type, tracking status for one occurrence of it.',
                ],
            ],
            'App\Filament\App\Pages\Exams\PortalReportsPublisher' => [
                'title' => 'Publish to Student Portal',
                'summary' => 'Control exactly which reports and results are released to the student portal, and withdraw them when needed.',
                'workflow' => [
                    '1. Select what to publish: choose the report cards or assessment results to release.',
                    '2. Confirm the audience: publishing makes the results visible to the named students and their parents or guardians.',
                    '3. Publish: release the selected items; students immediately see them in the portal.',
                    '4. Withdraw if necessary: unpublish to remove visibility while keeping the underlying marks intact.',
                ],
                'details' => [
                    'Reconciliation' => 'Publishing is separate from entering marks. Marks can be complete and correct while students still see nothing, which is the usual cause of "my child has no results" reports.',
                    'Best Practice' => 'Moderate marks through Performance Analytics before publishing, because publication is visible to parents immediately.',
                ],
            ],
            // =========================================================
            // FINANCE MODULE
            // =========================================================
            'App\Filament\App\Pages\ExecutiveFinancialDashboard' => [
                'title' => 'Finance Overview',
                'summary' => 'Executive-level view of the school financial position, combining receivables, collections, payables and cash position.',
                'workflow' => [
                    '1. Monitor the position: review billed versus collected totals, outstanding balances and overdue amounts.',
                    '2. Investigate exceptions: drill into high-value or ageing invoices and unapproved payment proofs.',
                    '3. Act: use the quick links into Invoices, Fee Collections and Expenses to resolve the issues surfaced.',
                ],
                'details' => [
                    'Relationship' => 'Every figure here is derived from Invoices, Payment Proofs, Expenses and School Bank Accounts rather than entered separately, so it can never drift from the underlying records.',
                    'Best Practice' => 'Treat the dashboard as a triage screen. Use it to find what needs attention, then work in the detail pages.',
                ],
            ],
            'App\Filament\App\Pages\Finance\FinancialStatementPage' => [
                'title' => 'Financial Statements',
                'summary' => 'Formal financial statements for the school: income and expenditure, cash movement, receivables and payables, and financial position at a point in time.',
                'workflow' => [
                    '1. Choose the period: select the academic year and term to report on, or a custom date range.',
                    '2. Review the statements: examine income by revenue stream, expenditure by category, and the resulting surplus or deficit.',
                    '3. Check the balance sheet position: review cash on hand, receivables outstanding and payables due.',
                    '4. Export or print for board and audit packs.',
                ],
                'details' => [
                    'Prerequisites' => 'Statements are only as accurate as the underlying records. Record expenses and confirm payments before treating a statement as final.',
                    'Best Practice' => 'Close off a term by reconciling these statements against bank statements before issuing any financial report externally.',
                ],
            ],
            'App\Filament\App\Pages\Finance\StudentBillingHub' => [
                'title' => 'Student Billing & Revenue (Receivables)',
                'summary' => 'Landing page for everything owed to the school: fee categories, fee structures, invoices, collections and the waivers that reduce them.',
                'workflow' => [
                    '1. Define what you charge: set up fee categories, then build fee structures scoped by academic year, term, grade or form and class stream.',
                    '2. Bill students: run the auto-billing engine to generate invoices from the active fee structures, or raise invoices individually.',
                    '3. Apply concessions: attach fee waivers and scholarships so eligible students are billed correctly from the outset.',
                    '4. Collect: take payments, verify submitted proof, and monitor outstanding balances.',
                    '5. Review: check Student Financial History for any individual account.',
                ],
                'details' => [
                    'Order Matters' => 'Fee categories and fee structures must exist before billing can run. A missing structure means students are simply not billed.',
                    'Relationship' => 'Waivers are best applied when the invoice is raised rather than after payment, because they change what the student was correctly charged.',
                ],
            ],
            FeeCategoryResource::class => [
                'title' => 'Fee Categories',
                'summary' => 'Define the types of charge the school bills, such as tuition, boarding, meals, transport, uniform or examinations.',
                'workflow' => [
                    '1. Create a category: give it a clear name and a short description explaining what it covers.',
                    '2. Group logically: keep categories broad enough to be reusable across levels, for example "Boarding" rather than "Grade 5 Boarding".',
                    '3. Use them: assign each category to the relevant fee structures, which carry the actual amounts.',
                ],
                'details' => [
                    'Relationship' => 'Categories group amounts; they hold no money themselves. The amount lives on the Fee Structure and the charge on the Invoice.',
                    'Best Practice' => 'Agree the category list before billing the first cohort. Renaming categories later leaves older invoices inconsistent.',
                ],
            ],
            FeeStructureResource::class => [
                'title' => 'Fee Structures',
                'summary' => 'Set the amounts charged, by academic year, term, grade or form level and class stream, using reusable fee categories.',
                'workflow' => [
                    '1. Choose the billing scope: decide whether the structure applies school-wide, to a specific class level, or to a single stream.',
                    '2. Pick the period: assign the academic year and term the charges belong to.',
                    '3. Set the currency and amount: enter the value for the chosen category within this scope.',
                    '4. Repeat for each charge: a level typically needs several rows, one per category.',
                    '5. Verify coverage: confirm every level and stream that must be billed has a matching structure before running billing.',
                ],
                'details' => [
                    'Prerequisite' => 'An active academic year with terms is required. Without terms there is nothing for the structure to attach to.',
                    'Common Failure' => 'A missing structure does not error. It silently leaves students unbilled, which is why coverage checks matter.',
                ],
            ],
            InvoiceResource::class => [
                'title' => 'Invoices',
                'summary' => 'Raise and manage student invoices, record payments against them, and print or export the billing documents parents receive.',
                'workflow' => [
                    '1. Generate invoices: use the auto-billing engine to bill a whole level, stream, year or term at once, based on the active fee structures.',
                    '2. Raise an invoice manually: select the student, academic year, term and any waiver or scholarship, then set the amount and due date.',
                    '3. Track payment: record payments against the invoice, choose the method, deposit bank account and transaction reference.',
                    '4. Handle adjustments: adjust fees with a stated reason where circumstances change, for example a mid-term transfer.',
                    '5. Issue documents: print individual invoices, receipts or statements, or print all filtered invoices in bulk. Export to CSV for reconciliation.',
                ],
                'details' => [
                    'Invoices Versus Structures' => 'An invoice is a charge to one student. A fee structure is the rule used to create many invoices. Editing a structure never changes invoices already raised.',
                    'Overpayments' => 'When a payment exceeds the balance, choose whether to hold it as a deposit or return it, so the account is not silently wrong.',
                    'Audit Trail' => 'Every fee adjustment requires a reason and is recorded, which matters when parents query a changed balance.',
                ],
            ],
            'App\Filament\App\Pages\Finance\FeeCollectionsPage' => [
                'title' => 'Fee Collections',
                'summary' => 'Front-desk view for taking payments: outstanding balances by student, payment capture, and receipting in one place.',
                'workflow' => [
                    '1. Find the student: search by name, class or invoice number to see outstanding balances immediately.',
                    '2. Take the payment: record the amount and method, select the bank account the money is deposited into, and capture the transaction reference.',
                    '3. Issue the receipt: print or share a receipt for the payment taken.',
                    '4. Reconcile: compare the takings for the day against bank deposits at close of business.',
                ],
                'details' => [
                    'Relationship' => 'Payments recorded here update invoice balances immediately, which flows straight into the Finance Overview and Financial Statements.',
                    'Best Practice' => 'Capture the payment reference at the point of collection. Chasing a missing bank reference later is far slower than asking for it upfront.',
                ],
            ],
            'App\Filament\App\Pages\Finance\StudentFinancialHistoryPage' => [
                'title' => 'Student Financial History',
                'summary' => 'Complete account history for an individual student: every charge, payment, refund, waiver and credit carried forward, across all years.',
                'workflow' => [
                    '1. Search for the student: find the account by name or admission number.',
                    '2. Review the ledger: read charges, payments, refunds, waivers and carry-forward entries in date order to reconstruct the full position.',
                    '3. Record a transaction: add a charge or payment directly, or apply a waiver or refund where the invoice route does not fit.',
                    '4. Carry balances forward: move an outstanding debit or credit into the next period deliberately rather than leaving it stranded.',
                    '5. Import history: bring in prior records from a spreadsheet when migrating from another system.',
                ],
                'details' => [
                    'Everything Is Audited' => 'Edits and deletions require a reason and are logged. The history is a legal record of what a family owes and has paid.',
                    'FIFO Allocation' => 'When a payment is not tied to a specific invoice, it is applied to the oldest outstanding balance first, so older debts clear before newer ones.',
                ],
            ],
            FeePaymentSubmissionResource::class => [
                'title' => 'Payment Proofs',
                'summary' => 'Review payment evidence uploaded by parents, and approve or reject each submission so balances only clear once funds are confirmed.',
                'workflow' => [
                    '1. Review the queue: submissions arrive with the student, invoice, method, amount and time submitted.',
                    '2. View the proof: open the uploaded receipt or transfer confirmation before deciding.',
                    '3. Approve: once the funds are confirmed in the bank account, approve the submission and the invoice balance updates.',
                    '4. Reject with a reason: if the proof is unreadable, the amount is wrong or the funds have not arrived, reject it with a reason the parent can act on.',
                ],
                'details' => [
                    'Approval Is Decisive' => 'Approving a proof reduces the invoice balance. Verify the money has actually arrived before approving, not after.',
                    'Best Practice' => 'Give a clear rejection reason. Parents resubmit far faster when told exactly what is wrong with the proof.',
                ],
            ],
            FeeWaiverResource::class => [
                'title' => 'Fee Waivers',
                'summary' => 'Define scholarships, bursaries and discounts that reduce what a student is billed, either as a fixed amount or a percentage.',
                'workflow' => [
                    '1. Create the waiver: name it so the purpose is obvious, for example "Siblings Discount" or "Means Tested Bursary".',
                    '2. Choose the type: set it as a fixed value or a percentage of the fee.',
                    '3. Set the value: enter the amount or percentage the waiver removes.',
                    '4. Apply it: select the waiver when raising an invoice so the reduction is recorded at the point of billing.',
                ],
                'details' => [
                    'Best Practice' => 'Apply waivers at invoice creation rather than as refunds afterwards. Billing the correct amount from the start avoids cash-flow gaps and awkward balances.',
                    'Audit Consideration' => 'Waivers usually reflect a funding decision. Keep the waiver name descriptive so the reason stays visible in financial reports.',
                ],
            ],
            'App\Filament\App\Pages\Finance\ExpensesPurchasingHub' => [
                'title' => 'Expenses & Purchasing (Payables)',
                'summary' => 'Landing page for what the school owes suppliers: operating expenses and the purchasing process that precedes them.',
                'workflow' => [
                    '1. Raise a need: identify what must be bought and raise a procurement request with the items, quantities and estimated costs.',
                    '2. Approve and order: once approved, convert the request into a purchase order for the chosen supplier.',
                    '3. Receive: record goods received against the order when the delivery arrives.',
                    '4. Pay: settle the supplier from the appropriate bank account and record the expense or payable.',
                ],
                'details' => [
                    'Relationship' => 'Procurement Requests, Purchase Orders and Goods Received live in the Inventory & Procurement module; the resulting cost is recognised here in Expenses.',
                    'Best Practice' => 'Do not skip the purchasing steps for routine items. The approval trail is what makes expenditure auditable at year end.',
                ],
            ],
            ExpenseResource::class => [
                'title' => 'Expenses',
                'summary' => 'Record operating expenditure against categories, suppliers and bank accounts, with supporting invoice references.',
                'workflow' => [
                    '1. Record the expense: enter the expense name, category, amount, date and the bank account it was paid from.',
                    '2. Capture the supplier: link the supplier, adding their contact and tax or VAT number if this is their first transaction.',
                    '3. Reference the evidence: enter the supplier invoice or receipt number and any notes.',
                    '4. Classify correctly: choose the right category so expenditure reports group meaningfully.',
                    '5. Verify: review expense status and confirm the charge is reflected in Financial Statements.',
                ],
                'details' => [
                    'Reporting Impact' => 'Category choice drives every expenditure report. Misfiled expenses distort budgets even when the total is right.',
                    'Supplier Records' => 'Supplier details entered here feed the Procurement module, so complete tax numbers to avoid duplicate supplier records.',
                ],
            ],
            'App\Filament\App\Pages\Finance\CoreAccountingHub' => [
                'title' => 'Core Accounting & Setup',
                'summary' => 'Foundational accounting configuration: where money comes from, which accounts it sits in, and the documents used for financial correspondence.',
                'workflow' => [
                    '1. Set up bank accounts: register each account the school holds, with bank name, account name, number and branch, and mark the primary account as default.',
                    '2. Define revenue streams: register each source of income under a category, with a default amount where relevant.',
                    '3. Design document templates: set up the layout for invoices, receipts and statements so financial documents carry the school branding.',
                    '4. Activate: ensure one bank account is the default, since collection screens and payroll defaults rely on it.',
                ],
                'details' => [
                    'Prerequisite Module' => 'This is the first Finance page to configure. Invoices, collections and payroll all need a bank account to post against.',
                    'Best Practice' => 'Record every account the school actually uses, including savings and mobile money accounts, so all cash movements are visible.',
                ],
            ],
            RevenueStreamResource::class => [
                'title' => 'Revenue Streams',
                'summary' => 'Register the sources of income beyond tuition fees, such as donations, grants, uniform sales, transport or facility hire.',
                'workflow' => [
                    '1. Create the stream: give it a name and assign it to a revenue category so reports group correctly.',
                    '2. Link the account: choose the bank account the income is deposited into.',
                    '3. Set a default amount: add a default value where the stream generates a predictable amount each period.',
                    '4. Record actual income: post receipts against the stream as money arrives.',
                    '5. Activate or deactivate: switch off streams that are no longer used without deleting their history.',
                ],
                'details' => [
                    'Relationship' => 'Revenue streams feed the income section of Financial Statements and the Finance Overview, so they must exist before income is posted.',
                    'Deactivate Rather Than Delete' => 'Deactivating preserves historical reporting. Deleting breaks past statements.',
                ],
            ],
            SchoolBankAccountResource::class => [
                'title' => 'School Bank Accounts',
                'summary' => 'Register the bank and mobile money accounts the school operates, and designate the default account used for collections and payroll.',
                'workflow' => [
                    '1. Add the account: enter the bank name, account name, account number and branch code, plus the SWIFT code for international transfers.',
                    '2. Set the default: mark one account as default so collection screens, receipts and payroll deductions default to it.',
                    '3. Set active status: deactivate accounts that are closed without deleting their transaction history.',
                ],
                'details' => [
                    'Prerequisite' => 'At least one active account with a default is required before invoices can be paid, expenses posted or payroll run.',
                    'Paynow and Statements' => 'Payment methods that reference a specific bank account rely on these details, so keep account numbers accurate.',
                ],
            ],
            FinanceDocumentTemplateResource::class => [
                'title' => 'Finance Document Templates',
                'summary' => 'Design the visual layout of financial documents such as invoices, receipts and statements, including school branding and typography.',
                'workflow' => [
                    '1. Create the template: choose the document type and name the template.',
                    '2. Choose a theme: start from a pre-designed layout rather than building from scratch.',
                    '3. Add branding: control the logo, its position and size, school name typography, motto, and address and contact details.',
                    '4. Style the title: set the document title size, colour, and weight, and add any text that should sit beneath it.',
                    '5. Preview and activate: use the live preview, then set it as the active template for that document type.',
                ],
                'details' => [
                    'Scope' => 'One active template per document type. Activating a new template changes future documents without altering previously issued ones.',
                    'Best Practice' => 'Match templates to the jurisdiction requirements for financial records, since statutory documents usually have prescribed elements.',
                ],
            ],
            // =========================================================
            // HR & PAYROLL MODULE
            // =========================================================
            'App\Filament\App\Pages\Hr\StaffDirectoryHub' => [
                'title' => 'Staff Directory & HR',
                'summary' => 'Landing page for the staff lifecycle: employee records, allocated assets and disciplinary processes.',
                'workflow' => [
                    '1. Register employees: create staff records with identity, contract, department, role and salary grade details.',
                    '2. Allocate resources: issue laptops, phones and other assets to staff and track their return.',
                    '3. Manage conduct: record disciplinary cases where needed, with escalation and conclusions.',
                    '4. Keep current: update statuses, contracts and emergency contacts as circumstances change.',
                ],
                'details' => [
                    'Relationship' => 'Employee records drive Payroll Periods, Teacher Assignments and attendance, since all of them key off the staff directory.',
                    'Best Practice' => 'Enter contract end dates and emergency contacts at registration. Both are needed long before anyone remembers to ask.',
                ],
            ],
            EmployeeResource::class => [
                'title' => 'Employees',
                'summary' => 'Complete staff records covering identity, contracts, qualifications, compensation and emergency contacts.',
                'workflow' => [
                    '1. Register a new employee: enter names, national ID or passport, date of birth, gender, phone, email and residential address.',
                    '2. Record the contract: set employment type, job role, department, designation, date joined and any contract end date.',
                    '3. Attach documents: upload the signed contract, academic qualifications and professional certificates as PDFs.',
                    '4. Set compensation: assign a salary grade and, where an arrangement is individual rather than standard, add specific allowances for this employee only.',
                    '5. Record personal details: marital status, spouse and dependent information, next of kin, medical conditions and allergies.',
                    '6. Activate: set the active status and use send activation emails to issue the employee their portal credentials.',
                ],
                'details' => [
                    'Row Actions' => 'Use change status for suspension or termination with a stated reason, promote to move an employee to a new role, assign asset to allocate equipment, and remove profile photo when it is no longer valid.',
                    'Payroll Link' => 'The salary grade assigned here determines what the employee earns in every future payroll run, so changes affect pay immediately.',
                    'Medical Privacy' => 'Medical conditions and allergies are sensitive. Access is permission-controlled and should only be shared with staff who need it for duty of care.',
                ],
            ],
            EmployeeAssetResource::class => [
                'title' => 'Staff Assets',
                'summary' => 'Track equipment and other assets issued to employees, including issue dates, expected returns and damage reports.',
                'workflow' => [
                    '1. Allocate: select the employee and the asset from the system catalogue, or describe it manually when it is not catalogued.',
                    '2. Record the issue: enter the asset name, serial number and issue date, with the condition noted at handover.',
                    '3. Track: the table shows who holds what and when it is due back.',
                    '4. Return or report damage: use return to close the allocation and record the return date, or report damage to log the details of a fault or loss.',
                ],
                'details' => [
                    'Relationship' => 'Assets allocated here correspond to the Fixed Asset register in Inventory & Procurement, so keep serial numbers consistent between the two.',
                    'Best Practice' => 'Record condition at issue and at return. It is the only reliable way to settle disputes over damage.',
                ],
            ],
            DisciplinaryCaseResource::class => [
                'title' => 'Disciplinary Cases',
                'summary' => 'Record and manage staff misconduct cases from incident through hearing, escalation and conclusion.',
                'workflow' => [
                    '1. Open a case: select the employee, describe the offense, set the incident date and rate the severity.',
                    '2. Escalate: use escalate where a case is more serious than initial handling suggests, so it is reviewed at the appropriate level.',
                    '3. Investigate: record findings from hearings and any supporting detail in the resolution notes.',
                    '4. Conclude: close the case with the outcome and the resolution agreed, which is retained on the employee record.',
                ],
                'details' => [
                    'Sensitivity' => 'Disciplinary records are confidential employment records. Access should be restricted to the roles entitled to handle employment matters.',
                    'Documentation' => 'Record hearing findings as you go. Reconstructing what was established months later is difficult and undermines any decision made on the case.',
                ],
            ],
            'App\Filament\App\Pages\Hr\PayrollCompensationHub' => [
                'title' => 'Payroll & Compensation',
                'summary' => 'Landing page for staff pay: define salary structures, run payroll periods and manage staff loans.',
                'workflow' => [
                    '1. Set up grades: define salary grades with base pay, allowances, deductions and the resulting gross and net figures.',
                    '2. Run a payroll period: create the period, calculate salaries for the staff in scope and review the breakdown.',
                    '3. Approve and pay: approve the run to deduct salaries and post the expense against the chosen bank account.',
                    '4. Manage deductions: track staff loans and record repayments alongside the payroll run.',
                ],
                'details' => [
                    'Order Matters' => 'Salary grades must exist before payroll can calculate anything. Employees without a grade are excluded from the run.',
                    'Reversibility' => 'Use undo to reverse a calculated run before approval. Once approved and paid, corrections must be made through adjustments rather than by recalculating.',
                ],
            ],
            PayrollPeriodResource::class => [
                'title' => 'Payroll Periods',
                'summary' => 'Calculate, review, approve and pay staff salaries for a defined period, with a full breakdown of who was paid what.',
                'workflow' => [
                    '1. Create the period: name it and set the start and end dates it covers.',
                    '2. Scope the run: filter by department, employment type, designation, gender or salary grade to calculate a specific group, such as permanent academic staff only.',
                    '3. Calculate: run Calculate and Populate Salaries to compute pay from each employee grade, allowances and deductions.',
                    '4. Review: open the breakdown to check individual figures, and use undo to reverse the run if the scope was wrong.',
                    '5. Approve and deduct: choose the bank account to deduct from, then approve to post the salary expense and finalize the run.',
                ],
                'details' => [
                    'One Run Per Period' => 'A payroll period should be calculated once. Recalculating after approval produces figures that no longer match what was paid.',
                    'Cross-Module Deductions' => 'Approved staff loan repayments are deducted during the run, which is why loan balances should be accurate before approval.',
                    'Audit' => 'The approved run is retained as the authoritative record of what each employee was paid for that period.',
                ],
            ],
            SalaryGradeResource::class => [
                'title' => 'Salary Grades',
                'summary' => 'Define the salary structures staff are appointed to, including base pay, standard allowances and deductions, with gross and net totals calculated for you.',
                'workflow' => [
                    '1. Create the grade: name it and enter the base salary.',
                    '2. Add standard allowances: set housing, transport and duty allowances, and mark whether the role is overtime eligible.',
                    '3. Add custom allowances: define further allowances as either a fixed amount or a percentage of a chosen component.',
                    '4. Add deductions: define deductions and where each is routed, such as to a bank account or to staff loan repayment.',
                    '5. Check the totals: confirm the figure after all allowances and again after deductions matches the intended package.',
                    '6. Reuse: clone an existing grade when introducing a new one that is broadly similar.',
                ],
                'details' => [
                    'Payroll Effect' => 'Every employee appointed to a grade earns this amount. Editing a grade changes future payroll for everyone in it.',
                    'Export' => 'Export selected grades as PDF or CSV for board approval or staff consultation before implementation.',
                    'Individual Exceptions' => 'Where one employee deviates from their grade, use the specific individual allowances on the employee record rather than creating a one-person grade.',
                ],
            ],
            StaffLoanResource::class => [
                'title' => 'Staff Loans',
                'summary' => 'Manage advances and loans to staff, including approval, disbursement, interest and scheduled repayment.',
                'workflow' => [
                    '1. Create the loan: select the employee and loan type, or specify another type, and enter the principal amount and bank account it was disbursed from.',
                    '2. Set the terms: choose the interest system and rate, then set whether the monthly deduction is a fixed amount or a percentage.',
                    '3. Check the figures: the total repayable and monthly deduction are calculated for you.',
                    '4. Approve and disburse: approve the loan and pay the principal to the employee.',
                    '5. Repay: record repayments as they are made; deductions can also be applied automatically during an approved payroll run.',
                ],
                'details' => [
                    'Payroll Interaction' => 'Approved loans are deducted during payroll. Confirm the deduction basis before approving a payroll run, since an incorrect setting deducts the wrong amount from every affected employee.',
                    'Balance Tracking' => 'Balance remaining is maintained automatically as repayments are recorded, so it can be relied on when assessing a further advance.',
                ],
            ],
            'App\Filament\App\Pages\Hr\AttendanceLeaveHub' => [
                'title' => 'Attendance & Leave',
                'summary' => 'Landing page for staff timekeeping: leave requests requiring approval and daily staff attendance records.',
                'workflow' => [
                    '1. Approve leave: review pending requests against each employee leave entitlement and approve or reject with HR remarks.',
                    '2. Record attendance: log check-in and check-out times, or review attendance status for each day.',
                    '3. Reconcile: compare attendance against payroll at period end to identify anomalies such as unrecorded leave.',
                ],
                'details' => [
                    'Entitlement Driven' => 'Leave requests are validated against each employee leave type entitlement, so define leave types with their annual day allowances before staff apply.',
                    'Payroll Significance' => 'Unpaid or absent days affect payroll. Reconcile attendance before calculating a payroll run.',
                ],
            ],
            LeaveRequestResource::class => [
                'title' => 'Leave Requests',
                'summary' => 'Staff leave applications with entitlement tracking, approval workflow and HR remarks on the decision.',
                'workflow' => [
                    '1. Define leave types: set each type with its maximum days per year and description, so entitlement is enforced automatically.',
                    '2. Review requests: check the employee, leave type, dates, total days and the stated reason against remaining entitlement.',
                    '3. Approve: approve requests within entitlement; approval deducts from the balance for that leave year.',
                    '4. Reject with remarks: reject with a reason and add HR remarks so the employee understands the decision.',
                ],
                'details' => [
                    'Entitlement Enforcement' => 'Requests exceeding the annual allowance can be spotted immediately by comparing total days against the leave type maximum and prior approvals.',
                    'Audit Trail' => 'Decisions and HR remarks are retained, which matters when leave disputes or entitlement queries arise later.',
                ],
            ],
            StaffAttendanceResource::class => [
                'title' => 'Staff Attendance',
                'summary' => 'Daily staff attendance with check-in and check-out times, recording method and status per employee per day.',
                'workflow' => [
                    '1. Record attendance: select the employee and date, set the status, and enter the check-in and check-out times in HH:MM format.',
                    '2. Note the method: record how attendance was captured, whether by direct entry, device or an approved alternative.',
                    '3. Review exceptions: filter by date to find missing check-outs, late arrivals and absent staff.',
                    '4. Reconcile: use the records to support payroll and to justify any attendance-related deduction.',
                ],
                'details' => [
                    'Payroll Relevance' => 'Attendance status is evidence for payroll adjustments. Reconcile before calculating a payroll period.',
                    'Data Quality' => 'Incomplete check-out times are the most common cause of payroll queries, so review exceptions regularly.',
                ],
            ],
            // =========================================================
            // INVENTORY & PROCUREMENT MODULE
            // =========================================================
            'App\Filament\App\Pages\Inventory\StockInventoryHub' => [
                'title' => 'Stock & Inventory',
                'summary' => 'Landing page for what the school holds: the item catalogue, movements out to recipients, and adjustments for stock counts.',
                'workflow' => [
                    '1. Catalogue items: register each item with its SKU, category, unit of measure and cost basis.',
                    '2. Track levels: monitor quantity on hand and use the reorder level to spot items that need purchasing.',
                    '3. Issue stock: record movements out to departments, staff, students or other recipients.',
                    '4. Reconcile: run stock adjustments after a physical count to record variances with a reason.',
                ],
                'details' => [
                    'Relationship' => 'Stock movements are driven by Goods Received in Procurement and by clinical or dispensary issues elsewhere, so the register reflects real movements rather than manual entry.',
                    'Best Practice' => 'Keep the reorder level accurate. It is what turns this catalogue into a purchasing signal rather than just a list.',
                ],
            ],
            InventoryItemResource::class => [
                'title' => 'Inventory Items',
                'summary' => 'Catalogue of all goods held by the school, with SKU, barcode, category, stock levels, costing and reorder thresholds.',
                'workflow' => [
                    '1. Add the item: enter the name, SKU and barcode tracking code, and select the category.',
                    '2. Describe it: choose the unit of measure and item type, and add a description and technical properties where relevant.',
                    '3. Set levels: enter the initial quantity on hand, the average unit cost and the low stock warning threshold.',
                    '4. Flag for resale: mark items as saleable where they should appear in student billing.',
                    '5. Review the catalogue: use filters to find items, and export selected items to CSV for stocktake sheets.',
                ],
                'details' => [
                    'Costing' => 'Average cost is the basis for valuation and margins. Recalculate it when costs change materially, or inventory reports understate value.',
                    'Saleable Flag' => 'The saleable flag drives auto-billing integration. Items meant for internal use should stay unflagged so they never appear on a student invoice.',
                    'Reorder Level' => 'This is the trigger for purchasing. Set it above expected usage between deliveries to avoid stockouts.',
                ],
            ],
            InventoryIssuanceResource::class => [
                'title' => 'Stock Issuance',
                'summary' => 'Record goods leaving the stores, whether consumed internally or issued to a specific recipient as a returnable asset.',
                'workflow' => [
                    '1. Select the item: choose the inventory item and source location, adding the batch number where stock is batch tracked.',
                    '2. Set the quantity: enter how much is being issued.',
                    '3. Choose the recipient: specify whether it goes to a staff member, student, department or other recipient type.',
                    '4. Mark returnable: for items such as textbooks or laptops, mark as returnable and set the expected return date and condition on issue.',
                    '5. Record the return: close the allocation when the item comes back, noting the condition on return.',
                ],
                'details' => [
                    'Returnable Versus Consumable' => 'Returnable items stay linked to the recipient until returned. Consumables are simply written off on issue.',
                    'Condition Records' => 'Recording condition at issue and return is what makes damage disputes resolvable.',
                ],
            ],
            StockAdjustmentResource::class => [
                'title' => 'Stock Adjustments',
                'summary' => 'Reconcile recorded stock against a physical count, recording variances per item with a reason and committing them to the audit trail.',
                'workflow' => [
                    '1. Start an adjustment: the adjustment number is generated, and you set the storage zone and the date the count was conducted.',
                    '2. Record each item: select the item or variant and enter the system quantity, the physical quantity counted and the variance the system calculates.',
                    '3. Explain variances: give a reason for each variance line, such as damage, theft, expiry or a miscount.',
                    '4. Commit the audit: finalize the adjustment so the variances post to stock levels and the movement history.',
                ],
                'details' => [
                    'Reason Required' => 'Variances without a reason cannot be relied on later. A shrinkage figure with no explanation invites the same question at every audit.',
                    'Audit Trail' => 'Committed adjustments are permanent and visible in stock movement history. Do not use adjustments to correct data entry mistakes made in error elsewhere.',
                    'Frequency' => 'Count high-value or fast-moving categories more often than slow-moving ones.',
                ],
            ],
            'App\Filament\App\Pages\Inventory\ProcurementHub' => [
                'title' => 'Procurement',
                'summary' => 'Landing page for purchasing: requests, approved orders, goods received and the supplier base.',
                'workflow' => [
                    '1. Request: raise a procurement request with the items, quantities, specifications, urgency and justification.',
                    '2. Approve: review and approve the request, recording the requester and procurement officer signatures.',
                    '3. Order: raise a purchase order against the approved request for the selected supplier.',
                    '4. Approve the order: authorize the order and deduct payment from the relevant bank account where payment is immediate.',
                    '5. Receive: record goods received against the order, accepting or rejecting quantities and capturing batch and expiry details.',
                    '6. Evaluate: review supplier records and tax details for future purchasing.',
                ],
                'details' => [
                    'Control Chain' => 'Request, order and receipt are separate records on purpose. Skipping a step removes the evidence that expenditure was authorized.',
                    'Supplier Master Data' => 'Suppliers registered here carry into Finance, so complete tax numbers to avoid duplicates.',
                ],
            ],
            ProcurementRequestResource::class => [
                'title' => 'Procurement Requests',
                'summary' => 'Formal requests to purchase goods or services, with item lines, urgency, justification and an approval trail.',
                'workflow' => [
                    '1. Raise the request: the request number is generated. Enter the department, urgency and purpose.',
                    '2. Add items: for each line, describe the item or link an existing catalogue item, set the quantity and estimated unit cost, and note specifications.',
                    '3. Flag capital items: mark lines that are fixed assets so they are routed to asset registration on receipt.',
                    '4. Sign off: record the requester signature, the procurement officer name and the date signed.',
                    '5. Approve: approve the request to authorize it, and record the approver and approval date.',
                    '6. Select a supplier: where known, nominate a preferred supplier with contact and tax details, saving the supplier for reuse.',
                ],
                'details' => [
                    'Urgency' => 'Urgency affects prioritization when several requests compete for the same budget, so set it honestly.',
                    'Documentation' => 'Export the request as PDF to obtain wet signatures where policy requires them, since the PDF reflects what was actually requested.',
                ],
            ],
            PurchaseOrderResource::class => [
                'title' => 'Purchase Orders',
                'summary' => 'Authorized orders placed with suppliers, tracking expected delivery, order value and payment.',
                'workflow' => [
                    '1. Create the order: select the approved procurement request, enter the order number, supplier and order date, and set the expected delivery date.',
                    '2. Add line items: specify each item, quantity ordered and unit cost, and flag capital items for asset registration.',
                    '3. Review the total: the order total is calculated from the lines.',
                    '4. Approve: authorize the order. Use deduct from bank account where payment is made immediately rather than on credit.',
                    '5. Print: issue the order document to the supplier.',
                    '6. Receive: use receive goods once the delivery arrives, and compare GRN to confirm what was actually supplied.',
                ],
                'details' => [
                    'Partial Deliveries' => 'An order may be received in several deliveries. The GRN shows outstanding quantities so remaining deliveries can be tracked.',
                    'Payment Timing' => 'Paying at order stage affects cash immediately. Deferring payment to the GRN stage usually matches supplier terms better.',
                ],
            ],
            GoodsReceivedResource::class => [
                'title' => 'Goods Received',
                'summary' => 'Record deliveries against purchase orders, accepting or rejecting quantities line by line and capturing batch and expiry data.',
                'workflow' => [
                    '1. Start the GRN: the GRN number is generated. Select the purchase order and the received date, and record who received the delivery.',
                    '2. Compare with the order: the GRN shows the quantity ordered, the quantity already received and the outstanding quantity for every line.',
                    '3. Accept or reject: enter the quantity accepted and rejected now, with the batch number and expiry date where the goods are perishable or batch tracked.',
                    '4. Confirm: post the receipt so accepted quantities increase stock levels.',
                ],
                'details' => [
                    'Three-Way Match' => 'Comparing the PO, this delivery and any previous receipts prevents paying twice for the same goods or accepting more than was ordered.',
                    'Rejections' => 'Record rejected quantities rather than editing the ordered amount, so the discrepancy with the supplier remains visible and auditable.',
                    'LPO Reference' => 'Keep the local purchase order reference so deliveries can be matched to paperwork later.',
                ],
            ],
            SupplierResource::class => [
                'title' => 'Suppliers',
                'summary' => 'The supplier master record, holding contact details and tax registration used across purchasing and expenses.',
                'workflow' => [
                    '1. Add the supplier: enter the company name, contact person, phone, email and physical address.',
                    '2. Record tax details: capture the VAT or tax registration number so tax documents remain valid.',
                    '3. Review: filter the list and check details before approving payments to new suppliers.',
                ],
                'details' => [
                    'Reuse' => 'Suppliers recorded here are available to Procurement Requests and Expenses. Correcting a tax number once prevents inconsistent records in both modules.',
                    'Verification' => 'Confirm bank details and tax registration directly with the supplier before the first substantial payment.',
                ],
            ],
            'App\Filament\App\Pages\Inventory\FixedAssetsHub' => [
                'title' => 'Fixed Assets',
                'summary' => 'Landing page for long-term assets: the asset register with valuations and depreciation, and the maintenance schedule that keeps them serviceable.',
                'workflow' => [
                    '1. Register assets: record each asset with its number, purchase cost, useful life, location, custodian and funding source.',
                    '2. Maintain them: schedule preventive maintenance and record completed work with its cost.',
                    '3. Value them: post depreciation to keep the current value and net worth reporting accurate.',
                    '4. Reconcile with stock: fixed assets flagged on procurement appear here as well as in the inventory catalogue.',
                ],
                'details' => [
                    'Dual Record' => 'An asset flagged as capital on a procurement request appears in both the asset register and inventory. Keep the asset number consistent between them.',
                    'Relationship' => 'Asset depreciation affects the balance sheet in Financial Statements, so schedule depreciation from acquisition rather than retrospectively.',
                ],
            ],
            FixedAssetResource::class => [
                'title' => 'Fixed Assets',
                'summary' => 'The long-term asset register with acquisition values, depreciation methods, valuations, locations and custodians.',
                'workflow' => [
                    '1. Register the asset: enter the asset number, name, serial number, description, acquisition date, purchase cost and salvage value.',
                    '2. Link to stock: optionally associate the asset with its catalogue item so it appears in inventory reporting.',
                    '3. Set the depreciation method: choose a method and set the useful life in years.',
                    '4. Assign location and custodian: select where the asset is and who is responsible for it, with location and custodian details recorded centrally.',
                    '5. Record the funding source: note how the asset was paid for, which matters for restricted funding.',
                    '6. Post depreciation: run depreciation on a schedule so current value stays accurate.',
                ],
                'details' => [
                    'Valuation Accuracy' => 'Current value drives the balance sheet. Post depreciation consistently, otherwise reported net worth drifts from reality.',
                    'Custody' => 'Naming a custodian creates accountability and links the asset to the person accountable for it.',
                ],
            ],
            AssetMaintenanceResource::class => [
                'title' => 'Asset Maintenance',
                'summary' => 'Preventive and corrective maintenance schedule for fixed assets, tracking scheduled dates, recurrence and cost.',
                'workflow' => [
                    '1. Schedule the work: select the asset, give the job a title, choose the type and set the scheduled date.',
                    '2. Set recurrence: for repeating work, set the recurrence interval in days so the next task is generated automatically.',
                    '3. Complete: record the completion date, who performed the work, the cost incurred and any notes.',
                    '4. Track cost: review total maintenance cost against asset value to judge whether replacement is more economical.',
                ],
                'details' => [
                    'Cost Accumulation' => 'Maintenance costs accumulate against the asset and inform the replacement decision, so enter them even when invoiced through Finance.',
                    'Recurrence Matters' => 'A recurrence interval keeps compliance-driven work, such as safety inspections, from lapsing.',
                ],
            ],
            // =========================================================
            // LIBRARY MODULE
            // =========================================================
            'App\Filament\App\Pages\Library\CatalogueHub' => [
                'title' => 'Library Catalogue',
                'summary' => 'Landing page for everything the library holds: physical books and electronic resources, catalogued with copies and availability.',
                'workflow' => [
                    '1. Catalogue holdings: add books with title, authors, ISBN, classification and format, plus the initial number of physical copies.',
                    '2. Add eResources: register digital items with an uploaded document or an external link.',
                    '3. Issue and return: circulate titles to students and staff through Circulation.',
                    '4. Maintain: track copy states as damaged or lost so availability counts remain truthful.',
                ],
                'details' => [
                    'Copy Tracking' => 'Availability is counted per physical copy, not per title. Copy totals for available, damaged and lost drive what Circulation can actually lend.',
                    'Relationship' => 'Circulation borrows from the catalogue, so catalogue items must exist before anything can be issued.',
                ],
            ],
            LibraryBookResource::class => [
                'title' => 'Books',
                'summary' => 'Catalogue of physical library holdings with bibliographic detail, classification, cover art and per-copy availability.',
                'workflow' => [
                    '1. Add the title: enter title, subtitle, authors or creators, and the publisher and publication year.',
                    '2. Classify: assign the system category and classification, and choose a format class or define a custom one.',
                    '3. Identify: enter the ISBN and any target classification references.',
                    '4. Add copies: set the initial physical copy count, which creates the individual copies that Circulation issues against.',
                    '5. Upload the cover: add cover graphics so the catalogue is browsable by appearance rather than title alone.',
                ],
                'details' => [
                    'Copy States' => 'Each copy is tracked separately as available, issued, damaged or lost, so availability counts stay accurate over time.',
                    'Author Records' => 'Authors are recorded as reusable entities, so consistent entry produces proper authority lists later.',
                ],
            ],
            EResourceResource::class => [
                'title' => 'eResources',
                'summary' => 'Electronic holdings such as PDFs, audio and linked media, with download and open-link access for authorized users.',
                'workflow' => [
                    '1. Add the resource: enter the title, subtitle and authors or creators.',
                    '2. Classify: assign the category, format class and subject classification.',
                    '3. Provide access: upload the document or enter the resource URL or YouTube link.',
                    '4. Distribute: use download, open link, or pack and download selected to distribute resources to a class.',
                ],
                'details' => [
                    'Access Control' => 'eResource distribution depends on role permissions. Confirm recipients are authorized before bulk distributing paid or licensed content.',
                    'Link Longevity' => 'Where a resource is hosted externally, links can rot. Prefer uploaded copies for anything that must remain available.',
                ],
            ],
            'App\Filament\App\Pages\Library\CirculationHub' => [
                'title' => 'Library Circulation',
                'summary' => 'Landing page for lending: the issue counter for borrowing titles, and the live list of everything currently on loan.',
                'workflow' => [
                    '1. Issue: from the Issue Book counter, select the title and the specific copy or barcode, choose the borrower, and set the due date.',
                    '2. Track: the Issues list shows every active loan, its borrower and its due date.',
                    '3. Return: record returns as titles come back, updating availability immediately.',
                    '4. Follow up: use due dates to identify overdue items and contact borrowers.',
                ],
                'details' => [
                    'Copy Level' => 'Loans are made against a specific copy, not a title. This is what allows the same title to be available to several students simultaneously.',
                    'Overdue Management' => 'Overdue items do not block other loans automatically, so follow up administratively to keep turnover healthy.',
                ],
            ],
            'App\Filament\App\Pages\IssueBook' => [
                'title' => 'Issue Book',
                'summary' => 'The front-desk counter for lending library items to students and staff, selecting the specific copy and setting the due date.',
                'workflow' => [
                    '1. Find the title: search for the resource title the borrower wants.',
                    '2. Select the copy: choose the specific copy or scan its barcode, so availability is tracked per physical item.',
                    '3. Identify the borrower: search for the student, or search a staff borrower for non-student loans.',
                    '4. Set the due date: enter the return due date consistent with the library policy.',
                    '5. Confirm: complete the issue, which reduces the available copy count and appears in Issues.',
                ],
                'details' => [
                    'Faster Counter Service' => 'Scanning or searching by barcode rather than title is much quicker at the counter, especially for common titles.',
                    'Prerequisite' => 'The borrower must already exist in the system, so ensure students are enrolled and staff are registered before issuing.',
                ],
            ],
            LibraryIssueResource::class => [
                'title' => 'Library Issues',
                'summary' => 'The register of every loan: which copy was issued, to whom, when, and when it is due back.',
                'workflow' => [
                    '1. Review loans: filter to see what is currently on loan by borrower, title or due date.',
                    '2. Record returns: close a loan when the item is returned, which returns the copy to available stock.',
                    '3. Flag problems: mark a copy damaged or lost when it comes back unusable or unaccounted for.',
                    '4. Follow up: identify overdue items and contact borrowers before they block others.',
                ],
                'details' => [
                    'Availability Integrity' => 'Each row is one physical copy in one borrower hands. Closing rows promptly is what keeps the available counts trustworthy.',
                    'Damaged And Lost' => 'Recording damage or loss rather than quietly adjusting counts preserves the library history for stocktaking.',
                ],
            ],
            'App\Filament\App\Pages\Knowledge\KnowledgeHub' => [
                'title' => 'Knowledge Repository',
                'summary' => 'Landing page for the institutional knowledge base: shared assets and the galleries that present them.',
                'workflow' => [
                    '1. Add assets: register documents, media and other shared resources with their metadata and access visibility.',
                    '2. Curate galleries: group related assets into galleries for themed or class-specific access.',
                    '3. Distribute: share individual assets or bulk download gallery contents.',
                    '4. Control access: set visibility so material is restricted to appropriate roles.',
                ],
                'details' => [
                    'Visibility Matters' => 'Access visibility determines who can retrieve an asset. Review it when material is confidential or licensed.',
                    'Relationship' => 'The repository is broader than the library catalogue and holds material that is not a formal library holding.',
                ],
            ],
            KnowledgeAssetResource::class => [
                'title' => 'Knowledge Assets',
                'summary' => 'Shared institutional documents and media with full metadata, cover art, document upload or external links, and access controls.',
                'workflow' => [
                    '1. Add the asset: enter the resource title, subtitle or volume, and the authors or creators.',
                    '2. Classify: assign the system category, sub-classification, classification and format class.',
                    '3. Identify: record the reference number or ISBN, and any curriculum reference.',
                    '4. Set visibility: choose who may access it, since this governs retrieval for every user.',
                    '5. Attach the resource: upload the document or enter a resource URL or YouTube link, and add a cover graphic.',
                    '6. Add copies: set the initial physical copy count where printed copies are also held.',
                ],
                'details' => [
                    'Curriculum Alignment' => 'Curriculum references let you prove which teaching material maps to syllabus objectives, which matters for inspection evidence.',
                    'Copy Tracking' => 'Copy counts behave as in the library catalogue, with available, damaged and lost tracked per copy.',
                ],
            ],
            KnowledgeGalleryResource::class => [
                'title' => 'Galleries',
                'summary' => 'Curated collections of knowledge assets, presented together for themed access or bulk distribution.',
                'workflow' => [
                    '1. Create the gallery: give it a title, category and classification reflecting its purpose.',
                    '2. Add assets: include the relevant knowledge assets, keeping each gallery focused on one theme or class.',
                    '3. Share: use download, open link, or pack and download selected to distribute the collection.',
                ],
                'details' => [
                    'Purpose' => 'Galleries make bulk distribution practical. Packaging a topic set once avoids repeated individual downloads when preparing class material.',
                    'Classification' => 'Consistent classification keeps galleries findable alongside the underlying assets.',
                ],
            ],

            // =========================================================
            // BOARDING & WELFARE MODULE
            // =========================================================
            'App\Filament\App\Pages\Boarding\AccommodationHub' => [
                'title' => 'Accommodation',
                'summary' => 'Landing page for boarding: hostels, their rooms and beds, and the allocations that place students in them.',
                'workflow' => [
                    '1. Define hostels: register each hostel with its type, capacity, status and the floors and wings it contains.',
                    '2. Define rooms: add rooms within floors and wings, setting room type and capacity.',
                    '3. Allocate beds: place students in specific rooms and beds for an academic year, recording the condition at handover.',
                    '4. Review: monitor occupancy against hostel capacity to identify spare or over-subscribed space.',
                ],
                'details' => [
                    'Prerequisite Order' => 'Hostels, then rooms, then allocations. A bed cannot exist before its room.',
                    'Capacity Integrity' => 'Allocations warn when a room would exceed capacity, which is the main control against over-crowding.',
                ],
            ],
            HostelResource::class => [
                'title' => 'Hostels',
                'summary' => 'Boarding facilities with their type, capacity, status and internal structure of floors and wings.',
                'workflow' => [
                    '1. Add the hostel: enter the name, type (for example boys, girls or mixed), capacity and status.',
                    '2. Describe it: add a description covering facilities and any rules specific to that hostel.',
                    '3. Build the structure: add floors with their floor number and name, and add wings within each floor with their name and description.',
                    '4. Verify capacity: confirm the structure can accommodate the stated capacity before allocating students.',
                ],
                'details' => [
                    'Structure First' => 'Rooms are attached to a floor and wing, so the structure must exist before rooms can be added.',
                    'Gender Separation' => 'Hostel type is what enforces appropriate allocation. Set it carefully, since it determines who may be housed where.',
                ],
            ],
            HostelRoomResource::class => [
                'title' => 'Hostel Rooms',
                'summary' => 'Individual rooms within hostels, organized by floor and wing, with room type and bed capacity.',
                'workflow' => [
                    '1. Select the location: choose the hostel, then the floor and wing the room sits in.',
                    '2. Identify the room: enter the room number and name, and choose the room type.',
                    '3. Set capacity: define how many beds the room holds, which drives allocation limits.',
                    '4. Track status: room status shows whether it is available, occupied or out of service.',
                ],
                'details' => [
                    'Capacity Enforcement' => 'Room capacity is enforced during allocation, so setting it accurately is what prevents overcrowding.',
                    'Out Of Service' => 'Mark unusable rooms out of service rather than deleting them, so history and outstanding allocations remain visible.',
                ],
            ],
            HostelAllocationResource::class => [
                'title' => 'Hostel Allocations',
                'summary' => 'Placement of students in specific rooms and beds for an academic year, with condition checks at allocation and checkout.',
                'workflow' => [
                    '1. Select the student: choose the student and the academic year the placement applies to.',
                    '2. Choose the bed: select the room and then the specific bed. The page shows hostel, wing and floor for verification.',
                    '3. Record the condition: note the room and bed condition at allocation, and set the expected checkout date.',
                    '4. Add notes: record anything relevant to the placement.',
                    '5. Review occupancy: check status to see which beds are occupied and which remain free.',
                ],
                'details' => [
                    'Capacity Warning' => 'The page warns when an allocation would exceed room capacity. Treat that as a hard stop, since it is the primary safeguard in boarding oversight.',
                    'Checkout Condition' => 'Recording condition at allocation and at expected checkout protects the school when damage or loss is disputed.',
                ],
            ],

            // =========================================================
            // HEALTH & SAFETY MODULE
            // =========================================================
            'App\Filament\App\Pages\Health\HealthRecordsHub' => [
                'title' => 'Health Records',
                'summary' => 'Landing page for student health: the medical background held per student, and the clinical visits recorded against it.',
                'workflow' => [
                    '1. Record background: capture blood group, allergies, chronic conditions and immunization history for each student.',
                    '2. Log visits: record clinic visits with symptoms, vital signs, diagnosis, treatment given and any prescription.',
                    '3. Link stock: issue prescribed medicine from inventory so dispensary usage is recorded.',
                    '4. Discharge: close visits with the final diagnosis, treatment actions and any referral destination.',
                ],
                'details' => [
                    'Safety Critical' => 'Allergies and chronic conditions must be accurate. They are what makes the clinic safe to treat a student.',
                    'Confidentiality' => 'Health information is sensitive personal data. Access is permission-controlled and must not be used for non-clinical purposes.',
                ],
            ],
            StudentMedicalRecordResource::class => [
                'title' => 'Medical Records',
                'summary' => 'Per-student medical background: blood group, allergies, chronic conditions and immunization history.',
                'workflow' => [
                    '1. Select the student: choose the learner whose record you are maintaining.',
                    '2. Record essentials: enter blood group, known allergies and any chronic conditions.',
                    '3. Log immunization: add each vaccine administered with its name and the date given.',
                    '4. Keep current: update the record whenever a new diagnosis, allergy or immunization arises.',
                ],
                'details' => [
                    'Immediate Use' => 'Allergies are surfaced at the point of treatment. An incomplete record is a clinical risk rather than an administrative one.',
                    'Consent And Privacy' => 'Handle these records only where necessary for the student care or safeguarding duty.',
                ],
            ],
            ClinicVisitResource::class => [
                'title' => 'Clinic Visits',
                'summary' => 'Individual clinic attendances with vital signs, symptoms, diagnosis, treatment, prescriptions and discharge outcomes.',
                'workflow' => [
                    '1. Record the attendance: select the student and visit time, and enter body temperature in degrees Celsius and blood pressure.',
                    '2. Describe the presentation: record the symptoms observed.',
                    '3. Diagnose and treat: enter the diagnosis and the treatment given.',
                    '4. Prescribe: link a stock product and record the medicine, dosage, frequency and quantity prescribed, so dispensary usage is tracked.',
                    '5. Discharge: close the visit with the final diagnosis, the final treatment actions and any referral destination.',
                    '6. Review: filter by status to track open visits and confirm all are discharged.',
                ],
                'details' => [
                    'Stock Link' => 'Prescriptions linked to inventory keep clinic usage and stock levels consistent, and reveal when medicines need reordering.',
                    'Referral' => 'Record where a student was referred when care was escalated, so follow-up with families and other institutions is traceable.',
                    'Confidentiality' => 'Clinical detail is restricted to staff with a clinical need to know.',
                ],
            ],
            // =========================================================
            // COMMUNICATION CENTER MODULE
            // =========================================================
            'App\Filament\App\Pages\CommunicationCenter' => [
                'title' => 'Communication Center',
                'summary' => 'Overview of the school communication channels: announcements, events, chat, shared resources, polls and the support inbox.',
                'workflow' => [
                    '1. Announce: publish notices to staff, students and parents, with priority, attachments and acknowledgement where needed.',
                    '2. Schedule events: add calendar events with categories, locations and target roles.',
                    '3. Discuss: use chat threads for group and direct conversation, posting alerts where urgent.',
                    '4. Share resources: publish downloadable campus resources with version tracking.',
                    '5. Consult: run polls and surveys with role targeting and participation tracking.',
                    '6. Support: handle helpdesk tickets and messages to Kairo CORE from one place.',
                ],
                'details' => [
                    'Personal Schedule' => 'Schedule and My Day bring the user tasks and calendar together, so communication items and personal workload can be checked in sequence.',
                    'Relationship' => 'Everything here is permission-aware: what a user can publish, read and respond to depends on their role.',
                ],
            ],
            'App\Filament\App\Pages\Schedule' => [
                'title' => 'Schedule & Tasks',
                'summary' => 'The personal working day: your calendar and your tasks in one place, so meetings, deadlines and follow-ups are seen together.',
                'workflow' => [
                    '1. Review the calendar: see events and meetings that involve you.',
                    '2. Review your tasks: work through assigned items, noting who each is assigned to where tasks are shared.',
                    '3. Create tasks: add new tasks with a clear title and due date.',
                    '4. Keep current: update task status as work progresses, since the list reflects what is genuinely outstanding.',
                ],
                'details' => [
                    'Shared Work' => 'Tasks can be assigned to others. Assignment is visible to participants so nothing is assumed to be handled.',
                    'Relationship' => 'Calendar entries can originate from the Events page in the Communication Center, so publishing an event with your role targeted is what puts it on this schedule.',
                ],
            ],
            'App\Filament\App\Pages\Communication\CommunityHub' => [
                'title' => 'Community & Engagement',
                'summary' => 'Landing page for engagement with students, parents and staff: notices, events, conversation, resources and surveys.',
                'workflow' => [
                    '1. Inform: publish announcements and notices with priorities, attachments and optional acknowledgement.',
                    '2. Schedule: maintain the events calendar with categories, locations and target roles.',
                    '3. Converse: manage chat threads, including bulletin alerts for urgent messages.',
                    '4. Share: publish campus resources that staff and students can download.',
                    '5. Consult: run polls and surveys and watch participation.',
                ],
                'details' => [
                    'Targeting' => 'Visibility and target roles determine who sees each item. Check targeting before publishing anything sensitive.',
                    'Acknowledgement' => 'Requiring acknowledgement on a notice gives you a record of who has actually seen it, which matters for critical announcements.',
                ],
            ],
            AnnouncementResource::class => [
                'title' => 'Announcements',
                'summary' => 'Notices published to staff, students and parents, with priority, styling, attachments, visibility and acknowledgement tracking.',
                'workflow' => [
                    '1. Write the notice: enter the title and content, then set the priority and choose a display style.',
                    '2. Target it: set visibility to the roles who should see it, using roles rather than naming individuals where possible.',
                    '3. Attach documents: upload PDFs, Word documents, spreadsheets or images where supporting material is needed.',
                    '4. Schedule: set the published date and an expiry so the notice ages out rather than lingering.',
                    '5. Publish: move it to published. Use publish again from the table for bulk publishing.',
                    '6. Review history: show history to include expired and draft notices when reviewing what has gone out.',
                ],
                'details' => [
                    'Acknowledgement' => 'Requiring acknowledgement records who has read the notice, giving you a defensible record for critical announcements.',
                    'Expiry' => 'Setting an expiry keeps the notice board current. Without it, outdated notices remain visible and get acted on in error.',
                ],
            ],
            EventCalendarResource::class => [
                'title' => 'Events',
                'summary' => 'The school events calendar with categories, locations, times and role targeting, which also feeds personal schedules.',
                'workflow' => [
                    '1. Create the event: enter the title, description and category.',
                    '2. Place it: set the location, start and end times, and a colour to distinguish the event type.',
                    '3. Target roles: choose which roles the event applies to, so it reaches the right people.',
                    '4. Review: filter by category or date to plan around the academic and extracurricular calendar.',
                ],
                'details' => [
                    'Schedule Feed' => 'Events targeted at a role appear on that role members Schedule page, so targeting is what determines whether people actually see an event.',
                    'Clash Awareness' => 'Check times against the timetable before publishing, since events frequently conflict with lessons and assemblies.',
                ],
            ],
            ChatThreadResource::class => [
                'title' => 'Chat',
                'summary' => 'Conversation threads between staff, with group discussions, member management, message counts and urgent bulletin alerts.',
                'workflow' => [
                    '1. Start a thread: give it a group name and select the participants, or use a direct conversation.',
                    '2. Manage members: add or remove participants as the discussion evolves.',
                    '3. Post alerts: use the bulletin alert for messages that must interrupt, distinguishing them from ordinary chat.',
                    '4. Monitor activity: the table shows active members, messages sent and last activity, which helps identify stalled threads.',
                ],
                'details' => [
                    'Official Records' => 'Chat is convenient but easily lost. Put anything that becomes an official instruction into an announcement or task instead.',
                    'Alerts' => 'Reserve bulletin alerts for genuinely urgent matters. Frequent alerts train recipients to ignore them.',
                ],
            ],
            CampusResourceResource::class => [
                'title' => 'Campus Resources',
                'summary' => 'Shared downloadable resources with categories, tags, version tracking and download counts.',
                'workflow' => [
                    '1. Add the resource: enter the title, description, category and tags, and set the version.',
                    '2. Attach files: upload the asset file and a cover image where a visual helps staff find it.',
                    '3. Set eligibility: define who is allowed to download it.',
                    '4. Publish: make it available to the intended audience.',
                    '5. Track use: the download count shows which resources are actually reaching staff.',
                ],
                'details' => [
                    'Version Control' => 'Bump the version when content changes so users do not work from superseded documents, and so the current version is unambiguous.',
                    'Usage Signal' => 'Low download counts on resources people should be using usually indicate a discoverability problem rather than a lack of need.',
                ],
            ],
            PollResource::class => [
                'title' => 'Polls & Surveys',
                'summary' => 'Polls and surveys with choice options, anonymous or attributed responses, role targeting and live participation standings.',
                'workflow' => [
                    '1. Create the question: write the poll or survey question and add a description with context.',
                    '2. Choose the type: select the appropriate poll or survey type and add each choice option.',
                    '3. Set anonymity: leave anonymous on for honest feedback, or turn it off where individual accountability is required.',
                    '4. Target and close: choose target roles and set the closing date.',
                    '5. Vote: record votes from the table as they arrive.',
                    '6. Watch participation: current standing shows the percentage and count per option, so low participation can be acted on before the poll closes.',
                ],
                'details' => [
                    'Anonymity' => 'Anonymous polls get more truthful answers. Announce anonymity explicitly, or respondents will assume responses are attributed and answer cautiously.',
                    'Closing Date' => 'A closing date creates urgency. Without one, polls linger and responses arrive too late to influence anything.',
                ],
            ],
            'App\Filament\App\Pages\Communication\HelpInboxHub' => [
                'title' => 'Help & Inbox',
                'summary' => 'The support channel: helpdesk tickets from users and messages exchanged with Kairo CORE support.',
                'workflow' => [
                    '1. Handle tickets: pick up new helpdesk tickets, assign them to an agent and reply.',
                    '2. Escalate internally: use internal notes for staff discussion that the submitter must not see.',
                    '3. Close with resolution: record a resolution summary so the outcome is documented.',
                    '4. Contact Kairo CORE: raise platform support messages from the inbox and track replies.',
                ],
                'details' => [
                    'Internal Notes' => 'Internal notes are hidden from parents and students. Anything a submitter should read belongs in a reply, not a note.',
                    'Two Channels' => 'Helpdesk tickets handle school-internal issues. Messages to Kairo CORE are for platform and licensing matters, so route problems to the right channel to avoid delay.',
                ],
            ],
            HelpdeskTicketResource::class => [
                'title' => 'Helpdesk',
                'summary' => 'Support tickets from staff, students and parents, with assignment, replies, internal notes and documented resolution.',
                'workflow' => [
                    '1. Open the ticket: submitter, category, subject, description, priority and status are captured so nothing important is lost.',
                    '2. Assign: pick a support agent so ownership is explicit and the queue does not stall.',
                    '3. Reply: respond to the submitter with the answer or the information they need.',
                    '4. Take internal notes: record staff-only discussion using internal notes, hidden from parents and students.',
                    '5. Close: resolve with a resolution summary that states what was done and what the submitter should expect.',
                ],
                'details' => [
                    'Ownership Matters' => 'An unassigned ticket is the main cause of slow support. Assign on sight rather than at the end of the day.',
                    'Resolution Quality' => 'A good resolution summary prevents the same question being reopened as a new ticket.',
                    'Priority' => 'Set priority to reflect genuine urgency, so blockers are not queued behind routine questions.',
                ],
            ],
            PlatformInboxResource::class => [
                'title' => 'Kairo CORE Messages',
                'summary' => 'Two-way correspondence with Kairo CORE platform support, covering system issues, licensing and product questions.',
                'workflow' => [
                    '1. Send a message: state the subject, body and priority, including the school name and any relevant error text.',
                    '2. Track the thread: view the thread to follow replies from platform support.',
                    '3. Reply: respond within the thread to supply the detail support asks for.',
                    '4. Mark as read: confirm receipt so outstanding replies are not overlooked.',
                ],
                'details' => [
                    'Direction' => 'The direction column distinguishes messages you sent from replies received, which keeps long threads readable.',
                    'Include Detail' => 'Platform support cannot see your screens. Include exact error messages, the affected page and what you expected to happen to speed resolution.',
                    'Scope' => 'Use this channel for platform and subscription matters. School-internal questions belong in the Helpdesk.',
                ],
            ],

            // =========================================================
            // LMS MODULE
            // =========================================================
            'App\Filament\App\Pages\Lms\LmsHub' => [
                'title' => 'Homework & Lessons',
                'summary' => 'Landing page for learning materials: setting, distributing and tracking homework and lesson content.',
                'workflow' => [
                    '1. Set homework: create an assignment for a class stream and subject with a due date.',
                    '2. Attach materials: add instructions, a worksheet file and a supporting lesson link.',
                    '3. Distribute: share the assignment directly with the class, including by WhatsApp where parents are the audience.',
                    '4. Track submissions: monitor the submission count against each assignment to identify who has not handed work in.',
                ],
                'details' => [
                    'Class Targeting' => 'Homework is set per class stream and subject, so the cohort receiving each assignment is explicit.',
                    'Relationship' => 'Submissions counts come from student portal activity, which is the quickest way to identify who needs a reminder.',
                ],
            ],
            HomeworkResource::class => [
                'title' => 'Homework',
                'summary' => 'Assignments issued to a class stream for a subject, with due dates, supporting materials and submission tracking.',
                'workflow' => [
                    '1. Create the assignment: enter the assignment title, select the class stream and subject, and set the submission due date.',
                    '2. Write instructions: use the instructions or study guide to explain exactly what is expected.',
                    '3. Attach materials: upload a worksheet or study guide, and add a YouTube lesson link where a video supports the task.',
                    '4. Distribute: use share WhatsApp to send the assignment to parents where that is the agreed channel.',
                    '5. Track: the submissions count shows how many students have submitted, so non-submitters can be followed up.',
                ],
                'details' => [
                    'Clear Due Dates' => 'Explicit due dates, including the time where relevant, prevent the disputes that arise when students assume a deadline.',
                    'Materials' => 'Attach the worksheet rather than describing it. It removes ambiguity about what was actually set.',
                ],
            ],

            // =========================================================
            // WEBSITE MODULE
            // =========================================================
            'App\Filament\App\Pages\Website\WebsiteTemplatesHub' => [
                'title' => 'Templates & Design',
                'summary' => 'Landing page for the public-facing website: the sites themselves, their pages, and visual design control.',
                'workflow' => [
                    '1. Create the website: choose a foundation template and set the site title, title suffix, and branding colors and fonts.',
                    '2. Build pages: create and edit pages, set one as the homepage and publish when ready.',
                    '3. Design: use the visual studio and content manager for in-context editing.',
                    '4. Maintain: keep page content current so public information stays accurate.',
                ],
                'details' => [
                    'Public Facing' => 'Everything here is visible to the public. Review changes before publishing, since correcting a live error is harder than preventing one.',
                    'Templates' => 'Changing the active template restyles the site without altering content, so restyling is low risk if content is left intact.',
                ],
            ],
            CmsWebsiteResource::class => [
                'title' => 'Websites',
                'summary' => 'Public-facing websites for the school, with a chosen template, branding colors and typography.',
                'workflow' => [
                    '1. Create the site: choose a website foundation to start from rather than a blank layout.',
                    '2. Brand it: set the title suffix such as " | Royal Academy", the primary font and the primary accent color.',
                    '3. Assign the template: choose the active template that governs the site layout.',
                    '4. Maintain: edit content through the Content Manager and pages.',
                ],
                'details' => [
                    'Live Effect' => 'Changes affect the public site. Confirm branding and template choices before considering the site complete.',
                    'Relationship' => 'The template controls presentation; page content is managed separately on the Pages page.',
                ],
            ],
            CmsPageResource::class => [
                'title' => 'Website Pages',
                'summary' => 'Content pages for the public website, with publishing state, homepage designation and visual editing.',
                'workflow' => [
                    '1. Create the page: enter the title and slug. Choose meaningful slugs, since they appear in public URLs and are hard to change later without breaking links.',
                    '2. Edit visually: use Open Visual Studio for in-context editing of the page content.',
                    '3. Set the homepage: designate one page as the homepage so the site root always has content.',
                    '4. Publish: publish the page once content is verified. Unpublished pages remain drafts.',
                    '5. Review freshness: check last modified to spot pages that are long out of date.',
                ],
                'details' => [
                    'One Homepage' => 'Exactly one page should be the homepage. Confirm the designation after structural changes to the site.',
                    'Public Accuracy' => 'Publish only verified content. Anything published here is immediately visible to the public.',
                ],
            ],
            'App\Filament\App\Pages\WebsiteContentManager' => [
                'title' => 'Website Content Manager',
                'summary' => 'Edit website content in context, without navigating through the page structure, so copy changes can be made and reviewed quickly.',
                'workflow' => [
                    '1. Choose the site: select the website whose content you are managing.',
                    '2. Edit in context: change content directly where it appears on the page.',
                    '3. Save changes: confirm the edit, then publish the affected pages.',
                    '4. Review: check the updated pages as a visitor would see them.',
                ],
                'details' => [
                    'Publishing Discipline' => 'Content edits do not go live until the affected page is published. Confirm publication rather than assuming it.',
                    'Relationship' => 'This is the fast path for copy changes. Structural work, slugs and navigation belong on the Pages page.',
                ],
            ],
            // =========================================================
            // ACADEMICS: REMAINING CATEGORY HUBS
            // =========================================================
            'App\Filament\App\Pages\Academic\ProgressionHub' => [
                'title' => 'Progression',
                'summary' => 'Category shortcut for moving students onward: the promotion runs that carry a class cohort to the next level, and the screening runs that check who qualifies.',
                'workflow' => [
                    '1. Open Promotion Runs to create and execute the promotion of a class cohort into the next class or form.',
                    '2. Open Screening Runs to assess candidates against promotion criteria such as results, conduct and attendance.',
                    '3. Resolve exceptions before promoting: exclude repeat candidates, resolve ties or subject failures, and confirm guardian consent.',
                    '4. Execute and review: once the run completes, verify the new class allocations before the cohort starts the new level.',
                ],
                'details' => [
                    'Category Shortcut' => 'This page forwards you to the first page in the Progression category, or back to the last page you used there. Follow the category tabs for the full set.',
                    'Order Matters' => 'Screen before promoting. Promoting first removes the ability to compare candidates against the criteria.',
                ],
            ],
            'App\Filament\App\Pages\Academic\SetupStructureHub' => [
                'title' => 'Setup & Structure',
                'summary' => 'Category shortcut for the academic skeleton: levels or forms, subjects, classrooms and academic years.',
                'workflow' => [
                    '1. Define the levels: in Level, create the forms or courses the school operates, in ascending order.',
                    '2. Define the subjects: in Subjects, register the curriculum subjects and map them to the levels that study them.',
                    '3. Place the rooms: in Classrooms, record the teaching spaces available and any capacity limits.',
                    '4. Open the year: in Academic Years, create the year and its terms, because timetables, examinations and fee structures all key off them.',
                    '5. Verify: confirm the structure is complete before enrolling students or building timetables.',
                ],
                'details' => [
                    'Everything Depends On This' => 'Enrollments, timetables, examinations and fees all reference levels, subjects, classrooms and academic years. Incomplete structure causes errors much later.',
                    'Category Shortcut' => 'This page forwards you to the first page in the Setup & Structure category, or back to the last page you used there. Follow the category tabs for the full set.',
                ],
            ],

            // =========================================================
            // BOARDING & WELFARE: WELFARE & CARE
            // =========================================================
            'App\Filament\App\Pages\Boarding\WelfareHub' => [
                'title' => 'Welfare & Care',
                'summary' => 'Category shortcut for the daily supervision of boarders: gate out-passes, hostel roll calls and room inspections.',
                'workflow' => [
                    '1. Control movement: in Out Passes, raise parental approvals, verify the OTP and confirm warden exit for students leaving the hostel.',
                    '2. Confirm presence: in Attendance, run the morning, evening and curfew roll calls per hostel.',
                    '3. Maintain standards: in Inspections, score rooms on cleanliness, orderliness and inventory condition, and record the outcome.',
                    '4. Review: compare roll call results with outstanding out-passes, so a student cannot be off-site and recorded present at once.',
                ],
                'details' => [
                    'Safeguarding Purpose' => 'These three records are the audit trail for the safety of boarders. Gaps in them are what surface in an inspection or an incident enquiry.',
                    'Category Shortcut' => 'This page forwards you to the first page in the Welfare & Care category, or back to the last page you used there.',
                ],
            ],
            HostelOutPassResource::class => [
                'title' => 'Out Passes',
                'summary' => 'Permission for boarders to leave the hostel: the request, the parental approval, the OTP verification and the warden exit confirmation.',
                'workflow' => [
                    '1. Raise the request: select the student and hostel, choose the out-pass type (weekend leave, home visit, medical check or emergency), and give the reason.',
                    '2. Set the window: record the expected departure and expected return times, so absence beyond the approved window is detectable.',
                    '3. Request parental approval: send the approval request and wait for the guardian response before the student leaves.',
                    '4. Verify the OTP: confirm the one-time code against the guardian on the phone. The OTP is what prevents a student leaving under a guardian name.',
                    '5. Confirm the exit: record the warden exit confirmation, which closes the loop with the gate and roll call.',
                    '6. Monitor: filter by status to see which requests are pending approval, pending OTP or already cleared.',
                ],
                'details' => [
                    'OTP Is The Control' => 'The OTP check exists because students leave under a parent name. Never clear an out-pass on the strength of a verbal message alone.',
                    'Expected Return' => 'An expected return time turns an unreturned boarder into a visible exception rather than an overnight mystery.',
                    'Emergency Passes' => 'Emergency and medical passes still require the exit record. Speed the approval, not the audit trail.',
                ],
            ],
            HostelAttendanceResource::class => [
                'title' => 'Hostel Attendance',
                'summary' => 'Boarder roll calls for the morning, evening and curfew, captured per hostel so presence is evidenced at each checkpoint.',
                'workflow' => [
                    '1. Choose the hostel: select the hostel whose boarders you are marking, then the date and the roll-call type.',
                    '2. Load learners: use Load learners from selected hostel to list only that hostel boarders with their building and room numbers.',
                    '3. Mark presence: mark each learner present or absent and add a remark where the absence needs explanation.',
                    '4. Submit: save the roll call, which becomes the attendance record for that checkpoint.',
                    '5. Reconcile: compare the roll call against outstanding out-passes, since a student on an out-pass should not also be present.',
                ],
                'details' => [
                    'Three Checkpoints' => 'Morning, evening and curfew roll calls exist because a student verified at evening roll call and missing at curfew is the pattern that matters for safeguarding.',
                    'Building Context' => 'The list carries building and room numbers, so a warden can physically verify a mark rather than trusting a name.',
                    'Remarks' => 'A bare absence mark is not actionable. Record why, because the explanation is what later reviews rely on.',
                ],
            ],
            HostelInspectionResource::class => [
                'title' => 'Hostel Inspections',
                'summary' => 'Room inspections scored on cleanliness, orderliness and inventory condition, with a pass or fail outcome and inspector notes.',
                'workflow' => [
                    '1. Select the room: choose the hostel room being inspected.',
                    '2. Score it: rate cleanliness, orderliness and inventory status on their scores, which is what the outcome is calculated from.',
                    '3. Record the outcome: set whether the room passes inspection, and name the inspector.',
                    '4. Note findings: write what failed and what must be corrected, so the next inspection can verify the correction.',
                    '5. Trend: compare inspections over time for the same room to see whether standards are improving or slipping.',
                ],
                'details' => [
                    'Scored, Not Judgemental' => 'Numeric scores make rooms comparable across weeks and hostels, which matters when standards need defending.',
                    'Notes Carry The Detail' => 'The score says how bad it was; the notes say what to fix. Without notes, a failed inspection produces no action.',
                ],
            ],

            // =========================================================
            // REPORTS & INTELLIGENCE
            // =========================================================
            'App\Filament\App\Pages\ReportingDashboard' => [
                'title' => 'Reports & Analytics Center',
                'summary' => 'The reporting overview: report volumes and success rates, your pinned layouts and favourite templates, active schedules, and recent generation runs.',
                'workflow' => [
                    '1. Compile a snapshot: use Compile Snapshot or click a card to refresh the figures against live data.',
                    '2. Start work: choose New Generation Run to build a report, or Launch Custom Creator for the full report builder.',
                    '3. Reuse: open Saved Layout Presets or Most Used Templates, then Compile and Download.',
                    '4. Automate: review Recurring Automated Jobs to see scheduled deliveries and where automated PDFs are being emailed.',
                    '5. Investigate: check Recent Generation Logs for failed or stalled runs before assuming a report is missing.',
                ],
                'details' => [
                    'Check The Success Rate' => 'A falling success rate usually means a report definition depends on data that has changed shape. The logs identify which runs failed and why.',
                    'Window Matters' => 'Charts default to the last 14 days. Switch to All Time before drawing conclusions about long-term trends.',
                    'Compiled On Demand' => 'Figures are snapshots compiled when requested, so a card can lag behind data entered a moment ago.',
                ],
            ],
            'App\Filament\App\Pages\Reports\ReportsHub' => [
                'title' => 'Reports',
                'summary' => 'Category shortcut for reporting: the report builder, the archive of generated files and the analytics explorer.',
                'workflow' => [
                    '1. Build a report: open the Report Generator to choose a data source, columns, filters, totals and charts.',
                    '2. Collect outputs: open Generated Reports to download or verify everything you have produced.',
                    '3. Explore live data: open Analytics Explorer to query operational metrics without writing a formal report.',
                    '4. Track usage: return to the Dashboard to see report volumes, formats and how the reports are being used.',
                ],
                'details' => [
                    'Build, Archive, Explore' => 'The category separates the three jobs: the generator produces files, the archive stores them, and the explorer answers ad-hoc questions.',
                    'Category Shortcut' => 'This page forwards you to the first page in the Reports category, or back to the last page you used there.',
                ],
            ],
            'App\Filament\App\Pages\ReportGeneratorPage' => [
                'title' => 'Report Generator',
                'summary' => 'Build a custom report from live school data: pick the data source, choose columns, add filters, totals, charts and branding, then generate the file.',
                'workflow' => [
                    '1. Name and format: enter the report name and choose the output format (PDF, CSV, Excel, JSON, print or rich text).',
                    '2. Choose the data source: select one or more sources. Related sources can be joined from Advanced options once more than one is selected.',
                    '3. Pick columns: tick the fields to include. Fields are labelled by their source, so you always know where a column comes from.',
                    '4. Refine from Advanced options: add joins, filters (field, operator, value and AND or OR logic), row grouping, totals, calculated columns and charts.',
                    '5. Brand it: set the header text, footer text, accent colour and page orientation.',
                    '6. Automate: set a frequency if the report must be delivered on a schedule rather than produced once.',
                    '7. Generate: create the run. You are taken to Generated Reports, where the file is downloaded and can be verified.',
                ],
                'details' => [
                    'Sources First' => 'Columns, joins and filters all follow the selected sources. Choose the closest source first, then narrow it, rather than picking columns across unrelated sources.',
                    'Joins Appear Late' => 'The joins list only offers relationships to already-selected sources, so add sources in order before configuring joins.',
                    'Performance' => 'Wide column selections across many sources produce slow runs. Use filters to restrict the row set rather than exporting everything and trimming later.',
                    'Accuracy' => 'Treat generated output as a draft until verified on the Generated Reports page.',
                ],
            ],
            GeneratedReportResource::class => [
                'title' => 'Generated Reports',
                'summary' => 'The archive of every report run: format, record count, execution time, validation status, and the file itself.',
                'workflow' => [
                    '1. Find the run: filter by report, format, status or date.',
                    '2. Download: use Download to obtain the generated file.',
                    '3. Verify data accuracy: run Verify against the run so the record count is confirmed against the underlying data before the file is circulated.',
                    '4. Investigate failures: open failed runs to see the reason; a Missing Data Source or institutional boundary error means the report definition referenced data outside this tenant.',
                ],
                'details' => [
                    'Record Count' => 'The record count is the quickest sanity check. A report that should cover a class and returns five rows usually means a filter, not a data problem.',
                    'Verify Before Sharing' => 'Verification is what distinguishes a report you can present from one you still need to check.',
                    'Execution Time' => 'Long execution times usually indicate wide column selections across many joined sources.',
                ],
            ],
            'App\Filament\App\Pages\AnalyticsExplorer' => [
                'title' => 'Analytics Explorer',
                'summary' => 'Ad-hoc querying across every operational module: live record counts, a records ledger, statistical summaries and visual plots, without writing a formal report.',
                'workflow' => [
                    '1. Choose a scope: pick a group such as Admissions, Academics, Finance, HR or Platform Settings, then the metric you care about.',
                    '2. Read the figures: the dashboard cards show record counts and metrics for the current tenant.',
                    '3. Explore: the Records Ledger lists matching database rows; refine your keyword when nothing matches.',
                    '4. Analyse: use Statistical Analysis for distributions and Visual Insights Plot for charts on the selected metric.',
                    '5. Escalate: when a question needs a formatted, repeatable output, use Build Full Report to turn it into a proper report definition.',
                ],
                'details' => [
                    'Live, Not Snapshots' => 'Figures here are queried live, so they reflect the current data rather than the last compiled report.',
                    'Tenant Scoped' => 'Queries are confined to this school. Counts that seem low are usually a data-entry gap rather than a query fault.',
                    'When To Build A Report' => 'Use the explorer for questions. Use the report generator when the answer must be produced regularly or shared as a file.',
                ],
            ],

            // =========================================================
            // SYSTEM ADMINISTRATION
            // =========================================================
            'App\Filament\App\Pages\AdministrationDashboard' => [
                'title' => 'Administration Overview',
                'summary' => 'The administration home screen: greeting and term countdown, quick actions, weekly attendance distribution and termly tuition collection status.',
                'workflow' => [
                    '1. Check the term clock: the days remaining counter shows how much of the current term is left.',
                    '2. Scan the indicators: weekly attendance distribution and termly tuition collection status show the two trends that usually need attention first.',
                    '3. Act: use the fast action center to jump straight into System Settings, Roles, Departments and the Compliance Log.',
                ],
                'details' => [
                    'Purpose' => 'This screen is a triage page, not a report. Use Analytics Explorer or Reports when you need the underlying detail.',
                    'Collection Status' => 'Tuition collection status compares invoiced amounts against receipts, which is the fastest indicator of the school cash position for the term.',
                ],
            ],
            'App\Filament\App\Pages\Administration\SystemSettingsHub' => [
                'title' => 'System Settings',
                'summary' => 'Category shortcut for configuration: system settings, email configuration and data export.',
                'workflow' => [
                    '1. Configure the system: open System Settings for branding, modules, communication, portals and integrations.',
                    '2. Configure email: open Email Configuration to set per-category sending details and send test emails.',
                    '3. Export data: open Data Export when a dataset must leave the system as CSV.',
                ],
                'details' => [
                    'Category Shortcut' => 'This page forwards you to the first page in the System Settings category, or back to the last page you used there.',
                    'Change Impact' => 'Settings changes affect the whole school. Record what you changed and why, particularly for module visibility and payment settings.',
                ],
            ],
            'App\Filament\App\Pages\SystemSettingsPage' => [
                'title' => 'System Settings',
                'summary' => 'School-wide configuration in one place: visual design, branding, per-module visibility, invoicing behaviour, payment details, terms and conditions, notifications and integrations.',
                'workflow' => [
                    '1. Brand it: in Design & Branding, upload the school logo and favicon, and tune logo scale, header opacity, background wallpaper, wallpaper opacity and scaling.',
                    '2. Choose the typography: set the system font and review the typography preview before saving.',
                    '3. Control modules: in Core Modules, enable or disable each module, or use Enable All Modules and Disable All Modules in bulk. Each module exposes its own page links here.',
                    '4. Set the footer: control the low-profile footer shown at the bottom of every app page.',
                    '5. Configure payments: under Bank Accounts, choose which finance-owned accounts appear on printed invoices; set Mobile Money / EcoCash details separately.',
                    '6. Control invoicing: in Invoicing & Billing, decide how the invoicing engine turns fee structures into invoices.',
                    '7. Write the terms: customise the Terms of Service and Conditions that users must accept at portal registration.',
                    '8. Tune behaviour: review UI & Experience, Communication & Notifications, Portals & Access and Data & Integrations, then save.',
                ],
                'details' => [
                    'Disabling A Module Hides It' => 'Disable All Modules removes the module from navigation for everyone. It does not delete data, but users lose access until it is re-enabled.',
                    'Page Links Here Are Shortcuts' => 'Each module section links to that module pages. The settings themselves are what you configure; the links are for convenience.',
                    'Payments Split Ownership' => 'Bank accounts are created in Finance and only selected here for display on invoices. Configure them in the right place to avoid two competing sets of details.',
                    'Terms Affect Registration' => 'Changes to Terms and Conditions apply to users registering from that point on. Existing users keep the terms they accepted.',
                ],
            ],
            'App\Filament\App\Pages\EmailConfigurationPage' => [
                'title' => 'Email Configuration',
                'summary' => 'Outbound email settings for each category of school communication: admissions, finance, academic and general communication.',
                'workflow' => [
                    '1. Pick the category: each section covers one type of mail. Admissions sends application confirmations and new-application alerts; Finance sends invoices, receipts and payment reminders; Academic sends report cards, results and academic notices; Communication sends announcements and school-wide messages.',
                    '2. Enable the category: turn sending on for the category. The status badge shows whether the category is currently active.',
                    '3. Enter the SMTP details: host, port, encryption, username, password and from address for the school mail server.',
                    '4. Set the recipient and send a test email for each category before relying on it.',
                    '5. Save: store the configuration, which is written to the same settings used by the Admissions Settings page.',
                ],
                'details' => [
                    'One Source Of Truth' => 'This page and Admissions Settings read and write the same email configuration rows, so the two entry points cannot drift apart.',
                    'Test Per Category' => 'Test each category separately. Working credentials can still fail for one category because a sender address is not permitted for that domain.',
                    'Credentials' => 'Passwords are stored server-side. Do not paste credentials into unrelated notes or tickets.',
                ],
            ],
            'App\Filament\App\Pages\Administration\UserManagementHub' => [
                'title' => 'User Management',
                'summary' => 'Category shortcut for accounts and access: user accounts and the roles that govern what they can do.',
                'workflow' => [
                    '1. Manage accounts: open User Accounts to review registrations, approvals and account status.',
                    '2. Manage access: open Roles to define what each role may do, and clone a built-in role rather than starting from scratch.',
                ],
                'details' => [
                    'Category Shortcut' => 'This page forwards you to the first page in the User Management category, or back to the last page you used there.',
                    'Access Model' => 'Permissions come from the assigned role, plus any extra permissions granted to an individual account. Review both when auditing access.',
                ],
            ],
            UserAccountResource::class => [
                'title' => 'User Accounts',
                'summary' => 'Every account in the school: assigned and requested roles, department membership, inherited permissions, extra permissions and approval status.',
                'workflow' => [
                    '1. Review registrations: filter by status to see accounts awaiting approval, and open a pending account for review.',
                    '2. Assign the role: choose the assigned role, and check the inherited permissions summary to see exactly what that role grants.',
                    '3. Add departments: attach the account to its departments so reports and permissions group correctly.',
                    '4. Grant extras carefully: use Extra Permissions For This Account only for genuine exceptions, and document why.',
                    '5. Resolve conflicts: when an existing account already holds the email, choose how to handle the duplicate rather than creating a second account.',
                    '6. Approve or reject: move the account to approved with the approval timestamp, or reject it with a reason the requester can act on.',
                ],
                'details' => [
                    'Requested Versus Assigned' => 'The requested role records what the applicant asked for; the assigned role records what they actually got. A difference is a deliberate decision that should be understood.',
                    'Extra Permissions Are The Risk' => 'Most access problems come from ad-hoc extra permissions that outlive their purpose. Review them during audits.',
                    'Rejection Reasons' => 'A rejection without a reason generates repeat applications and support queries. Record the specific reason.',
                ],
            ],
            CustomRoleResource::class => [
                'title' => 'Roles',
                'summary' => 'Role definitions that govern system access: what each role may do, which users hold it, and whether it is a system default.',
                'workflow' => [
                    '1. Review existing roles: the list shows name, responsibility description, whether the role is a system default and how many directory users hold it.',
                    '2. Create a role: give it a clear designation name and describe its responsibility, since that description is what staff read when deciding whom to assign.',
                    '3. Set permissions: grant the permissions the role needs, using special privileges sparingly and only where the job genuinely requires them.',
                    '4. Clone rather than rebuild: use Clone on a role close to what you need, then adjust the copy.',
                    '5. Assign: set the role on user accounts, then confirm the assigned user count matches expectations.',
                ],
                'details' => [
                    'System Defaults' => 'System default roles are relied on by the application and should not be edited casually. Clone them and adjust the clone.',
                    'Privilege Creep' => 'Special privileges accumulate. Review them against the role description, and remove any that the description no longer justifies.',
                    'Blast Radius' => 'A role held by many users changes the access of all of them at once. Check the assigned user count before editing permissions.',
                ],
            ],
            DepartmentResource::class => [
                'title' => 'Departments',
                'summary' => 'The organisational structure: departments with their classification, head, budget code and status, plus the permissions each department defaults to.',
                'workflow' => [
                    '1. Create the department: enter the department name and a unique short code, then choose its classification category.',
                    '2. Assign leadership: select the departmental head, or set the head of department once appointments are confirmed.',
                    '3. Financial linkage: enter the financial budget code and budget centre code where the department carries a budget.',
                    '4. Set status: mark the department active or inactive so it appears correctly in staff groupings and reporting.',
                    '5. Define default permissions: set what members of the department can do by default, then refine per person only where necessary.',
                ],
                'details' => [
                    'Codes Matter' => 'Department codes appear on payroll, budget reports and exports. Choose codes that will still make sense in two years.',
                    'Budget Codes Link To Finance' => 'The budget code ties the department to the finance ledger. Incorrect codes misstate departmental spending.',
                    'Defaults Versus Individuals' => 'Department default permissions applied consistently are easier to audit than the same permissions granted one person at a time.',
                ],
            ],
            'App\Filament\App\Pages\Administration\AuditHub' => [
                'title' => 'Audit & Compliance',
                'summary' => 'Category shortcut for oversight: the system audit log of administrative actions, and the compliance records that support it.',
                'workflow' => [
                    '1. Investigate: open Audit Log to review who did what, when, from which IP address and with what outcome.',
                    '2. Filter for the question at hand: narrow by administrator account, operational module, action performed, IP source or time window.',
                    '3. Export for evidence: use Export Filtered CSV or Export Print View to produce a record for external or governance review.',
                ],
                'details' => [
                    'Category Shortcut' => 'This page forwards you to the first page in the Audit & Compliance category, or back to the last page you used there.',
                    'Exports Are Sensitive' => 'Audit exports contain IP addresses and account activity. Share them only with those entitled to see them.',
                ],
            ],
            SystemAuditLogResource::class => [
                'title' => 'Audit Log',
                'summary' => 'Every administrative action recorded with timestamp, administrator, action, module, request IP and outcome, including before and after values.',
                'workflow' => [
                    '1. Filter: narrow by Logged From and Logged Until, and filter by Administrator, Operational Module, IP Source, Action Performed or Outcome State.',
                    '2. Read the entry: the log shows the original database state and the updated changes saved, so the effect of the action is visible.',
                    '3. Search an IP source: use the IP source filter to review all activity from a specific address.',
                    '4. Export: use Export Filtered CSV for analysis, or Export Print View for a signed, formatted document.',
                    '5. Retain: keep exports of significant events, since the log is the evidence in any dispute about who changed what.',
                ],
                'details' => [
                    'Before And After' => 'Entries capture both states, which makes the log the fastest way to establish what a value was before it was changed.',
                    'Successful And Failed' => 'Outcome state matters as much as the action. Repeated failed attempts against one account are a security signal.',
                    'Handles Personal Data' => 'Audit exports contain administrator identities and IP addresses. Treat them as confidential.',
                ],
            ],
            'App\Filament\App\Pages\TenantDataExportPage' => [
                'title' => 'Data Export',
                'summary' => 'Download core school datasets as CSV for analysis, migration or archival, scoped to this school only.',
                'workflow' => [
                    '1. Choose the dataset: student directory, staff directory, billing invoices ledger, student attendance logs, staff daily attendance logs, form and class grade levels, class stream allocations, academic curriculum subjects, clinic outpatient visits, library books registry or inventory stock catalog.',
                    '2. Compile and download: the file is streamed as CSV, so large datasets such as attendance logs export without exhausting memory.',
                    '3. Check the file: columns are exported with readable headers, but values remain exactly as stored.',
                    '4. Store securely: exports contain personal data including health and attendance records. Handle them as confidential documents.',
                ],
                'details' => [
                    'Tenant Scoped' => 'Only rows belonging to this school are exported. There is no cross-school option here by design.',
                    'Streaming' => 'Rows are streamed lazily, so very large tables export reliably. This also means a cancelled download yields a truncated file.',
                    'For Analysis Or Archive' => 'For formatted, repeatable reports use the Report Generator instead. This page is for raw data.',
                ],
            ],

            // =========================================================
            // SUBSCRIPTION & BILLING
            // =========================================================
            'App\Filament\App\Pages\SaaSBillingOverview' => [
                'title' => 'Billing Overview',
                'summary' => 'The school account billing position: active licensing invoices, the prepaid credit balance, official payment receipts, bank settlement audits and the two ways to pay.',
                'workflow' => [
                    '1. Review the position: check the Access Status, Billing Amount and Prepaid Credit Balance to see what is owed and what credit is available.',
                    '2. Choose a payment option. Pay Online via Paynow sends the school to secure checkout for the selected target invoice. Manual Bank Settlement records a deposit you have made directly.',
                    '3. Online payment: select the target invoice, confirm the checkout currency, and proceed to Paynow secure checkout.',
                    '4. Manual settlement: upload the deposit slip or enter the transaction reference, amount paid, payment date and source bank, then submit the deposit slip.',
                    '5. Collect evidence: download the invoice PDF for the invoice and the receipt PDF for settled payments, using the platform banking details as the reference for transfers.',
                    '6. Reconcile: review bank settlement audits to confirm manual deposits have been matched and credited.',
                ],
                'details' => [
                    'Two Paths, One Ledger' => 'Both payment routes end in the same place. Use Paynow for immediacy and manual settlement where bank transfer is the established practice, and always keep the reference.',
                    'Prepaid Credit' => 'Credit already paid reduces what is due. Check the balance before generating a new invoice to avoid paying twice.',
                    'Keep Receipts' => 'Downloaded invoice and receipt PDFs are your proof. Keep them with the school financial records.',
                    'Settlement Audits' => 'Unmatched deposits delay access. Reconcile promptly after any manual transfer.',
                ],
            ],
            'App\Filament\App\Pages\SaaS\SaaSHub' => [
                'title' => 'Subscription & Billing',
                'summary' => 'Category shortcut for the school subscription: the billing overview with invoices and payments, and your current subscription record.',
                'workflow' => [
                    '1. Review billing: open Overview & Billing to see invoices, receipts, credit balance and to make a payment.',
                    '2. Check the plan: open My Subscription to confirm the plan name, billing period, status and renewal date.',
                    '3. Renew early: use Go to Billing Portal on the subscription record when the renewal date approaches, rather than allowing the plan to lapse.',
                ],
                'details' => [
                    'Category Shortcut' => 'This page forwards you to the first page in the Subscription & Billing category, or back to the last page you used there.',
                    'Watch The Renewal Date' => 'Access depends on an active subscription. Renewing before the expiry date avoids any interruption for the whole school.',
                ],
            ],
            SaaSMySubscriptionResource::class => [
                'title' => 'My Subscription',
                'summary' => 'The school current plan: plan name, billing period, status and the renewal or expiry date, with a link to the billing portal.',
                'workflow' => [
                    '1. Confirm the plan: check the plan name and billing period shown for this school.',
                    '2. Check the status: prepaid, active or unpaid status indicates whether the school is currently in good standing.',
                    '3. Note the renewal date: the renewal expiration date tells you when the current term of service ends.',
                    '4. Manage: use Go to Billing Portal when you need to change plan, update payment details or renew.',
                ],
                'details' => [
                    'Single Source' => 'This record reflects the live subscription state for the school. If it disagrees with an invoice, the subscription record is authoritative for access.',
                    'Renew Before Expiry' => 'Renewing after expiry can interrupt service for every user, not just administrators.',
                ],
            ],

            // Default fallback
            default => [
                'title' => 'System Guidance & Workflow',
                'summary' => 'This page is part of the Kairo CORE School Management System. Use the toolbar actions and filters to manage records securely.',
                'workflow' => [
                    '1. View & Search: Use the search bar to locate specific records quickly.',
                    '2. Create & Edit: Click primary action buttons to add or update records.',
                    '3. Data Integrity: All changes are audit-logged and protected by role permissions.',
                ],
                'details' => [
                    'Support' => 'Refer to the comprehensive System Documentation manual for deep-dive workflows.',
                ],
            ],
        };
    }
}
