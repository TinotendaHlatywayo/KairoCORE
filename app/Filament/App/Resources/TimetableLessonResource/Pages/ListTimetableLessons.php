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

    public $viewScope = 'school'; // 'school', 'stream', 'class'

    public $selectedCourseId = null;

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

        $this->viewScope = 'school';
        $this->selectedCourseId = null;
        $this->activeFilterClassId = null;

        $this->loadTimetableMatrix();
    }

    /**
     * Computed-style getter: Filters class streams on the fly as the user types
     */
    public function getFilteredSections(): Collection
    {
        $schoolId = app('current_tenant')->id;

        return Section::where('school_id', $schoolId)
            ->when($this->selectedCourseId, fn ($q) => $q->where('course_id', $this->selectedCourseId))
            ->where(function ($query) {
                $query->where('name', 'like', "%{$this->classSearchQuery}%")
                    ->orWhereHas('course', function ($q) {
                        $q->where('name', 'like', "%{$this->classSearchQuery}%");
                    });
            })
            ->get();
    }

    /**
     * Get available courses for stream filter
     */
    public function getAvailableCourses(): Collection
    {
        $schoolId = app('current_tenant')->id;
        return \Modules\Academics\Models\Course::where('school_id', $schoolId)->orderBy('name')->get();
    }

    /**
     * Set the selected class and close the search drawer
     */
    public function selectClass(int $id): void
    {
        $this->viewScope = 'class';
        $this->activeFilterClassId = $id;
        $this->isSearchOpen = false;
        $this->classSearchQuery = ''; // Clear search
        $this->loadTimetableMatrix();
    }

    public function selectScope(string $scope): void
    {
        $this->viewScope = $scope;
        if ($scope !== 'class') {
            $this->activeFilterClassId = null;
        }
        if ($scope !== 'stream') {
            $this->selectedCourseId = null;
        }
        $this->loadTimetableMatrix();
    }

    public function selectCourse(int $id): void
    {
        $this->viewScope = 'stream';
        $this->selectedCourseId = $id;
        $this->activeFilterClassId = null;
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

        // Build the base query based on view scope
        $baseQuery = TimetableLesson::where('school_id', $schoolId);

        if ($this->viewScope === 'class' && $this->activeFilterClassId) {
            $baseQuery->where('section_id', $this->activeFilterClassId);
        } elseif ($this->viewScope === 'stream' && $this->selectedCourseId) {
            $baseQuery->whereHas('section', fn ($q) => $q->where('course_id', $this->selectedCourseId));
        }
        // For 'school' scope, no additional filtering - show all lessons

        // For school/stream scope without class selected, we need to show a merged view
        // or just return empty matrix since we can't show multiple classes in one grid
        if ($this->viewScope !== 'class') {
            // Could show a summary view, but for now return empty grid
            // The user needs to select a specific class to see the detailed matrix
            return;
        }

        if (! $this->activeFilterClassId) {
            return;
        }

        foreach ($this->timeSlots as $slot) {
            foreach ($this->days as $day) {
                $lesson = $baseQuery->where('time_slot_id', $slot['id'])
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

    /**
     * Drag & drop handler for the weekly grid.
     *
     * Drops the dragged lesson onto a target cell: if another lesson of the
     * same class already occupies that cell, the two lessons exchange slots;
     * otherwise the dragged lesson moves alone. Teacher/room double-booking
     * is validated in memory before anything is written.
     */
    public function swapLesson(int $lessonId, int $targetSlotId, string $targetDay): void
    {
        $schoolId = app('current_tenant')->id;
        $targetDay = strtolower(trim($targetDay));

        if (! in_array($targetDay, $this->days, true)) {
            Notification::make()->title(__('Invalid day'))->danger()->send();

            return;
        }

        $source = TimetableLesson::where('school_id', $schoolId)->find($lessonId);
        if (! $source) {
            Notification::make()->title(__('Lesson not found'))->danger()->send();

            return;
        }

        if ((int) $source->time_slot_id === (int) $targetSlotId && $source->day_of_week === $targetDay) {
            return; // dropped on its own cell
        }

        $sourceDay = $source->day_of_week;
        $sourceSlot = (int) $source->time_slot_id;
        $targetKey = $targetDay.'|'.$targetSlotId;
        $sourceKey = $sourceDay.'|'.$sourceSlot;

        // The other lesson occupying the target cell, if the move is a swap.
        $target = TimetableLesson::where('school_id', $schoolId)
            ->where('section_id', $source->section_id)
            ->where('time_slot_id', $targetSlotId)
            ->where('day_of_week', $targetDay)
            ->first();

        // Rebuild busy maps from every OTHER lesson (both moving lessons are
        // excluded so their original cells are free for each other).
        $others = TimetableLesson::where('school_id', $schoolId)
            ->where('id', '!=', $source->id)
            ->when($target, fn ($q) => $q->where('id', '!=', $target->id))
            ->get(['section_id', 'teacher_id', 'classroom_id', 'time_slot_id', 'day_of_week']);

        $teacherBusy = $roomBusy = $sectionBusy = [];
        foreach ($others as $lesson) {
            $key = $lesson->day_of_week.'|'.$lesson->time_slot_id;
            if ($lesson->teacher_id) {
                $teacherBusy[$lesson->teacher_id][$key] = true;
            }
            if ($lesson->classroom_id) {
                $roomBusy[$lesson->classroom_id][$key] = true;
            }
            if ($lesson->section_id) {
                $sectionBusy[$lesson->section_id][$key] = true;
            }
        }

        $reason = $this->swapConflictReason($source, $target, $targetKey, $sourceKey, $teacherBusy, $roomBusy, $sectionBusy);
        if ($reason !== null) {
            Notification::make()
                ->title(__('Swap blocked'))
                ->body($reason)
                ->danger()
                ->send();

            $this->loadTimetableMatrix();

            return;
        }

        DB::beginTransaction();

        try {
            if ($target) {
                // The DB enforces unique (slot, day) for stream/teacher/classroom.
                // A straight swap would collide because the receiving row still
                // occupies the cell we are writing to, so route the source
                // lesson through a guaranteed-free scratch period first.
                $scratch = $this->findFreeSwapScratch($source, $targetKey, $sourceKey, $teacherBusy, $roomBusy, $sectionBusy);

                if ($scratch === null) {
                    DB::rollBack();
                    Notification::make()->title(__('Swap blocked'))->body(__('No free period available to complete the exchange.'))->danger()->send();
                    $this->loadTimetableMatrix();

                    return;
                }

                // 1. Move the source lesson out of the way.
                TimetableLesson::where('id', $source->id)->update([
                    'time_slot_id' => $scratch['slot_id'],
                    'day_of_week' => $scratch['day'],
                ]);

                // 2. Move the target lesson into the (now free) source cell.
                TimetableLesson::where('id', $target->id)->update([
                    'time_slot_id' => $sourceSlot,
                    'day_of_week' => $sourceDay,
                ]);

                // 3. Move the source lesson into the (now free) target cell.
                TimetableLesson::where('id', $source->id)->update([
                    'time_slot_id' => $targetSlotId,
                    'day_of_week' => $targetDay,
                ]);
            } else {
                TimetableLesson::where('id', $source->id)->update([
                    'time_slot_id' => $targetSlotId,
                    'day_of_week' => $targetDay,
                ]);
            }

            DB::commit();

            Notification::make()
                ->title($target ? __('Lessons exchanged') : __('Lesson moved'))
                ->body($target
                    ? __('Both lessons swapped their time slots.')
                    : __('Lesson moved to the new time slot.'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title(__('Swap failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->loadTimetableMatrix();
    }

    /**
     * Pick a (day, slot) cell that the source lesson can be parked in while an
     * exchange is carried out. The cell must be genuinely free for this
     * section/teacher/classroom (per the rebuilt busy maps) and must not be one
     * of the two cells actually being exchanged.
     *
     * @return array{day: string, slot_id: int}|null
     */
    protected function findFreeSwapScratch(
        TimetableLesson $source,
        string $targetKey,
        string $sourceKey,
        array $teacherBusy,
        array $roomBusy,
        array $sectionBusy,
    ): ?array {
        $schoolId = app('current_tenant')->id;

        $slotIds = TimeSlot::where('school_id', $schoolId)
            ->where('template_id', $source->template_id)
            ->orderBy('start_time')
            ->pluck('id')
            ->all();

        if (empty($slotIds)) {
            $slotIds = TimeSlot::where('school_id', $schoolId)->pluck('id')->all();
        }

        foreach ($this->days as $day) {
            foreach ($slotIds as $slotId) {
                $key = $day.'|'.$slotId;

                if (in_array($key, [$sourceKey, $targetKey], true)) {
                    continue;
                }

                if ($source->teacher_id && isset($teacherBusy[$source->teacher_id][$key])) {
                    continue;
                }
                if ($source->classroom_id && isset($roomBusy[$source->classroom_id][$key])) {
                    continue;
                }
                if (isset($sectionBusy[$source->section_id][$key])) {
                    continue;
                }

                return ['day' => $day, 'slot_id' => (int) $slotId];
            }
        }

        return null;
    }

    protected function swapConflictReason(
        TimetableLesson $source,
        ?TimetableLesson $target,
        string $targetKey,
        string $sourceKey,
        array $teacherBusy,
        array $roomBusy,
        array $sectionBusy,
    ): ?string {
        // Source lesson into the target cell.
        if ($source->teacher_id && isset($teacherBusy[$source->teacher_id][$targetKey])) {
            return __('That teacher is already teaching somewhere else at this time.');
        }
        if ($source->classroom_id && isset($roomBusy[$source->classroom_id][$targetKey])) {
            return __('That classroom is already in use at this time.');
        }
        if (isset($sectionBusy[$source->section_id][$targetKey])) {
            return __('This class is already booked at that time.');
        }

        // Target lesson into the vacated cell (swap only).
        if ($target) {
            if ($target->teacher_id && isset($teacherBusy[$target->teacher_id][$sourceKey])) {
                return __('The swapped lesson teacher is busy at the original time.');
            }
            if ($target->classroom_id && isset($roomBusy[$target->classroom_id][$sourceKey])) {
                return __('The swapped lesson classroom is busy at the original time.');
            }
            if (isset($sectionBusy[$target->section_id][$sourceKey])) {
                return __('The swapped lesson class is booked at the original time.');
            }
        }

        return null;
    }
}
