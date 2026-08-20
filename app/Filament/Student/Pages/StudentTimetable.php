<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\HomeworkResource;
use Filament\Pages\Page;
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
        }

        return [
            'student' => $student,
            'days' => $days,
        ];
    }
}
