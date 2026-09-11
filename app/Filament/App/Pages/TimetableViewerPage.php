<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Support\TeacherInitials;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Timetables\Models\TimeSlot;
use Modules\Timetables\Models\TimetableLesson;
use Modules\Timetables\Models\TimetableTemplate;

class TimetableViewerPage extends Page
{
    use ModulePermissionAccess;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static string $view = 'filament.app.pages.timetable-viewer-page';

    protected static ?string $navigationGroup = 'Academics';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('View Timetable');
    }

    public function getHeading(): string
    {
        return __('View Timetable');
    }

    public ?int $activeTemplateId = null;

    public ?int $activeFilterClassId = null;

    public ?int $selectedCourseId = null;

    public string $viewScope = 'class';

    public string $classSearchQuery = '';

    public array $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public array $matrix = [];

    public array $streamMatrix = [];

    public array $timeSlots = [];

    public array $activeTemplateSummary = [];

    public array $templates = [];

    public ?string $classTeacherName = null;

    public function mount(): void
    {
        $schoolId = app('current_tenant')->id;

        $this->templates = TimetableTemplate::where('school_id', $schoolId)
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'is_active' => (bool) $t->is_active,
            ])
            ->all();

        $active = TimetableTemplate::where('school_id', $schoolId)->where('is_active', true)->first();

        $this->activeTemplateId = $active?->id;

        if (! $this->activeTemplateId && $this->templates) {
            $this->activeTemplateId = $this->templates[0]['id'];
        }

        if ($this->activeTemplateId) {
            $this->applyActiveTemplate();
        }

        $firstSection = Section::where('school_id', $schoolId)->first();
        $this->activeFilterClassId = $firstSection?->id;

        $this->loadViewTimeline();
    }

    public function selectTemplate(int $templateId): void
    {
        $this->activeTemplateId = $templateId;
        $this->applyActiveTemplate();
        $this->loadViewTimeline();
    }

    public function makeActive(int $templateId): void
    {
        $schoolId = app('current_tenant')->id;

        TimetableTemplate::where('school_id', $schoolId)->update(['is_active' => false]);
        TimetableTemplate::where('school_id', $schoolId)->where('id', $templateId)->update(['is_active' => true]);

        $this->templates = collect($this->templates)
            ->map(fn ($t) => ['id' => $t['id'], 'name' => $t['name'], 'is_active' => (int) $t['id'] === (int) $templateId])
            ->all();

        $this->activeTemplateId = $templateId;

        Notification::make()->title(__('Template set as the active school timetable.'))->success()->send();

        $this->applyActiveTemplate();
        $this->loadViewTimeline();
    }

    protected function applyActiveTemplate(): void
    {
        $this->timeSlots = TimeSlot::where('school_id', app('current_tenant')->id)
            ->where('template_id', $this->activeTemplateId)
            ->orderBy('start_time', 'asc')
            ->get()
            ->toArray();

        $this->matrix = [];
        $this->streamMatrix = [];
        $this->activeTemplateSummary = [];

        $template = TimetableTemplate::find($this->activeTemplateId);

        if (! $template) {
            return;
        }

        $sets = $template->settings;
        $breakText = ($sets['has_fixed_break'] ?? false) ? 'Fixed at '.date('H:i', strtotime($sets['fixed_break_time'])) : 'Flexible after Period '.$sets['break_after_period'];
        $lunchText = ($sets['has_fixed_lunch'] ?? false) ? 'Fixed at '.date('H:i', strtotime($sets['fixed_lunch_time'])) : 'Flexible after Period '.$sets['lunch_after_period'];

        $this->activeTemplateSummary = [
            'name' => $template->name,
            'hours' => date('H:i', strtotime($sets['start_time'] ?? '08:00')).' to '.date('H:i', strtotime($sets['end_time_of_lessons'] ?? '15:30')),
            'break' => $breakText.' (Duration: '.$sets['break_duration'].' mins)',
            'lunch' => $lunchText.' (Duration: '.$sets['lunch_duration'].' mins)',
            'length' => ($sets['period_length'] ?? 35).' minutes',
        ];
    }

    public function selectScope(string $scope): void
    {
        $this->viewScope = $scope;
        $this->loadViewTimeline();
    }

    public function selectClass(int $id): void
    {
        $this->activeFilterClassId = $id;
        $this->classSearchQuery = '';
        $this->classTeacherName = null;

        $section = Section::with('classTeacher')->find($id);
        if ($section) {
            $this->classTeacherName = $section->classTeacher?->name;
        }

        $this->loadViewTimeline();
    }

    public function selectStreamCourse(int $courseId): void
    {
        $this->selectedCourseId = $courseId;
        $this->loadViewTimeline();
    }

    public function getFilteredSections(): Collection
    {
        $schoolId = app('current_tenant')->id;

        return Section::where('school_id', $schoolId)
            ->with('course')
            ->where(function ($query) {
                $query->where('name', 'like', "%{$this->classSearchQuery}%")
                    ->orWhereHas('course', function ($q) {
                        $q->where('name', 'like', "%{$this->classSearchQuery}%");
                    });
            })
            ->orderBy('name')
            ->limit(5)
            ->get();
    }

    public function getAvailableCourses(): Collection
    {
        return Course::where('school_id', app('current_tenant')->id)
            ->withCount('sections')
            ->get();
    }

    public function loadViewTimeline(): void
    {
        if (! $this->activeTemplateId) {
            return;
        }

        $schoolId = app('current_tenant')->id;

        if ($this->viewScope === 'stream' && $this->selectedCourseId) {
            $this->buildStreamMatrix($schoolId, $this->activeTemplateId, $this->selectedCourseId);

            return;
        }

        if (! $this->activeFilterClassId) {
            return;
        }

        foreach ($this->timeSlots as $slot) {
            foreach ($this->days as $day) {
                $lesson = TimetableLesson::where('school_id', $schoolId)
                    ->with(['subject', 'teacher', 'classroom'])
                    ->where('template_id', $this->activeTemplateId)
                    ->where('section_id', $this->activeFilterClassId)
                    ->where('time_slot_id', $slot['id'])
                    ->where('day_of_week', $day)
                    ->first();

                $this->matrix[$slot['id']][$day] = $lesson ? [
                    'id' => $lesson->id,
                    'subject' => $lesson->subject->name,
                    'teacher' => TeacherInitials::for($lesson->teacher?->name),
                    'room' => $lesson->classroom?->name ?? '—',
                    'color_classes' => $this->getSubjectColorClasses($lesson->subject->name),
                ] : null;
            }
        }
    }

    protected function buildStreamMatrix(int $schoolId, int $templateId, int $courseId): void
    {
        foreach ($this->timeSlots as $slot) {
            foreach ($this->days as $day) {
                $lessons = TimetableLesson::where('school_id', $schoolId)
                    ->with(['subject', 'teacher', 'classroom', 'section.course'])
                    ->where('template_id', $templateId)
                    ->where('course_id', $courseId)
                    ->where('time_slot_id', $slot['id'])
                    ->where('day_of_week', $day)
                    ->get();

                $this->streamMatrix[$slot['id'].'|'.$day] = $lessons->map(fn ($lesson) => [
                    'lesson_id' => $lesson->id,
                    'section_label' => trim(($lesson->section->course->name ?? '').' '.$lesson->section->name),
                    'subject' => $lesson->subject->name,
                    'teacher_initials' => TeacherInitials::for($lesson->teacher?->name),
                    'room' => $lesson->classroom?->name ?? '—',
                    'color_classes' => $this->getSubjectColorClasses($lesson->subject->name),
                ])->all();
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