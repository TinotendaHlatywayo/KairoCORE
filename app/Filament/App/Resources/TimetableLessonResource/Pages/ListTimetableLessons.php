<?php

namespace App\Filament\App\Resources\TimetableLessonResource\Pages;

use App\Filament\App\Resources\TimetableLessonResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\Section;
use Modules\Timetables\Models\TimeSlot;
use Modules\Timetables\Models\TimetableLesson;
use Modules\Timetables\Models\TimetableTemplate;
use Modules\Timetables\Services\TimetableGeneratorService;

class ListTimetableLessons extends ListRecords
{
    protected static string $resource = TimetableLessonResource::class;

    protected static string $view = 'filament.app.resources.timetable-lesson-resource.pages.list-timetable-lessons';

    public $activeFilterClassId = null;

    public $activeSchoolTemplateId = null;

    public array $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public array $matrix = [];

    public array $timeSlots = [];

    // Live search states
    public string $classSearchQuery = '';

    public bool $isSearchOpen = false;

    public function mount(): void
    {
        parent::mount();

        $schoolId = app('current_tenant')->id;
        $activeTemplate = TimetableTemplate::where('school_id', $schoolId)->where('is_active', true)->first();
        $this->activeSchoolTemplateId = $activeTemplate ? $activeTemplate->id : null;

        $firstSection = Section::first();
        $this->activeFilterClassId = $firstSection ? $firstSection->id : null;

        $this->loadTimetableMatrix();
    }

    /**
     * Computed-style getter: Filters class streams on the fly as the user types
     */
    public function getFilteredSections(): Collection
    {
        $schoolId = app('current_tenant')->id;

        return Section::where('school_id', $schoolId)
            ->where(function ($query) {
                $query->where('name', 'like', "%{$this->classSearchQuery}%")
                    ->orWhereHas('course', function ($q) {
                        $q->where('name', 'like', "%{$this->classSearchQuery}%");
                    });
            })
            ->get();
    }

    /**
     * Set the selected class and close the search drawer
     */
    public function selectClass(int $id): void
    {
        $this->activeFilterClassId = $id;
        $this->isSearchOpen = false;
        $this->classSearchQuery = ''; // Clear search
        $this->loadTimetableMatrix();
    }

    public function switchActiveTemplate(): void
    {
        if (empty($this->activeSchoolTemplateId)) {
            return;
        }

        $schoolId = app('current_tenant')->id;

        DB::beginTransaction();

        try {
            $template = TimetableTemplate::where('school_id', $schoolId)->findOrFail($this->activeSchoolTemplateId);
            $template->update(['is_active' => true]);

            $generator = app(TimetableGeneratorService::class);
            $generator->generate($template->settings, $template->id);

            DB::commit();

            Notification::make()
                ->title(__('School Schedule Changed Successfully'))
                ->body("Now running '{$template->name}' active time slots.")
                ->success()
                ->send();

            $this->loadTimetableMatrix();

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title(__('Rollover Failed'))->body($e->getMessage())->danger()->send();
        }
    }

    public function loadTimetableMatrix(): void
    {
        $schoolId = app('current_tenant')->id;

        $this->timeSlots = TimeSlot::where('school_id', $schoolId)
            ->orderBy('start_time', 'asc')
            ->get()
            ->toArray();

        $this->matrix = [];

        if (! $this->activeFilterClassId) {
            return;
        }

        foreach ($this->timeSlots as $slot) {
            foreach ($this->days as $day) {
                $lesson = TimetableLesson::where('school_id', $schoolId)
                    ->where('section_id', $this->activeFilterClassId)
                    ->where('time_slot_id', $slot['id'])
                    ->where('day_of_week', $day)
                    ->first();

                $this->matrix[$slot['id']][$day] = $lesson ? [
                    'id' => $lesson->id,
                    'subject' => $lesson->subject->name,
                    'teacher' => $lesson->teacher->name,
                    'room' => $lesson->classroom->name,
                    'color_classes' => $this->getSubjectColorClasses($lesson->subject->name),
                ] : null;
            }
        }
    }

    protected function getSubjectColorClasses(string $subjectName): string
    {
        $subject = strtolower($subjectName);

        if (str_contains($subject, 'math')) {
            return 'bg-indigo-50 text-indigo-800 border-indigo-200 dark:bg-indigo-950/30 dark:text-indigo-300 dark:border-indigo-800/30';
        }
        if (str_contains($subject, 'sci') || str_contains($subject, 'phys') || str_contains($subject, 'chem') || str_contains($subject, 'biol')) {
            return 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:border-emerald-800/30';
        }
        if (str_contains($subject, 'eng') || str_contains($subject, 'shon') || str_contains($subject, 'ndeb')) {
            return 'bg-sky-50 text-sky-800 border-sky-200 dark:bg-sky-950/30 dark:text-sky-300 dark:border-sky-800/30';
        }
        if (str_contains($subject, 'acc') || str_contains($subject, 'bus') || str_contains($subject, 'econ')) {
            return 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-800/30';
        }

        return 'bg-slate-50 text-slate-800 border-slate-200 dark:bg-slate-900/50 dark:text-slate-300 dark:border-slate-800/30';
    }
}
