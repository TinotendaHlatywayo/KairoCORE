<?php

namespace App\Support;

class HelpContent
{
    public static function for(string $resourceClass): ?array
    {
        return match ($resourceClass) {
            \App\Filament\App\Resources\AcademicYearResource::class => [
                'title' => 'Academic Years Guide',
                'description' => 'Manage academic years, terms, and set the active operating year.',
                'workflow' => "1. Create a year (e.g., 2026).\n2. Add terms (Term 1, Term 2, Term 3).\n3. Set the active year so enrollments and fees attach correctly.",
                'tips' => 'Only one academic year can be active at a time.',
            ],
            \App\Filament\App\Resources\ClassroomResource::class => [
                'title' => 'Classrooms Guide',
                'description' => 'Define physical rooms, capacities, and building locations.',
                'workflow' => "1. Click New Classroom.\n2. Enter room name, building, and capacity.\n3. Used by the timetable and boarding allocation modules.",
                'tips' => 'Capacity tracking prevents double-booking rooms during exam sessions or hostel allocations.',
            ],
            \App\Filament\App\Resources\SubjectResource::class => [
                'title' => 'Subjects Guide',
                'description' => 'Define the subjects taught across academic levels.',
                'workflow' => "1. Enter subject code and title.\n2. Assign to departments or grading scales.",
                'tips' => 'Subjects must exist before assigning teachers or building timetables.',
            ],
            \App\Filament\App\Resources\TeacherAssignmentResource::class => [
                'title' => 'Teacher Assignments Guide',
                'description' => 'Assign teachers to subjects and classes for the active term.',
                'workflow' => "1. Select employee.\n2. Choose subject and class.\n3. Designate primary form teacher if applicable.",
                'tips' => 'Enables grading access and timetable lesson generation for teachers.',
            ],
            \App\Filament\App\Resources\TimetableLessonResource::class => [
                'title' => 'Timetable Lessons Guide',
                'description' => 'Schedule lessons into time slots and classrooms.',
                'workflow' => "1. Choose day, time slot, and classroom.\n2. Assign teacher and subject.",
                'tips' => 'The system validates room and teacher double-booking automatically.',
            ],
            \App\Filament\App\Resources\AcademicReportResource::class => [
                'title' => 'Academic Reports Guide',
                'description' => 'Generate academic transcripts, report cards, and performance summaries.',
                'workflow' => "1. Select student or class.\n2. Choose term and template.\n3. Export or publish to portal.",
                'tips' => 'Ensure assessment marks are locked and approved before generating final reports.',
            ],
            default => null,
        };
    }
}
