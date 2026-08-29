<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\HomeworkResource;
use Filament\Pages\Page;
use Modules\DigitalAssessment\Enums\AssessmentStatus;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\Lms\Models\Homework;
use Modules\Timetables\Models\TimetableLesson;

class StudentTimetable extends Page
{
    protected static string $view = 'filament.student.pages.student-timetable';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Academics';

    protected static ?string $navigationLabel = 'My Timetable';

    protected static ?string $title = 'My Timetable';

    protected static ?string $slug = 'my-timetable';

    public static array $dayOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public static function getNavigationLabel(): string
    {
        return __('My Timetable');
    }

    protected function getViewData(): array
    {
        $student = HomeworkResource::currentStudent();

        $days = collect();
        $upcomingTests = collect();
        $upcomingTasks = collect();

        if ($student) {
            $sectionIds = $student->enrollments()->pluck('section_id')->filter()->unique();

            $lessons = TimetableLesson::whereIn('section_id', $sectionIds)
                ->with(['subject', 'timeSlot', 'teacher', 'classroom'])
                ->get()
                ->sortBy(function ($lesson) {
                    return array_search($lesson->day_of_week, static::$dayOrder, true);
                });

            $days = collect(static::$dayOrder)->mapWithKeys(function ($day) use ($lessons) {
                return [
                    $day => $lessons->where('day_of_week', $day)->values(),
                ];
            });

            // Tests/tasks set by teachers, surfaced on the timetable.
            $upcomingTests = DigitalAssessment::query()
                ->whereIn('status', [
                    AssessmentStatus::Published,
                    AssessmentStatus::Active,
                ])
                ->where(function ($q) use ($sectionIds) {
                    $q->whereNull('section_id')->orWhereIn('section_id', $sectionIds);
                })
                ->with('subject')
                ->get()
                ->filter(function ($assessment) use ($student) {
                    $hasCompleted = $assessment->attempts()
                        ->where('student_id', $student->id)
                        ->whereIn('status', ['submitted', 'graded', 'published', 'auto_submitted'])
                        ->exists();

                    return ! $hasCompleted;
                })
                ->sortBy(fn ($a) => $a->deadline_at ?? $a->availability_end_at ?? $a->availability_start_at)
                ->take(8);

            $upcomingTasks = Homework::query()
                ->where(function ($q) use ($sectionIds) {
                    $q->whereNull('section_id')->orWhereIn('section_id', $sectionIds);
                })
                ->with('subject')
                ->get()
                ->filter(function ($homework) use ($student) {
                    $submitted = $homework->submissions()
                        ->whereHas('student', fn ($q) => $q->where('id', $student->id))
                        ->exists();

                    return ! $submitted;
                })
                ->sortBy(fn ($h) => $h->due_date)
                ->take(8);
        }

        return [
            'student' => $student,
            'days' => $days,
            'upcomingTests' => $upcomingTests,
            'upcomingTasks' => $upcomingTasks,
        ];
    }
}
