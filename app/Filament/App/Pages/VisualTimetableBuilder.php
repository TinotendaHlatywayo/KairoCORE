<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Support\TeacherInitials;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Classroom;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\CourseSubject;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;
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

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('Academic Workspace');
    }

    public function getHeading(): string
    {
        return __('Academic Workspace');
    }

    public ?array $data = [];

    public ?int $activeFilterClassId = null;

    // Display metadata states
    public array $activeTemplateSummary = [];

    public array $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public array $matrix = [];

    public array $timeSlots = [];

    // Class | Stream scope toggle (a stream = every class in one Form level)
    public string $viewScope = 'class';

    public ?int $selectedCourseId = null;

    public array $streamMatrix = [];

    // Searchable Combobox states
    public string $classSearchQuery = '';

    public bool $isSearchOpen = false;

    // Add Lesson Modal States
    public bool $isAddModalOpen = false;

    public ?int $addSlotId = null;

    public string $addDay = 'monday';

    public ?int $addSubjectId = null;

    public ?int $addTeacherId = null;

    public ?int $addClassroomId = null;

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
                            ->options(TimetableTemplate::where('school_id', app('current_tenant')->id)->pluck('name', 'id'))
                            ->required(fn (Forms\Get $get) => $get('template_lifecycle') === 'existing')
                            ->visible(fn (Forms\Get $get) => $get('template_lifecycle') === 'existing')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (! $state) {
                                    return;
                                }
                                $template = TimetableTemplate::find($state);
                                if ($template && is_array($template->settings)) {
                                    // Preserve the current template lifecycle and active template
                                    // so loading an existing template doesn't revert the UI state.
                                    $settings = $template->settings;
                                    $settings['template_lifecycle'] = 'existing';
                                    $settings['active_template_id'] = $template->id;

                                    foreach ($settings as $key => $value) {
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

                Forms\Components\Section::make('Automatic Lesson Placement Engine')
                    ->description('Automatically fill all teaching periods using subjects, teachers, and class assignments without manual slot entry. Leaving Academic Year or Term empty applies the schedule to all years / all terms.')
                    ->schema([
                        Forms\Components\Select::make('academic_year_id')
                            ->label(__('Academic Year'))
                            ->options(AcademicYear::where('school_id', app('current_tenant')->id)->orderByDesc('start_date')->pluck('name', 'id'))
                            ->placeholder(__('All academic years'))
                            ->live(),
                        Forms\Components\Select::make('term_id')
                            ->label(__('Term'))
                            ->options(fn (Forms\Get $get) => ($get('academic_year_id') ? Term::where('academic_year_id', $get('academic_year_id')) : Term::where('school_id', app('current_tenant')->id))->orderBy('start_date')->pluck('name', 'id'))
                            ->placeholder(__('All terms'))
                            ->visible(fn (Forms\Get $get) => true)
                            ->live(),

                        Forms\Components\Toggle::make('replace_unlocked')
                            ->label(__('Clear existing unlocked lessons before generating'))
                            ->default(true),

                        Forms\Components\TextInput::make('max_per_subject_per_day')
                            ->label(__('Max periods per subject per class per day'))
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(3),
                    ])->columns(2),

                Forms\Components\Section::make('Double / Triple Period Preferences')
                    ->description(__('Choose which subjects combine consecutive periods into one longer teaching block. Each double lesson counts as 2 periods and each triple as 3. Short slots (≤ 40 min) are automatically paired into doubles when no preference is given, so free slots never clutter the grid.'))
                    ->schema([
                        Forms\Components\Repeater::make('period_patterns')
                            ->label(__('Per-Subject Block Preferences'))
                            ->schema([
                                Forms\Components\Select::make('course_id')
                                    ->label(__('Form / Grade'))
                                    ->options(Course::where('school_id', app('current_tenant')->id)->orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Select::make('subject_id')
                                    ->label(__('Subject'))
                                    ->options(Subject::where('school_id', app('current_tenant')->id)->orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                                Forms\Components\TextInput::make('double_count')
                                    ->label(__('Double Lessons / Week'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->helperText(__('Each placed as 2 consecutive periods = 2 periods.')),
                                Forms\Components\TextInput::make('triple_count')
                                    ->label(__('Triple Lessons / Week'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->helperText(__('Each placed as 3 consecutive periods = 3 periods.')),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->addActionLabel(__('Add Subject Pattern')),
                    ]),
            ])
            ->statePath('data');
    }

    public function getFilteredSections(): Collection
    {
        $schoolId = app('current_tenant')->id;

        // The class combobox is deliberately capped so a school with many
        // streams never renders an overwhelming list; typing narrows it down.
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

    public function selectClass(int $id): void
    {
        $this->activeFilterClassId = $id;
        $this->isSearchOpen = false;
        $this->classSearchQuery = '';
        $this->loadTimetableMatrix();
    }

    public function getAvailableCourses(): Collection
    {
        return Course::where('school_id', app('current_tenant')->id)
            ->orderBy('name')
            ->withCount('sections')
            ->get();
    }

    public function selectScope(string $scope): void
    {
        $this->viewScope = $scope === 'stream' ? 'stream' : 'class';
        $this->isSearchOpen = false;
        $this->classSearchQuery = '';

        if ($this->viewScope === 'stream' && ! $this->selectedCourseId) {
            $this->selectedCourseId = Course::where('school_id', app('current_tenant')->id)->value('id');
        }

        $this->loadTimetableMatrix();
    }

    public function selectStreamCourse(int $courseId): void
    {
        $this->selectedCourseId = $courseId;
        $this->loadTimetableMatrix();
    }

    protected function buildStreamMatrix(int $schoolId, int $templateId): void
    {
        $lessons = TimetableLesson::where('school_id', $schoolId)
            ->with(['section.course', 'subject', 'teacher', 'classroom'])
            ->where('template_id', $templateId)
            ->whereHas('section', function ($query) {
                $query->where('school_id', app('current_tenant')->id)
                    ->where('course_id', $this->selectedCourseId);
            })
            ->get();

        foreach ($lessons as $lesson) {
            $key = $lesson->time_slot_id.'|'.$lesson->day_of_week;

            $this->streamMatrix[$key][] = [
                'lesson_id' => $lesson->id,
                'section_label' => trim(($lesson->section->course->name ?? '').' '.$lesson->section->name),
                'subject' => $lesson->subject->name ?? '',
                'teacher' => $lesson->teacher->name ?? '',
                'teacher_initials' => TeacherInitials::for($lesson->teacher?->name),
                'room' => $lesson->classroom->name ?? '',
                'color_classes' => $this->getSubjectColorClasses($lesson->subject->name ?? ''),
                'is_locked' => (bool) $lesson->is_locked,
            ];
        }

        foreach ($this->streamMatrix as $key => $entries) {
            usort($entries, fn ($a, $b) => strcmp($a['section_label'], $b['section_label']));
            $this->streamMatrix[$key] = $entries;
        }
    }

    public function openAddLessonModal(int $slotId, string $day): void
    {
        $this->addSlotId = $slotId;
        $this->addDay = $day;
        $this->addSubjectId = null;
        $this->addTeacherId = null;
        $this->addClassroomId = null;
        $this->isAddModalOpen = true;
    }

    public function saveNewLesson(): void
    {
        $schoolId = app('current_tenant')->id;

        if (! $this->activeFilterClassId) {
            Notification::make()->title(__('Select a class first.'))->warning()->send();

            return;
        }

        $section = Section::where('school_id', $schoolId)->find($this->activeFilterClassId);

        if (! $section) {
            Notification::make()->title(__('Class not found.'))->danger()->send();

            return;
        }

        if (! $this->addSlotId || ! in_array($this->addDay, $this->days, true)) {
            Notification::make()->title(__('Missing slot or day.'))->danger()->send();

            return;
        }

        if (! $this->addSubjectId || ! $this->addTeacherId) {
            Notification::make()->title(__('Subject and teacher are required.'))->danger()->send();

            return;
        }

        $activeTemplate = TimetableTemplate::where('school_id', $schoolId)->where('is_active', true)->first();

        if (! $activeTemplate) {
            Notification::make()->title(__('No Active Timetable Template'))->body('Activate a template before adding lessons.')->warning()->send();

            return;
        }

        $formData = $this->form->getState();
        $academicYearId = $formData['academic_year_id'] ?? null;
        $termId = $formData['term_id'] ?? null;

        $slot = TimeSlot::where('school_id', $schoolId)
            ->where('template_id', $activeTemplate->id)
            ->find($this->addSlotId);

        if (! $slot) {
            Notification::make()->title(__('Time slot no longer exists.'))->danger()->send();

            return;
        }

        // Conflict 1: Teacher Overlap
        $teacherConflict = TimetableLesson::where('school_id', $schoolId)
            ->with(['subject', 'section.course'])
            ->where('template_id', $activeTemplate->id)
            ->where('time_slot_id', $slot->id)
            ->where('day_of_week', $this->addDay)
            ->where('teacher_id', $this->addTeacherId)
            ->first();

        if ($teacherConflict) {
            Notification::make()
                ->title(__('Scheduling Conflict Blocked'))
                ->body("Teacher is already scheduled to teach [{$teacherConflict->subject->name}] in class [{$teacherConflict->section->course->name} {$teacherConflict->section->name}] at this period!")
                ->danger()
                ->send();

            return;
        }

        // Conflict 2: Classroom Overlap
        if ($this->addClassroomId) {
            $roomConflict = TimetableLesson::where('school_id', $schoolId)
                ->with(['section.course'])
                ->where('template_id', $activeTemplate->id)
                ->where('time_slot_id', $slot->id)
                ->where('day_of_week', $this->addDay)
                ->where('classroom_id', $this->addClassroomId)
                ->first();

            if ($roomConflict) {
                Notification::make()
                    ->title(__('Classroom Double-Booking Blocked'))
                    ->body("This room is already occupied by class [{$roomConflict->section->course->name} {$roomConflict->section->name}] at this period!")
                    ->danger()
                    ->send();

                return;
            }
        }

        TimetableLesson::create([
            'school_id' => $schoolId,
            'template_id' => $activeTemplate->id,
            'section_id' => $section->id,
            'course_id' => $section->course_id,
            'subject_id' => $this->addSubjectId,
            'teacher_id' => $this->addTeacherId,
            'classroom_id' => $this->addClassroomId,
            'time_slot_id' => $slot->id,
            'day_of_week' => $this->addDay,
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'color' => '#ffffff',
            'is_locked' => false,
        ]);

        Notification::make()->title(__('Lesson Added!'))->success()->send();

        $this->isAddModalOpen = false;
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
        $this->streamMatrix = [];

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

        if (! $activeTemplate) {
            return;
        }

        if ($this->viewScope === 'stream' && $this->selectedCourseId) {
            $this->buildStreamMatrix($schoolId, $activeTemplate->id);

            return;
        }

        if (! $this->activeFilterClassId) {
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
                    'teacher' => TeacherInitials::for($lesson->teacher?->name),
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

            $this->applyPeriodPatterns($formData['period_patterns'] ?? []);

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

    public function autoGenerateLessons(): void
    {
        $schoolId = app('current_tenant')->id;
        $activeTemplate = TimetableTemplate::where('school_id', $schoolId)->where('is_active', true)->first();

        if (! $activeTemplate) {
            Notification::make()->title(__('No Active Timetable Template'))->body('Please compile and activate a template first before auto-generating lessons.')->warning()->send();

            return;
        }

        $formData = $this->form->getState();
        $academicYearId = $formData['academic_year_id'] ?? null;
        $termId = $formData['term_id'] ?? null;

        if (blank($academicYearId)) {
            $academicYearId = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->value('id');
        }

        if (blank($termId)) {
            $termId = Term::where('school_id', $schoolId)
                ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
                ->orderBy('start_date')
                ->value('id');
        }

        if (blank($academicYearId) || blank($termId)) {
            Notification::make()->title(__('No Academic Year or Term Found'))->body('Set an active academic year and at least one term before auto-generating lessons.')->warning()->send();

            return;
        }

        DB::beginTransaction();
        try {
            $this->applyPeriodPatterns($formData['period_patterns'] ?? []);

            $service = app(TimetableGeneratorService::class);
            $result = $service->autoPlaceLessons([
                'template_id' => $activeTemplate->id,
                'academic_year_id' => $academicYearId,
                'term_id' => $termId,
                'replace_unlocked' => (bool) ($formData['replace_unlocked'] ?? true),
                'max_per_subject_per_day' => (int) ($formData['max_per_subject_per_day'] ?? 1),
            ]);

            DB::commit();

            $placed = (int) $result['placed'];
            $unplacedCount = count($result['unplaced']);
            $skippedCount = count($result['skipped']);

            if ($placed > 0) {
                $msg = "Successfully placed {$placed} lesson(s) automatically with zero clashes!";
                if ($unplacedCount > 0) {
                    $msg .= " ({$unplacedCount} could not be placed due to tight constraints).";
                } elseif ($skippedCount > 0) {
                    $msg .= " ({$skippedCount} assignment(s) were skipped).";
                }

                Notification::make()->title(__('Timetable Auto-Generated Successfully!'))->body($msg)->success()->send();
                $this->loadTimetableMatrix();

                return;
            }

            $reasons = array_values($result['skipped'] ?: ['No lesson requirements could be derived from the current setup.']);
            if (! empty($result['unplaced'])) {
                $reasons = array_merge($reasons, array_map(fn ($u) => "{$u['label']}: {$u['reason']}", array_slice($result['unplaced'], 0, 6)));
            }

            $body = '0 lessons placed. '.implode(' ', array_map(fn ($r) => '• '.$r, array_slice($reasons, 0, 6)));

            Notification::make()->title(__('No Lessons Could Be Placed'))->body($body)->warning()->send();
            $this->loadTimetableMatrix();

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title(__('Auto-Generation Failed'))->body($e->getMessage())->danger()->send();
        }
    }

    /**
     * Persist the double / triple period preferences chosen on this page onto
     * the matching teacher assignment rows (course_subject) so the placement
     * engine packs those subjects into longer consecutive teaching blocks.
     */
    protected function applyPeriodPatterns(array $patterns): void
    {
        $schoolId = app('current_tenant')->id;

        foreach ($patterns as $pattern) {
            $courseId = (int) ($pattern['course_id'] ?? 0);
            $subjectId = (int) ($pattern['subject_id'] ?? 0);
            $doubleCount = max(0, (int) ($pattern['double_count'] ?? 0));
            $tripleCount = max(0, (int) ($pattern['triple_count'] ?? 0));

            if (! $courseId || ! $subjectId) {
                continue;
            }

            CourseSubject::where('school_id', $schoolId)
                ->where('course_id', $courseId)
                ->where('subject_id', $subjectId)
                ->get(['id', 'double_periods_per_week', 'triple_periods_per_week'])
                ->each(function ($assignment) use ($doubleCount, $tripleCount) {
                    $assignment->timestamps = false;
                    $assignment->update([
                        'double_periods_per_week' => $doubleCount,
                        'triple_periods_per_week' => $tripleCount,
                    ]);
                });
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
        $schoolId = app('current_tenant')->id;

        if (! in_array($targetDay, $this->days, true)) {
            Notification::make()->title(__('Invalid day.'))->danger()->send();

            return;
        }

        $lesson = TimetableLesson::with(['section', 'subject', 'teacher', 'classroom'])
            ->where('school_id', $schoolId)
            ->find($lessonId);

        if (! $lesson) {
            Notification::make()->title(__('Lesson not found.'))->danger()->send();

            return;
        }

        $targetSlot = TimeSlot::where('school_id', $schoolId)
            ->where('template_id', $lesson->template_id)
            ->find($targetSlotId);

        if (! $targetSlot) {
            Notification::make()->title(__('Time slot for this template no longer exists.'))->danger()->send();

            return;
        }

        if ($targetSlot->is_break) {
            Notification::make()->title(__('Cannot drop a lesson on a break.'))->warning()->send();

            return;
        }

        if ((int) $lesson->time_slot_id === (int) $targetSlotId && $lesson->day_of_week === $targetDay) {
            return;
        }

        // Occupied cell → try to swap the two lessons (same class, teachers and
        // rooms must remain clash-free after the exchange).
        $occupant = TimetableLesson::where('school_id', $schoolId)
            ->where('template_id', $lesson->template_id)
            ->where('academic_year_id', $lesson->academic_year_id)
            ->where('term_id', $lesson->term_id)
            ->where('time_slot_id', $targetSlotId)
            ->where('day_of_week', $targetDay)
            ->where('section_id', $lesson->section_id)
            ->where('id', '!=', $lesson->id)
            ->first();

        if ($occupant) {
            $this->attemptSwap($lesson, $occupant);

            return;
        }

        $conflict = $this->firstCellConflict(
            $targetSlotId,
            $targetDay,
            $lesson->section_id,
            $lesson->teacher_id,
            $lesson->classroom_id,
            [$lesson->id]
        );

        if ($conflict) {
            Notification::make()
                ->title(__('Scheduling Conflict Blocked'))
                ->body($conflict)
                ->danger()
                ->send();

            return;
        }

        try {
            $lesson->update([
                'time_slot_id' => $targetSlotId,
                'day_of_week' => $targetDay,
            ]);

            Notification::make()->title(__('Lesson Rescheduled!'))->success()->send();
            $this->loadTimetableMatrix();
        } catch (QueryException $e) {
            Notification::make()
                ->title(__('Reschedule Blocked'))
                ->body('That move clashes with another scheduled lesson.')
                ->danger()
                ->send();
        }
    }

    /**
     * Validate (and perform) a swap between two lessons occupying two cells of
     * the same class, recomputing every hard constraint for both new cells.
     */
    protected function attemptSwap(TimetableLesson $a, TimetableLesson $b): void
    {
        $aConflicts = $this->firstCellConflict(
            $b->time_slot_id,
            $b->day_of_week,
            $a->section_id,
            $a->teacher_id,
            $a->classroom_id,
            [$a->id, $b->id]
        );
        $bConflicts = $this->firstCellConflict(
            $a->time_slot_id,
            $a->day_of_week,
            $b->section_id,
            $b->teacher_id,
            $b->classroom_id,
            [$a->id, $b->id]
        );

        $blocked = $aConflicts ?? $bConflicts;

        if ($blocked) {
            Notification::make()
                ->title(__('Swap Blocked'))
                ->body($blocked)
                ->danger()
                ->send();

            return;
        }

        try {
            DB::transaction(function () use ($a, $b) {
                $a->update(['time_slot_id' => $b->time_slot_id, 'day_of_week' => $b->day_of_week]);
                $b->update(['time_slot_id' => $a->time_slot_id, 'day_of_week' => $a->day_of_week]);
            });

            Notification::make()->title(__('Lessons Swapped!'))->success()->send();
            $this->loadTimetableMatrix();
        } catch (QueryException $e) {
            Notification::make()
                ->title(__('Swap Blocked'))
                ->body('The exchange clashes with another scheduled lesson.')
                ->danger()
                ->send();
        }
    }

    /**
     * Return a human-readable conflict when placing the given lesson into a
     * cell would break a section / teacher / classroom constraint, or null
     * when the cell is free. The excluded ids are the lessons being moved.
     */
    protected function firstCellConflict(
        int $slotId,
        string $day,
        int $sectionId,
        ?int $teacherId,
        ?int $roomId,
        array $excludeIds
    ): ?string {
        $schoolId = app('current_tenant')->id;

        if ($slotId && $day && $sectionId) {
            $conflict = TimetableLesson::where('school_id', $schoolId)
                ->where('time_slot_id', $slotId)
                ->where('day_of_week', $day)
                ->whereNotIn('id', $excludeIds)
                ->where(function ($query) use ($sectionId, $teacherId, $roomId) {
                    $query->where('section_id', $sectionId);
                    if ($teacherId) {
                        $query->orWhere('teacher_id', $teacherId);
                    }
                    if ($roomId) {
                        $query->orWhere('classroom_id', $roomId);
                    }
                })
                ->with(['subject', 'section.course'])
                ->first();

            if ($conflict) {
                if ((int) $conflict->section_id === $sectionId) {
                    return "Class {$conflict->section->course->name} {$conflict->section->name} already has a lesson here.";
                }
                if ($conflict->teacher_id === $teacherId) {
                    return 'This teacher is already booked for ['.$conflict->subject->name.'] in ['.$conflict->section->course->name.' '.$conflict->section->name.'] at this period.';
                }

                return 'This classroom is already occupied by ['.$conflict->section->course->name.' '.$conflict->section->name.'] at this period.';
            }
        }

        return null;
    }

    public function deleteLesson(int $lessonId): void
    {
        TimetableLesson::destroy($lessonId);
        Notification::make()->title(__('Lesson Removed'))->success()->send();
        $this->loadTimetableMatrix();
    }
}
