<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\Classroom;
use Modules\Academics\Models\Section;
use Modules\Timetables\Models\TimeSlot;
use Modules\Timetables\Models\TimetableLesson;
use Modules\Timetables\Models\TimetableTemplate;
use Modules\Timetables\Services\TimetableGeneratorService;

class VisualTimetableBuilder extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;
    use ModulePermissionAccess;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.app.pages.visual-timetable-builder';

    // Matches the custom Academics group (housing student directory, classes, subjects, calendars, & builders)
    protected static ?string $navigationGroup = 'Academics';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationLabel(): string
    {
        return 'Visual Timetable Builder';
    }

    public function getHeading(): string
    {
        return 'Visual Timetable Builder';
    }

    public ?array $data = [];

    public ?int $activeFilterClassId = null;

    // Display metadata states
    public array $activeTemplateSummary = [];

    public array $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public array $matrix = [];

    public array $timeSlots = [];

    // Searchable Combobox states
    public string $classSearchQuery = '';

    public bool $isSearchOpen = false;

    // Inline Card Editor Modal States
    public bool $isEditModalOpen = false;

    public ?int $editingLessonId = null;

    public ?int $editSubjectId = null;

    public ?int $editTeacherId = null;

    public ?int $editClassroomId = null;

    public ?string $editColor = '#ffffff';

    public function mount(): void
    {
        $schoolId = app('current_tenant')->id;

        $activeTemplate = TimetableTemplate::where('school_id', $schoolId)->where('is_active', true)->first();

        if ($activeTemplate) {
            $this->form->fill(array_merge($activeTemplate->settings, [
                'template_lifecycle' => 'existing',
                'active_template_id' => $activeTemplate->id,
                'save_strategy' => 'overwrite',
            ]));
        } else {
            $this->form->fill([
                'template_lifecycle' => 'new',
                'start_time' => '08:00',
                'end_time_of_lessons' => '15:30',
                'period_length' => '35',
                'has_fixed_break' => false,
                'break_after_period' => '3',
                'break_duration' => '15',
                'fixed_break_time' => '10:00',
                'has_fixed_lunch' => false,
                'lunch_after_period' => '5',
                'lunch_duration' => '45',
                'fixed_lunch_time' => '12:30',
            ]);
        }

        $firstSection = Section::first();
        $this->activeFilterClassId = $firstSection ? $firstSection->id : null;

        $this->loadTimetableMatrix();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Template Lifecycle Controller')
                    ->schema([
                        Forms\Components\Select::make('template_lifecycle')
                            ->label(__('I want to...'))
                            ->options([
                                'new' => __('Create a brand new schedule template from scratch'),
                                'existing' => __('Load and modify an existing schedule template'),
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state === 'new') {
                                    $set('active_template_id', null);
                                    $set('template_name', '');
                                }
                            }),

                        Forms\Components\Select::make('active_template_id')
                            ->label(__('Select Existing Timetable Template'))
                            ->options(TimetableTemplate::pluck('name', 'id'))
                            ->required(fn (Forms\Get $get) => $get('template_lifecycle') === 'existing')
                            ->visible(fn (Forms\Get $get) => $get('template_lifecycle') === 'existing')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (! $state) {
                                    return;
                                }
                                $template = TimetableTemplate::find($state);
                                if ($template && is_array($template->settings)) {
                                    foreach ($template->settings as $key => $value) {
                                        $set($key, $value);
                                    }
                                    $set('save_strategy', 'overwrite');
                                }
                            }),

                        Forms\Components\Radio::make('save_strategy')
                            ->label(__('Saving Options'))
                            ->options([
                                'overwrite' => __('Save changes directly to this template (Overwrite settings)'),
                                'clone' => __('Save changes as a new separate copy (Cloning)'),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('template_lifecycle') === 'existing' && filled($get('active_template_id')))
                            ->live()
                            ->default('overwrite'),

                        Forms\Components\TextInput::make('template_name')
                            ->label(__('Template Name'))
                            ->required(fn (Forms\Get $get) => $get('template_lifecycle') === 'new' || $get('save_strategy') === 'clone')
                            ->visible(fn (Forms\Get $get) => $get('template_lifecycle') === 'new' || $get('save_strategy') === 'clone')
                            ->placeholder(__('e.g., Summer Timetable, Friday Schedule'))
                            ->maxLength(255),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Set as School\'s Active Operational Timetable')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Global Schedule Boundaries')
                    ->schema([
                        Forms\Components\TimePicker::make('start_time')->label(__('First Period Start Time'))->required(),
                        Forms\Components\TimePicker::make('end_time_of_lessons')->label(__('Lessons Closing Time'))->required(),
                        Forms\Components\TextInput::make('period_length')->label(__('Period Duration (Minutes)'))->numeric()->required(),
                    ])->columns(3),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Section::make('Tea Break Settings')
                            ->schema([
                                Forms\Components\Toggle::make('has_fixed_break')
                                    ->label(__('Use Fixed Break Time'))
                                    ->live()
                                    ->default(false),

                                Forms\Components\TimePicker::make('fixed_break_time')
                                    ->label(__('Fixed Break Clock Time'))
                                    ->visible(fn (Forms\Get $get) => $get('has_fixed_break') === true)
                                    ->required(fn (Forms\Get $get) => $get('has_fixed_break') === true),

                                Forms\Components\TextInput::make('break_after_period')
                                    ->label(__('Place Break After Period No.'))
                                    ->numeric()
                                    ->visible(fn (Forms\Get $get) => $get('has_fixed_break') === false)
                                    ->required(fn (Forms\Get $get) => $get('has_fixed_break') === false),

                                Forms\Components\TextInput::make('break_duration')
                                    ->label(__('Break Duration (Minutes)'))
                                    ->numeric()
                                    ->required(),
                            ])->columnSpan(1),

                        Forms\Components\Section::make('Lunch Break Settings')
                            ->schema([
                                Forms\Components\Toggle::make('has_fixed_lunch')
                                    ->label(__('Use Fixed Lunch Time'))
                                    ->live()
                                    ->default(false),

                                Forms\Components\TimePicker::make('fixed_lunch_time')
                                    ->label(__('Fixed Lunch Clock Time'))
                                    ->visible(fn (Forms\Get $get) => $get('has_fixed_lunch') === true)
                                    ->required(fn (Forms\Get $get) => $get('has_fixed_lunch') === true),

                                Forms\Components\TextInput::make('lunch_after_period')
                                    ->label(__('Place Lunch After Period No.'))
                                    ->numeric()
                                    ->visible(fn (Forms\Get $get) => $get('has_fixed_lunch') === false)
                                    ->required(fn (Forms\Get $get) => $get('has_fixed_lunch') === false),

                                Forms\Components\TextInput::make('lunch_duration')
                                    ->label(__('Lunch Duration (Minutes)'))
                                    ->numeric()
                                    ->required(),
                            ])->columnSpan(1),
                    ]),
            ])
            ->statePath('data');
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
            ->get();
    }

    public function selectClass(int $id): void
    {
        $this->activeFilterClassId = $id;
        $this->isSearchOpen = false;
        $this->classSearchQuery = '';
        $this->loadTimetableMatrix();
    }

    public function loadTimetableMatrix(): void
    {
        $schoolId = app('current_tenant')->id;

        $activeTemplate = TimetableTemplate::where('school_id', $schoolId)->where('is_active', true)->first();

        $this->timeSlots = TimeSlot::where('school_id', $schoolId)
            ->where('template_id', $activeTemplate?->id)
            ->orderBy('start_time', 'asc')
            ->get()
            ->toArray();

        $this->matrix = [];

        if ($activeTemplate) {
            $sets = $activeTemplate->settings;
            $breakText = ($sets['has_fixed_break'] ?? false) ? 'Fixed at '.date('H:i', strtotime($sets['fixed_break_time'])) : 'Flexible after Period '.$sets['break_after_period'];
            $lunchText = ($sets['has_fixed_lunch'] ?? false) ? 'Fixed at '.date('H:i', strtotime($sets['fixed_lunch_time'])) : 'Flexible after Period '.$sets['lunch_after_period'];

            $this->activeTemplateSummary = [
                'name' => $activeTemplate->name,
                'hours' => date('H:i', strtotime($sets['start_time'])).' to '.date('H:i', strtotime($sets['end_time_of_lessons'])),
                'break' => $breakText.' (Duration: '.$sets['break_duration'].' mins)',
                'lunch' => $lunchText.' (Duration: '.$sets['lunch_duration'].' mins)',
                'length' => $sets['period_length'].' minutes',
            ];
        } else {
            $this->activeTemplateSummary = [];
        }

        if (! $this->activeFilterClassId || ! $activeTemplate) {
            return;
        }

        foreach ($this->timeSlots as $slot) {
            foreach ($this->days as $day) {
                $lesson = TimetableLesson::where('school_id', $schoolId)
                    ->with(['subject', 'teacher', 'classroom'])
                    ->where('template_id', $activeTemplate->id)
                    ->where('section_id', $this->activeFilterClassId)
                    ->where('time_slot_id', $slot['id'])
                    ->where('day_of_week', $day)
                    ->first();

                $this->matrix[$slot['id']][$day] = $lesson ? [
                    'id' => $lesson->id,
                    'subject' => $lesson->subject->name,
                    'teacher' => $lesson->teacher->name,
                    'room' => $lesson->classroom->name,
                    'is_locked' => $lesson->is_locked,
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

    public function generateSlots(): void
    {
        $formData = $this->form->getRawState();
        $schoolId = app('current_tenant')->id;

        DB::beginTransaction();

        try {
            $template = null;

            if ($formData['template_lifecycle'] === 'new') {
                $exists = TimetableTemplate::where('school_id', $schoolId)
                    ->where('name', $formData['template_name'])
                    ->exists();

                if ($exists) {
                    throw new \Exception("A template named '{$formData['template_name']}' already exists.");
                }

                $template = TimetableTemplate::create([
                    'school_id' => $schoolId,
                    'name' => $formData['template_name'],
                    'is_active' => (bool) $formData['is_active'],
                    'settings' => $formData,
                ]);
            } else {
                $templateId = $formData['active_template_id'];
                $template = TimetableTemplate::findOrFail($templateId);

                if ($formData['save_strategy'] === 'overwrite') {
                    $template->update([
                        'is_active' => (bool) $formData['is_active'],
                        'settings' => $formData,
                    ]);
                } else {
                    $exists = TimetableTemplate::where('school_id', $schoolId)
                        ->where('name', $formData['template_name'])
                        ->exists();

                    if ($exists) {
                        throw new \Exception("A template named '{$formData['template_name']}' already exists.");
                    }

                    $template = TimetableTemplate::create([
                        'school_id' => $schoolId,
                        'name' => $formData['template_name'],
                        'is_active' => (bool) $formData['is_active'],
                        'settings' => $formData,
                    ]);
                }
            }

            $generator = app(TimetableGeneratorService::class);
            $generator->generate($formData, $template->id);

            DB::commit();

            Notification::make()->title(__('Schedules & Templates Saved Successfully!'))->success()->send();
            $this->mount();

        } catch (QueryException $e) {
            DB::rollBack();
            Notification::make()->title(__('Database Exception'))->body('Duplicate template names are restricted.')->danger()->send();
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title(__('Generation Blocked'))->body($e->getMessage())->danger()->send();
        }
    }

    public function deleteTemplate(): void
    {
        $formData = $this->form->getRawState();

        if (empty($formData['active_template_id'])) {
            Notification::make()->title(__('Select a template to delete first.'))->warning()->send();

            return;
        }

        DB::beginTransaction();

        try {
            $templateId = $formData['active_template_id'];
            $template = TimetableTemplate::findOrFail($templateId);

            TimeSlot::where('template_id', $templateId)->update(['template_id' => null]);
            $template->delete();

            DB::commit();

            Notification::make()->title(__('Template Deleted Successfully'))->success()->send();
            $this->mount();

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title(__('Deletion Blocked'))->body($e->getMessage())->danger()->send();
        }
    }

    public function openEditLessonModal(int $lessonId): void
    {
        $lesson = TimetableLesson::findOrFail($lessonId);

        $this->editingLessonId = $lessonId;
        $this->editSubjectId = $lesson->subject_id;
        $this->editTeacherId = $lesson->teacher_id;
        $this->editClassroomId = $lesson->classroom_id;
        $this->editColor = $lesson->color;

        $this->isEditModalOpen = true;
    }

    public function saveLessonEdits(): void
    {
        $schoolId = app('current_tenant')->id;
        $lesson = TimetableLesson::findOrFail($this->editingLessonId);

        // Conflict 1: Teacher Overlap
        $teacherConflict = TimetableLesson::where('school_id', $schoolId)
            ->with(['subject', 'course', 'section'])
            ->where('id', '!=', $this->editingLessonId)
            ->where('template_id', $lesson->template_id)
            ->where('academic_year_id', $lesson->academic_year_id)
            ->where('term_id', $lesson->term_id)
            ->where('time_slot_id', $lesson->time_slot_id)
            ->where('day_of_week', $lesson->day_of_week)
            ->where('teacher_id', $this->editTeacherId)
            ->first();

        if ($teacherConflict) {
            Notification::make()
                ->title(__('Scheduling Conflict Blocked'))
                ->body("Teacher is already scheduled to teach [{$teacherConflict->subject->name}] in class [{$teacherConflict->course->name} {$teacherConflict->section->name}] at this period!")
                ->danger()
                ->send();

            return;
        }

        // Conflict 2: Classroom Overlap
        $roomConflict = TimetableLesson::where('school_id', $schoolId)
            ->with(['course', 'section'])
            ->where('id', '!=', $this->editingLessonId)
            ->where('template_id', $lesson->template_id)
            ->where('academic_year_id', $lesson->academic_year_id)
            ->where('term_id', $lesson->term_id)
            ->where('time_slot_id', $lesson->time_slot_id)
            ->where('day_of_week', $lesson->day_of_week)
            ->where('classroom_id', $this->editClassroomId)
            ->first();

        if ($roomConflict) {
            Notification::make()
                ->title(__('Classroom Double-Booking Blocked'))
                ->body("This room is already occupied by class [{$roomConflict->course->name} {$roomConflict->section->name}] at this period!")
                ->danger()
                ->send();

            return;
        }

        $lesson->update([
            'subject_id' => $this->editSubjectId,
            'teacher_id' => $this->editTeacherId,
            'classroom_id' => $this->editClassroomId,
            'color' => $this->editColor,
        ]);

        Notification::make()->title(__('Lesson Details Updated!'))->success()->send();

        $this->isEditModalOpen = false;
        $this->loadTimetableMatrix();
    }

    public function moveLesson(int $lessonId, int $targetSlotId, string $targetDay): void
    {
        $lesson = TimetableLesson::find($lessonId);
        if ($lesson) {
            $lesson->update([
                'time_slot_id' => $targetSlotId,
                'day_of_week' => $targetDay,
            ]);

            Notification::make()->title(__('Lesson Rescheduled!'))->success()->send();
            $this->loadTimetableMatrix();
        }
    }

    public function deleteLesson(int $lessonId): void
    {
        TimetableLesson::destroy($lessonId);
        Notification::make()->title(__('Lesson Removed'))->success()->send();
        $this->loadTimetableMatrix();
    }
}
