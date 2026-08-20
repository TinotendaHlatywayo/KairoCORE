<?php

namespace App\Filament\App\Resources\TimetableLessonResource\Pages;

use App\Filament\App\Resources\TimetableLessonResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Modules\Timetables\Models\TimetableLesson;
use Modules\Timetables\Models\TimetableTemplate;

class CreateTimetableLesson extends CreateRecord
{
    protected static string $resource = TimetableLessonResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $schoolId = app('current_tenant')->id;

        // Find the school's currently active operational template
        $activeTemplate = TimetableTemplate::where('school_id', $schoolId)->where('is_active', true)->first();
        if ($activeTemplate) {
            $data['template_id'] = $activeTemplate->id;
        }

        // Conflict 1: Teacher Overlap
        $teacherConflict = TimetableLesson::where('school_id', $schoolId)
            ->where('template_id', $data['template_id'] ?? null)
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('term_id', $data['term_id'])
            ->where('time_slot_id', $data['time_slot_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('teacher_id', $data['teacher_id'])
            ->first();

        if ($teacherConflict) {
            throw ValidationException::withMessages([
                'teacher_id' => "Conflict: This teacher is already scheduled to teach [{$teacherConflict->subject->name}] during this period.",
            ]);
        }

        // Conflict 2: Classroom Overlap
        $roomConflict = TimetableLesson::where('school_id', $schoolId)
            ->where('template_id', $data['template_id'] ?? null)
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('term_id', $data['term_id'])
            ->where('time_slot_id', $data['time_slot_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('classroom_id', $data['classroom_id'])
            ->first();

        if ($roomConflict) {
            throw ValidationException::withMessages([
                'classroom_id' => 'Conflict: This classroom is already occupied during this period.',
            ]);
        }

        // Conflict 3: Class Stream Overlap
        $streamConflict = TimetableLesson::where('school_id', $schoolId)
            ->where('template_id', $data['template_id'] ?? null)
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('term_id', $data['term_id'])
            ->where('time_slot_id', $data['time_slot_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('section_id', $data['section_id'])
            ->first();

        if ($streamConflict) {
            throw ValidationException::withMessages([
                'section_id' => "Conflict: This class stream is already scheduled for [{$streamConflict->subject->name}] during this period.",
            ]);
        }

        return $data;
    }
}
