<?php

namespace App\Support;

class HelpContent
{
    public static function for(string $resourceClass): ?array
    {
        return match ($resourceClass) {
            // ==========================================
            // STUDENTS MODULE
            // ==========================================
            \App\Filament\App\Resources\StudentResource::class => [
                'title' => 'Students Management Guide',
                'description' => 'Comprehensive directory and lifecycle manager for all enrolled learners across the school.',
                'workflow' => "1. **Enrolling Students:** Students are typically admitted via the Admissions module and screened applications. However, you can also register individual students directly using the **New Student** action.\n"
                    . "2. **Student Profile & Tabs:** Clicking a student opens their full 360° record, including personal details, guardian/parent contacts, medical notes, hostel placement, and financial history.\n"
                    . "3. **Class & Stream Assignment:** Every student must be assigned to an active Class/Stream (set up in Academics) to receive timetables, assessment marks, and fee billing.\n"
                    . "4. **Status Management:** Active, Suspended, Expelled, or Graduated statuses control system access and billing automatically.",
                'tips' => 'Students are automatically cross-linked to the Finance module for fee invoicing and the Exams module for report cards. Always ensure correct admission numbers and guardian emails are recorded.',
            ],
            \App\Filament\App\Resources\CardTemplateResource::class => [
                'title' => 'ID Card Designer Guide',
                'description' => 'Design, layout, and activate official student and staff identification cards with QR verification codes.',
                'workflow' => "1. **Create Template:** Click **New Card Template** to start a custom layout.\n"
                    . "2. **Design Configuration:** Set dimensions, upload school crests/logos, configure background styling, and position portrait photos, student name, admission number, and barcode/QR elements.\n"
                    . "3. **Verification Integration:** Every generated ID card embeds a secure verification hash pointing to `/verify-card/{hash}`, allowing authorities to authenticate cards instantly.\n"
                    . "4. **Activation:** Mark the preferred template as active so the student and staff portals generate printable cards using this layout.",
                'tips' => 'Preview your template using a sample student record before batch printing to verify text alignment and photo proportions.',
            ],
            \App\Filament\App\Resources\StudentMedicalRecordResource::class => [
                'title' => 'Student Medical & Health Guide',
                'description' => 'Track learner health histories, allergies, chronic conditions, and clinic visit logs.',
                'workflow' => "1. **Medical Profile:** Link medical records directly to the student profile.\n"
                    . "2. **Allergies & Conditions:** Document critical allergies and emergency contact protocols.\n"
                    . "3. **Clinic Visits:** Record school nurse visits, treatments administered, and medications prescribed.",
                'tips' => 'Critical allergies highlighted here appear on teacher attendance rosters and boarding welfare dashboards to ensure learner safety.',
            ],

            // ==========================================
            // ACADEMICS MODULE
            // ==========================================
            \App\Filament\App\Resources\AcademicYearResource::class => [
                'title' => 'Academic Years & Terms Guide',
                'description' => 'Establish the temporal foundation of the school calendar, defining operating years and terms.',
                'workflow' => "1. **Create Academic Year:** Define the year title (e.g. `2026`).\n"
                    . "2. **Configure Terms:** Add operating terms (Term 1, Term 2, Term 3) with start and end dates.\n"
                    . "3. **Activate Year:** Set the current operating year as active. All fee billing, enrollments, timetables, and grading attach to this active year.",
                'tips' => 'Only one academic year can be active at any given time across your school tenant.',
            ],
            \App\Filament\App\Resources\ClassroomResource::class => [
                'title' => 'Classrooms & Facilities Guide',
                'description' => 'Register physical rooms, lecture halls, and laboratories with capacity limits.',
                'workflow' => "1. **Add Room:** Click **New Classroom**, specifying room name/number, building, and maximum capacity.\n"
                    . "2. **Timetable Integration:** Rooms are assigned to timetable lessons to prevent physical double-booking.\n"
                    . "3. **Boarding Link:** Rooms not used for teaching can be designated as hostel rooms in the Boarding module.",
                'tips' => 'Accurate capacities prevent overcrowding and ensure exam venue compliance.',
            ],
            \App\Filament\App\Resources\SubjectResource::class => [
                'title' => 'Subjects Curriculum Guide',
                'description' => 'Define the institutional curriculum of subjects offered across academic levels.',
                'workflow' => "1. **Create Subject:** Enter subject name, code (e.g. `MATH3`), and department.\n"
                    . "2. **Grading Integration:** Link subjects to appropriate grading scales and assessment weightings.\n"
                    . "3. **Prerequisites:** Subjects defined here are required before teacher assignments and timetable lessons can be scheduled.",
                'tips' => 'Ensure subjects are created before attempting to build term timetables or assign subject specialists.',
            ],
            \App\Filament\App\Resources\TeacherAssignmentResource::class => [
                'title' => 'Teacher Assignments Guide',
                'description' => 'Allocate teaching staff to specific subjects, classes, and form-teacher roles.',
                'workflow' => "1. **Assign Staff:** Select an employee, assign them to a course/class and subject.\n"
                    . "2. **Form Teacher:** Designate class form teachers for pastoral care and attendance tracking.\n"
                    . "3. **Permissions:** Assignments grant teachers grading access for their specific student rosters.",
                'tips' => 'Use the **Assign Subject Specialist** action for bulk multi-class allocations.',
            ],
            \App\Filament\App\Resources\TimetableLessonResource::class => [
                'title' => 'Timetable Lessons Guide',
                'description' => 'Schedule and manage weekly teaching timetables with automated conflict detection.',
                'workflow' => "1. **Time Slots:** Ensure operating time slots and templates are configured.\n"
                    . "2. **Lesson Scheduling:** Place lessons into the weekly matrix specifying day, time slot, room, teacher, and subject.\n"
                    . "3. **Drag & Drop / Swapping:** Use the visual timetable grid to drag or swap lessons between slots.",
                'tips' => 'The system automatically blocks teacher, classroom, and student-class double bookings in real time.',
            ],
            \App\Filament\App\Resources\AcademicReportResource::class => [
                'title' => 'Academic Reports Guide',
                'description' => 'Compile and publish student report cards, transcripts, and cumulative assessment results.',
                'workflow' => "1. **Select Scope:** Choose target class, student, and academic term.\n"
                    . "2. **Template Selection:** Pick an approved report template format.\n"
                    . "3. **Generation & Publishing:** Lock assessment marks, generate PDF reports, and publish them directly to the Student Portal.",
                'tips' => 'Always verify assessment mark approvals in the Exams module prior to generating final report cards.',
            ],
            default => null,
        };
    }
}
