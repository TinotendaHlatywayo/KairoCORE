<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\HomeworkResource;
use Filament\Pages\Page;
use Modules\Academics\Models\AcademicYear;
use Modules\DigitalAssessment\Enums\AssessmentStatus;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\Lms\Models\Homework;
use Modules\Timetables\Models\TimetableLesson;
use Modules\Timetables\Models\TimetableTemplate;
use Modules\Students\Models\Enrollment;

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
            $schoolId = $student->school_id ?? (current_tenant()?->id ?? auth()->user()?->school_id);

            // The student's section is taken from the ACTIVE academic year only,
            // so a learner who moved classes between years only sees the current
            // one.
            $activeYear = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->first();

            $sectionIds = Enrollment::where('student_id', $student->id)
                ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
                ->pluck('section_id')
                ->filter()
                ->unique();

            // The ACTIVE timetable template is the only schedule shown to the
            // learner. Lessons tagged with no academic year / term are the
            // "applies to all years/terms" schedules and are always included.
            $activeTemplate = TimetableTemplate::where('school_id', $schoolId)->where('is_active', true)->first();

            $lessons = TimetableLesson::query()
                ->when($activeTemplate, fn ($q) => $q->where('template_id', $activeTemplate->id))
                ->when($activeYear, function ($q) use ($activeYear) {
                    return $q->where(function ($sub) use ($activeYear) {
                        $sub->whereNull('academic_year_id')->orWhere('academic_year_id', $activeYear->id);
                    });
                })
                ->with(['subject', 'timeSlot', 'teacher', 'classroom'])
                ->whereIn('section_id', $sectionIds)
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
