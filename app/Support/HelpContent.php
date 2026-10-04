<?php

namespace App\Support;

class HelpContent
{
    public static function for(string $pageOrResourceClass): array
    {
        $class = ltrim($pageOrResourceClass, '\\');

        return match ($class) {
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

            // =========================================================
            // STUDENTS MODULE
            // =========================================================
            \App\Filament\App\Resources\StudentResource::class => [
                'title' => 'Student Directory & Profiles',
                'summary' => 'Complete lifecycle manager for all enrolled learners, covering personal details, enrollment streams, guardians, and medical notes.',
                'workflow' => [
                    '1. Directory Search: Use search and filters to locate students by name, admission number, or grade level.',
                    '2. Viewing Profiles: Click View on any student to inspect Student Information, personal data, guardian contacts, photo, and current enrollment details (Academic Year, Form/Grade Level, and Stream/Class).',
                    '3. Editing & Enrollment: Click Edit to update student information, assign or change class streams, or update medical and boarding status.',
                    '4. Bulk Operations: Use the CSV import and export tools to manage large student rosters efficiently.',
                ],
                'details' => [
                    'Cross-Module Link' => 'Students registered here are automatically linked to Finance for invoicing, Attendance tracking, and Exam report cards.',
                ],
            ],
            \App\Filament\App\Resources\CardTemplateResource::class => [
                'title' => 'ID Card Designer & Templates',
                'summary' => 'Design and activate official student and staff identification card layouts with secure QR verification hashes.',
                'workflow' => [
                    '1. Create Template: Design card dimensions, upload school badges, and position student portraits and details.',
                    '2. QR Verification: Each generated card embeds a secure verification link for instant authenticity checks.',
                    '3. Activation: Mark your preferred template as active for single or bulk PDF card printing.',
                ],
                'details' => [
                    'Relationship' => 'Pulls photo and identity data directly from active Student and Employee records.',
                ],
            ],
            \App\Filament\App\Resources\StudentMedicalRecordResource::class => [
                'title' => 'Student Medical & Health Records',
                'summary' => 'Track learner health histories, chronic conditions, emergency allergies, and school clinic visits.',
                'workflow' => [
                    '1. Medical Profiles: Record critical allergies, blood groups, and physician contacts.',
                    '2. Clinic Visits: Log daily nurse visits, treatments administered, and medication dispensed.',
                ],
                'details' => [
                    'Relationship' => 'Alerts teachers and boarding staff to critical student health requirements.',
                ],
            ],

            // =========================================================
            // ACADEMICS MODULE
            // =========================================================
            \App\Filament\App\Resources\AcademicYearResource::class => [
                'title' => 'Academic Years & Terms',
                'summary' => 'Establish the temporal foundation of the school calendar by defining operating years and terms.',
                'workflow' => [
                    '1. Register Year: Create the academic year title (e.g. 2027) and date ranges.',
                    '2. Configure Terms: Add operating terms (Term 1, Term 2, Term 3).',
                    '3. Set Active Year: Mark the current year as active so enrollments, timetables, and billing attach correctly.',
                ],
                'details' => [
                    'Prerequisite' => 'Must be configured first before setting up classes, subjects, or timetables.',
                ],
            ],
            \App\Filament\App\Resources\ClassroomResource::class => [
                'title' => 'Classrooms & Facilities',
                'summary' => 'Register physical rooms, lecture halls, and laboratories with seating capacities.',
                'workflow' => [
                    '1. Add Room: Enter room name, building, and maximum capacity.',
                    '2. Timetable Allocation: Rooms are scheduled in timetables to prevent physical double-booking.',
                ],
                'details' => [
                    'Relationship' => 'Ensures room availability during lesson scheduling and exam seating.',
                ],
            ],
            \App\Filament\App\Resources\SubjectResource::class => [
                'title' => 'Subjects Curriculum',
                'summary' => 'Define the institutional curriculum of subjects offered across grade levels.',
                'workflow' => [
                    '1. Create Subject: Enter subject name, code, and department.',
                    '2. Grading Link: Associate subjects with grading scales for exam computations.',
                ],
                'details' => [
                    'Prerequisite' => 'Must exist before scheduling teacher assignments or timetable lessons.',
                ],
            ],
            \App\Filament\App\Resources\CourseResource::class => [
                'title' => 'Grade Levels (Setup & Structure)',
                'summary' => 'Define grade levels or forms offered by the school (e.g. Grade 4, Form 1, Lower Sixth).',
                'workflow' => [
                    '1. Create Level: Enter level name, rank order, and fee categories.',
                    '2. Stream Setup: Organize students into class streams under each level.',
                ],
                'details' => [
                    'Relationship' => 'Anchors student enrollments and fee billing cohorts.',
                ],
            ],
            \App\Filament\App\Resources\TeacherAssignmentResource::class => [
                'title' => 'Teacher Assignments',
                'summary' => 'Allocate teaching staff to specific subjects, classes, and form-teacher roles.',
                'workflow' => [
                    '1. Select Staff: Choose an employee from the HR directory.',
                    '2. Assign Role: Link teacher to course, class, and subject.',
                ],
                'details' => [
                    'Relationship' => 'Connects HR staff records with Academics and grading permissions.',
                ],
            ],
            \App\Filament\App\Resources\TimetableLessonResource::class => [
                'title' => 'Timetables & Lesson Scheduling',
                'summary' => 'Build and manage weekly teaching schedules with automated conflict detection.',
                'workflow' => [
                    '1. Time Slots: Set up daily lesson periods.',
                    '2. Schedule Lessons: Place lessons into the weekly matrix specifying day, room, teacher, and subject.',
                ],
                'details' => [
                    'Relationship' => 'Relies on classrooms, subjects, and teacher assignments.',
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
