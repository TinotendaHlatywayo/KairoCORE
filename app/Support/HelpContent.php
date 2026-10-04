<?php

namespace App\Support;

class HelpContent
{
    public static function for(string $pageOrResourceClass): array
    {
        // Normalize class name
        $class = ltrim($pageOrResourceClass, '\\');

        return match ($class) {
            // =========================================================
            // ACADEMICS MODULE
            // =========================================================
            'App\Filament\App\Pages\Academic\AcademicOperationsCenter' => [
                'title' => 'Academic Operations Center',
                'summary' => 'Central control dashboard for managing the school’s academic rhythm, term transitions, and operational health.',
                'workflow' => [
                    '1. **Review Active Term Status:** Check current enrollment counts, active academic year, and term dates at a glance.',
                    '2. **Monitor Teaching Progress:** Track timetable completion rates and staff assignment coverage across levels.',
                    '3. **Operational Actions:** Quick-launch into Setup & Structure, Timetables, or Progression runs directly from summary cards.',
                ],
                'details' => [
                    'How it relates' => 'Serves as the high-level home page for the Academics module, linking directly to Setup & Structure, Timetables, and Student Progression.',
                    'Best Practice' => 'Check this dashboard at the start of every term to ensure all foundational setup steps are complete before students attend classes.',
                ],
            ],
            \App\Filament\App\Resources\AcademicYearResource::class => [
                'title' => 'Academic Years & Terms (Setup & Structure)',
                'summary' => 'Establish the temporal foundation of the school calendar. You can register multiple historical or future years, but exactly one year must be marked as active.',
                'workflow' => [
                    '1. **Step 1 — Register Academic Year:** Click **New Academic Year**, enter the year title (e.g., `2026`), and set start/end dates.',
                    '2. **Step 2 — Configure Terms:** Add operating terms (Term 1, Term 2, Term 3) with precise grading periods.',
                    '3. **Step 3 — Set Active Year:** Mark the current operating year as active. *Crucial:* All student enrollments, attendance records, fee billing, timetables, and exam grading attach automatically to this active year.',
                ],
                'details' => [
                    'Prerequisite Relationship' => 'This is the very **first page** you must configure in Setup & Structure. Without an active academic year, classrooms, subjects, and timetables cannot be linked.',
                    'Cross-Module Impact' => 'When you change the active academic year, the Finance module links fee structures, and the Student module anchors enrollments to that year.',
                ],
            ],
            \App\Filament\App\Resources\ClassroomResource::class => [
                'title' => 'Classrooms & Facilities (Setup & Structure)',
                'summary' => 'Register physical rooms, lecture halls, laboratories, and outdoor spaces with strict capacity limits to prevent overcrowding.',
                'workflow' => [
                    '1. **Step 1 — Add Classroom:** Click **New Classroom** and specify the room name/number (e.g., `Lab-01` or `Room 12`), building block, and maximum student seating capacity.',
                    '2. **Step 2 — Timetable Assignment:** Classrooms created here become available in the Timetable module for lesson scheduling.',
                    '3. **Step 3 — Boarding & Welfare Integration:** Rooms can also be cross-referenced when allocating hostel accommodation.',
                ],
                'details' => [
                    'Relationship' => 'Classrooms relate directly to **Timetables & Teaching**, ensuring no two classes are assigned to the same physical room at the same time.',
                    'Best Practice' => 'Keep capacity numbers accurate; the system uses them during exam seating arrangements and hostel room allocation.',
                ],
            ],
            \App\Filament\App\Resources\SubjectResource::class => [
                'title' => 'Subjects Curriculum (Setup & Structure)',
                'summary' => 'Define the institutional curriculum of subjects offered across the school (e.g., Mathematics, English, Physics, Shona).',
                'workflow' => [
                    '1. **Step 1 — Create Subject:** Enter the subject full name and unique subject code (e.g., `MATH3`, `ENG-SUP`).',
                    '2. **Step 2 — Departmental Grouping:** Associate subjects with appropriate academic departments (e.g., Sciences, Humanities, Languages).',
                    '3. **Step 3 — Grading Scales:** Link subjects to grading scales so assessment marks compute correctly in the Exams module.',
                ],
                'details' => [
                    'Relationship' => 'Subjects must exist before you can assign teachers in **Teacher Assignments** or schedule lessons in **Timetable Lessons**.',
                    'Workflow Flow' => 'Academic Years → Classrooms → **Subjects** → Levels → Teacher Assignments.',
                ],
            ],
            \App\Filament\App\Resources\CourseResource::class => [
                'title' => 'Grade Levels (Setup & Structure)',
                'summary' => 'Define the grade levels or forms offered by your school (e.g., Grade 1, Grade 2, Form 1, Form 4, Lower Sixth).',
                'workflow' => [
                    '1. **Step 1 — Create Level:** Click **New Level**, enter level name, level ranking order, and associate tuition fees if applicable.',
                    '2. **Step 2 — Stream Setup:** Group students into class streams under each level.',
                    '3. **Step 3 — Progression Linking:** Levels determine how students promote from year to year in the Progression module.',
                ],
                'details' => [
                    'Relationship' => 'Levels tie Student enrollments, Fee Structures, and Timetable class streams together into coherent educational cohorts.',
                ],
            ],
            \App\Filament\App\Resources\TeacherAssignmentResource::class => [
                'title' => 'Teacher Assignments (Timetables & Teaching)',
                'summary' => 'Allocate teaching staff to specific subjects, classes, and designate Form Teachers for pastoral care and attendance.',
                'workflow' => [
                    '1. **Select Employee:** Choose a registered staff member from the HR directory.',
                    '2. **Assign Role:** Link the teacher to a specific Subject and Class/Stream.',
                    '3. **Form Teacher Designation:** Check the box if this teacher is the primary Form Teacher for pastoral care and daily attendance tracking.',
                ],
                'details' => [
                    'Relationship' => 'Connects **HR & Payroll** staff records with **Academics** and grants teachers grading permissions for their assigned student rosters.',
                ],
            ],
            \App\Filament\App\Resources\TimetableLessonResource::class => [
                'title' => 'Timetables & Lesson Scheduling',
                'summary' => 'Build and manage weekly teaching schedules with automated conflict detection preventing teacher and room double-booking.',
                'workflow' => [
                    '1. **Configure Time Slots:** Set up daily lesson periods (e.g., Period 1: 08:00 - 08:40).',
                    '2. **Schedule Lessons:** Place lessons into the weekly matrix specifying day, time slot, room, teacher, and subject.',
                    '3. **Drag & Drop / Swapping:** Use the visual timetable grid to drag or swap lessons between slots instantly.',
                ],
                'details' => [
                    'Relationship' => 'Relies entirely on **Classrooms**, **Subjects**, and **Teacher Assignments** being set up first.',
                ],
            ],
            \App\Filament\App\Resources\PromotionRunResource::class => [
                'title' => 'Student Promotion Runs (Progression)',
                'summary' => 'Execute end-of-year batch student promotions, repeating, or graduations based on final exam performance.',
                'workflow' => [
                    '1. **Select Source Year:** Choose the concluding academic year.',
                    '2. **Set Criteria:** Review student pass/fail thresholds and terminal level graduations.',
                    '3. **Execute Run:** Process batch promotions; promoted students automatically transition to the next grade level for the new academic year.',
                ],
                'details' => [
                    'Relationship' => 'Ties **Exams & Grading** performance analytics directly to **Students** enrollment updates for the upcoming academic year.',
                ],
            ],

            // =========================================================
            // STUDENTS MODULE
            // =========================================================
            \App\Filament\App\Resources\StudentResource::class => [
                'title' => 'Student Directory & Profiles',
                'summary' => 'Complete lifecycle manager for all enrolled learners, covering personal details, guardians, medical flags, and billing links.',
                'workflow' => [
                    '1. **Directory View:** Search, filter, and review active students by class, stream, or status.',
                    '2. **360° Profile:** Click any student to inspect fee invoices, academic report cards, hostel placement, and attendance.',
                    '3. **Bulk Import/Export:** Use Excel/CSV tools to import new student rosters with automated error checking and rejected row downloads.',
                ],
                'details' => [
                    'Cross-Module Link' => 'Students registered here are automatically billable in **Finance**, trackable in **Attendance**, and eligible for report cards in **Exams & Grading**.',
                ],
            ],
            \App\Filament\App\Resources\CardTemplateResource::class => [
                'title' => 'ID Card Designer & Templates',
                'summary' => 'Design, layout, and activate official student and staff identification cards complete with secure QR verification hashes.',
                'workflow' => [
                    '1. **Create Template:** Design layouts specifying card dimensions, crests, portrait photo position, and barcode/QR placement.',
                    '2. **Secure Verification:** Every generated card embeds a secure verification URL (`/verify-card/{hash}`) preventing forgery.',
                    '3. **Activation & Printing:** Mark the preferred template active for instant single or bulk PDF card generation.',
                ],
                'details' => [
                    'Relationship' => 'Draws photo and identity data directly from **Students** and **Employees** records.',
                ],
            ],

            // =========================================================
            // ADMISSIONS MODULE
            // =========================================================
            'App\Filament\App\Pages\AdmissionSettingsPage' => [
                'title' => 'Online Admissions & Application Settings',
                'summary' => 'Configure public-facing school application portals, custom entrance forms, and screening workflows.',
                'workflow' => [
                    '1. **Setup Portal:** Configure application opening/closing dates and required applicant documents.',
                    '2. **Review Applications:** Process incoming applications submitted through the public school website.',
                    '3. **Convert to Student:** Approved applications instantly convert into official student profiles with one click.',
                ],
                'details' => [
                    'Relationship' => 'Bridges the public school **Website** cms with the internal **Students** directory.',
                ],
            ],

            // =========================================================
            // FINANCE MODULE
            // =========================================================
            \App\Filament\App\Resources\FeeStructureResource::class => [
                'title' => 'Fee Structures & Billing (Student Billing)',
                'summary' => 'Define termly tuition fees, boarding levies, and miscellaneous charges by grade level or student category.',
                'workflow' => [
                    '1. **Fee Categories:** Create ledger categories (e.g., Tuition, Laboratory, Transport).',
                    '2. **Fee Structures:** Assign itemized amounts to specific grade levels and terms.',
                    '3. **Invoice Generation:** Batch-generate termly student invoices across entire class streams.',
                ],
                'details' => [
                    'Relationship' => ' Directly bills students listed in the **Students** module and populates **Financial Statements**.',
                ],
            ],

            // Default fallback for any other page
            default => [
                'title' => 'System Guidance & Workflow',
                'summary' => 'This page is part of the Kairo CORE School Management System. Use the toolbar actions and filters to manage records securely.',
                'workflow' => [
                    '1. **View & Search:** Use the table search bar to locate specific records quickly.',
                    '2. **Create / Edit:** Click the primary action buttons to add or modify records.',
                    '3. **Data Integrity:** All changes are audit-logged and restricted by role permissions.',
                ],
                'details' => [
                    'Support' => 'Refer to the comprehensive System Documentation manual for deep-dive workflows on this module.',
                ],
            ],
        };
    }
}
