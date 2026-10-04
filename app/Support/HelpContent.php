<?php

namespace App\Support;

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
            \App\Filament\App\Resources\AcademicYearResource::class => [
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
            \App\Filament\App\Resources\ClassroomResource::class => [
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
            \App\Filament\App\Resources\SubjectResource::class => [
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
            \App\Filament\App\Resources\CourseResource::class => [
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
            \App\Filament\App\Resources\TeacherAssignmentResource::class => [
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
            \App\Filament\App\Resources\TimetableLessonResource::class => [
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
            \App\Filament\App\Resources\PromotionRunResource::class => [
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
            \App\Filament\App\Resources\ScreeningRunResource::class => [
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
            \App\Filament\App\Resources\StudentResource::class => [
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
            \App\Filament\App\Resources\CardTemplateResource::class => [
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
            \App\Filament\App\Resources\ApplicationResource::class => [
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
