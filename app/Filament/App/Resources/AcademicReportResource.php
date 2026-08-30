<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\AcademicReportResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Academics\Models\AcademicReport;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\ReportTemplate;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Term;
use Modules\Finance\Models\Invoice;
use Modules\Students\Models\Student;

class AcademicReportResource extends Resource
{
    use ModulePermissionAccess;

    public static function getEloquentQuery(): Builder
    {
        // Class column renders section→course; eager load to avoid N+1.
        return parent::getEloquentQuery()->with(['section.course']);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Exams & Grading');
    }

    protected static ?string $model = AcademicReport::class;

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Academic Reports';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        $ratingOptions = [
            'outstanding' => __('Outstanding (10.0)'),
            'excellent' => __('Excellent (8.5)'),
            'very_good' => __('Very Good (7.0)'),
            'good' => __('Good (5.5)'),
            'satisfactory' => __('Satisfactory (4.0)'),
            'needs_improvement' => __('Needs Improvement (2.0)'),
        ];

        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Section::make(__('Class & Student Selection'))
                            ->description(__('Select the class stream first to load enrolled students.'))
                            ->schema([
                                Forms\Components\Select::make('section_id')
                                    ->label(__('Class Stream'))
                                    ->options(function () {
                                        return Section::with('course')->get()->pluck('full_name', 'id');
                                    })
                                    ->required()
                                    ->live()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder(__('Select Class Stream (e.g. Form 2A)...')),

                                Forms\Components\Select::make('student_id')
                                    ->label(__('Student'))
                                    ->options(function (Forms\Get $get) {
                                        $sectionId = $get('section_id');
                                        if (! $sectionId) {
                                            return [];
                                        }

                                        return Student::whereHas('enrollments', fn ($q) => $q->where('section_id', $sectionId))
                                            ->get()
                                            ->pluck('full_name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder(__('Select Student...')),

                                Forms\Components\Select::make('term_id')
                                    ->label(__('Academic Term'))
                                    ->options(function () {
                                        $schoolId = app('current_tenant')->id ?? auth()->user()?->school_id;
                                        $activeYear = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->first();
                                        if (! $activeYear) {
                                            return [];
                                        }

                                        return Term::where('academic_year_id', $activeYear->id)
                                            ->get()
                                            ->mapWithKeys(function ($term) {
                                                return [$term->id => ucwords(strtolower($term->name))];
                                            });
                                    })
                                    ->required()
                                    ->preload()
                                    ->placeholder(__('Select Term...')),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'approved' => __('Approved'),
                                        'draft' => __('Draft'),
                                    ])
                                    ->default('approved')
                                    ->required(),

                                Forms\Components\Textarea::make('teacher_comment')
                                    ->label(__('Class Teacher Remarks'))
                                    ->placeholder(__('Write overall progress observation...'))
                                    ->rows(3),

                                Forms\Components\Textarea::make('unhu_competencies.outstanding_achievements') // FIX: Save inside casted JSON field
                                    ->label(__('Outstanding Achievements'))
                                    ->placeholder(__("★ First Place in National Mathematics Olympiad (Senior Category)\n★ Captain of the School Debating Society"))
                                    ->rows(3)
                                    ->helperText(__('Enter outstanding student accomplishments, separated by new lines.')),

                                Forms\Components\Textarea::make('headmaster_comment')
                                    ->label(__('Headmaster Remarks'))
                                    ->placeholder(__('Principal review notes...'))
                                    ->rows(3),
                            ])->columnSpan(1),

                        Forms\Components\Section::make(__('Heritage-Based Curriculum (HBC) Competencies'))
                            ->description(__('Assess the student\'s values and practical competencies.'))
                            ->schema([
                                Forms\Components\Fieldset::make(__('Top 10 Core Competencies'))
                                    ->schema([
                                        Forms\Components\Select::make('unhu_competencies.respect')->label('1. '.AcademicReport::$competencyLabels['respect'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.honesty')->label('2. '.AcademicReport::$competencyLabels['honesty'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.responsibility')->label('3. '.AcademicReport::$competencyLabels['responsibility'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.discipline')->label('4. '.AcademicReport::$competencyLabels['discipline'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.patriotism')->label('5. '.AcademicReport::$competencyLabels['patriotism'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.cooperation')->label('6. '.AcademicReport::$competencyLabels['cooperation'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.leadership')->label('7. '.AcademicReport::$competencyLabels['leadership'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.critical_thinking')->label('8. '.AcademicReport::$competencyLabels['critical_thinking'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.creativity')->label('9. '.AcademicReport::$competencyLabels['creativity'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.environment')->label('10. '.AcademicReport::$competencyLabels['environment'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                    ])->columns(2),

                                Forms\Components\Section::make(__('Additional 10 Competencies'))
                                    ->description(__('Expand to grade optional competencies.'))
                                    ->collapsed()
                                    ->schema([
                                        Forms\Components\Select::make('unhu_competencies.communication')->label('11. '.AcademicReport::$competencyLabels['communication'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.digital_literacy')->label('12. '.AcademicReport::$competencyLabels['digital_literacy'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.entrepreneurship')->label('13. '.AcademicReport::$competencyLabels['entrepreneurship'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.cultural_appreciation')->label('14. '.AcademicReport::$competencyLabels['cultural_appreciation'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.community_service')->label('15. '.AcademicReport::$competencyLabels['community_service'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.perseverance')->label('16. '.AcademicReport::$competencyLabels['perseverance'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.compassion')->label('17. '.AcademicReport::$competencyLabels['compassion'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.time_management')->label('18. '.AcademicReport::$competencyLabels['time_management'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.self_confidence')->label('19. '.AcademicReport::$competencyLabels['self_confidence'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                        Forms\Components\Select::make('unhu_competencies.adaptability')->label('20. '.AcademicReport::$competencyLabels['adaptability'])->options($ratingOptions)->placeholder(__('Skip / Not Graded')),
                                    ])->columns(2),
                            ])->columnSpan(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label(__('Student'))
                    ->searchable(['first_name', 'last_name', 'admission_number'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('section.name')
                    ->label(__('Class'))
                    ->formatStateUsing(fn ($record) => ($record->section?->course?->name ?? '').' '.($record->section?->name ?? ''))
                    ->sortable(),

                Tables\Columns\TextColumn::make('term.academicYear.name')
                    ->label(__('Year'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('term.name')
                    ->label(__('Term'))
                    ->formatStateUsing(fn ($state) => ucwords(strtolower($state))),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'draft' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('overall_score')
                    ->label(__('Overall'))
                    ->numeric(2)
                    ->badge()
                    ->color(fn ($state) => $state >= 8.5 ? 'success' : ($state >= 4.0 ? 'info' : 'danger'))
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('strength')
                    ->label(__('Strength'))
                    ->wrap()
                    ->html()
                    ->width('220px')
                    ->formatStateUsing(fn ($state) => nl2br(e($state)))
                    ->tooltip(fn ($record) => strip_tags(str_replace('<br />', "\n", $record->strength))),

                Tables\Columns\TextColumn::make('needs_improvement')
                    ->label(__('Needs Improvement'))
                    ->wrap()
                    ->html()
                    ->width('220px')
                    ->formatStateUsing(fn ($state) => nl2br(e($state)))
                    ->tooltip(fn ($record) => strip_tags(str_replace('<br />', "\n", $record->needs_improvement))),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label(__('Teacher'))
                    ->default(__('System Admin')),
            ])
            ->filters([
                Tables\Filters\Filter::make('scope')
                    ->label(__('Report Scope'))
                    ->form([
                        Forms\Components\Select::make('scope_type')
                            ->label(__('Scope'))
                            ->options([
                                'single_class' => __('Single Class Stream'),
                                'whole_stream' => __('Whole Stream (Form Level)'),
                                'whole_school' => __('Whole School'),
                                'fee_paid' => __('Fee Payment Percentage'),
                            ])
                            ->default('single_class')
                            ->live(),

                        Forms\Components\Select::make('section_id')
                            ->label(__('Class Stream'))
                            ->options(fn () => Section::with('course')->get()->pluck('full_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('scope_type') === 'single_class'),

                        Forms\Components\Select::make('course_id')
                            ->label(__('Stream / Form Level'))
                            ->options(fn () => Course::where('school_id', app('current_tenant')->id ?? auth()->user()?->school_id)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('scope_type') === 'whole_stream'),

                        Forms\Components\TextInput::make('min_paid_percentage')
                            ->label(__('Minimum Fees Paid %'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->helperText(__('Only include students who have paid at least this percentage of their total fees.'))
                            ->visible(fn (Forms\Get $get) => $get('scope_type') === 'fee_paid'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $scopeType = $data['scope_type'] ?? 'single_class';

                        return $query
                            ->when(
                                $scopeType === 'single_class' && ! empty($data['section_id']),
                                fn (Builder $q): Builder => $q->where('section_id', $data['section_id']),
                            )
                            ->when(
                                $scopeType === 'whole_stream' && ! empty($data['course_id']),
                                fn (Builder $q): Builder => $q->whereHas('section', fn (Builder $sq) => $sq->where('course_id', $data['course_id'])),
                            )
                            ->when(
                                $scopeType === 'fee_paid' && ! empty($data['min_paid_percentage']),
                                fn (Builder $q): Builder => $q->whereIn('student_id', self::studentsPaidAtLeast((float) $data['min_paid_percentage'])),
                            );
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'approved' => __('Approved'),
                        'draft' => __('Draft'),
                    ]),

                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->label(__('Academic Year'))
                    ->relationship('term.academicYear', 'name')
                    ->options(fn () => AcademicYear::where('school_id', app('current_tenant')->id ?? auth()->user()?->school_id)->pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('term_id')
                    ->label(__('Term'))
                    ->options(function () {
                        $schoolId = app('current_tenant')->id ?? auth()->user()?->school_id;
                        $activeYear = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->first();

                        if (! $activeYear) {
                            return [];
                        }

                        return Term::where('academic_year_id', $activeYear->id)
                            ->get()
                            ->mapWithKeys(function ($term) {
                                return [$term->id => ucwords(strtolower($term->name))];
                            });
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip(__('Edit Report')),

                Tables\Actions\Action::make('printReport')
                    ->label(__('Print Report'))
                    ->icon('heroicon-o-printer')
                    ->iconButton()
                    ->color('info')
                    ->tooltip(__('Download Print-Ready Report Card'))
                    ->url(fn ($record) => route('report.pdf', ['record' => $record->id]))
                    ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip(__('Delete Report')),
            ])
            ->headerActions([
                Tables\Actions\Action::make('generateReports')
                    ->label(__('Generate Reports'))
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->form([
                        Forms\Components\Section::make(__('Report Generation'))
                            ->description(__('Reports are generated from the active report template. Only one template may be active at a time.'))
                            ->schema([
                                Forms\Components\Select::make('scope_type')
                                    ->label(__('Report Scope'))
                                    ->options([
                                        'single_class' => __('Single Class Stream'),
                                        'whole_stream' => __('Whole Stream (Form Level)'),
                                        'whole_school' => __('Whole School'),
                                        'fee_paid' => __('Fee Payment Percentage'),
                                    ])
                                    ->default('single_class')
                                    ->live()
                                    ->required(),

                                Forms\Components\Select::make('section_id')
                                    ->label(__('Class Stream'))
                                    ->options(fn () => Section::with('course')->get()->pluck('full_name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Forms\Get $get) => $get('scope_type') === 'single_class')
                                    ->required(fn (Forms\Get $get) => $get('scope_type') === 'single_class'),

                                Forms\Components\Select::make('course_id')
                                    ->label(__('Stream / Form Level'))
                                    ->options(fn () => Course::where('school_id', app('current_tenant')->id ?? auth()->user()?->school_id)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Forms\Get $get) => $get('scope_type') === 'whole_stream')
                                    ->required(fn (Forms\Get $get) => $get('scope_type') === 'whole_stream'),

                                Forms\Components\TextInput::make('min_paid_percentage')
                                    ->label(__('Minimum Fees Paid %'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->helperText(__('Only generate for students who have paid at least this percentage of total fees.'))
                                    ->visible(fn (Forms\Get $get) => $get('scope_type') === 'fee_paid')
                                    ->required(fn (Forms\Get $get) => $get('scope_type') === 'fee_paid'),

                                Forms\Components\Select::make('term_id')
                                    ->label(__('Academic Term'))
                                    ->options(function () {
                                        $schoolId = app('current_tenant')->id ?? auth()->user()?->school_id;
                                        $activeYear = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->first();
                                        if (! $activeYear) {
                                            return [];
                                        }

                                        return Term::where('academic_year_id', $activeYear->id)
                                            ->get()
                                            ->mapWithKeys(fn ($term) => [$term->id => ucwords(strtolower($term->name))]);
                                    })
                                    ->required()
                                    ->preload(),
                            ])->columns(2),
                    ])
                    ->action(function (array $data): void {
                        $schoolId = app('current_tenant')->id ?? auth()->user()?->school_id;

                        $template = ReportTemplate::where('school_id', $schoolId)
                            ->where('is_active', true)
                            ->first();

                        if (! $template) {
                            Notification::make()
                                ->title(__('No active report template'))
                                ->body(__('Mark exactly one report template as active before generating reports.'))
                                ->danger()
                                ->send();

                            return;
                        }

                        $students = self::resolveScopeStudents($data, $schoolId);

                        if ($students->isEmpty()) {
                            Notification::make()
                                ->title(__('No matching students'))
                                ->body(__('No students matched the selected scope criteria.'))
                                ->warning()
                                ->send();

                            return;
                        }

                        $created = 0;
                        $skipped = 0;

                        foreach ($students as $student) {
                            $enrollment = $student->enrollments()
                                ->where('academic_year_id', $data['term_id'] ? Term::find($data['term_id'])->academic_year_id : null)
                                ->first();

                            $sectionId = $data['scope_type'] === 'single_class'
                                ? $data['section_id']
                                : ($enrollment?->section_id ?? $student->enrollments()->first()?->section_id);

                            $exists = AcademicReport::where('student_id', $student->id)
                                ->where('term_id', $data['term_id'])
                                ->where('section_id', $sectionId)
                                ->exists();

                            if ($exists || ! $sectionId) {
                                $skipped++;

                                continue;
                            }

                            AcademicReport::create([
                                'school_id' => $schoolId,
                                'student_id' => $student->id,
                                'section_id' => $sectionId,
                                'term_id' => $data['term_id'],
                                'status' => 'draft',
                                'unhu_competencies' => [],
                            ]);

                            $created++;
                        }

                        Notification::make()
                            ->title(__('Report generation complete'))
                            ->body(__('Generated')." {$created} ".__('report card(s) using')." '{$template->name}'. {$skipped} ".__('skipped (already exists or missing class).'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('bulkPrintFiltered')
                    ->label(__('Bulk Print Reports'))
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('print_mode')
                            ->label(__('Select Output Type'))
                            ->options([
                                'combined' => __('Single Combined PDF (Best for printing)'),
                                'zip' => __('ZIP Archive (Individual PDF files)'),
                            ])
                            ->default('combined')
                            ->required(),
                    ])
                    ->action(function (array $data, Pages\ListAcademicReports $livewire) {
                        $query = $livewire->getFilteredTableQuery();
                        $ids = $query->pluck('id')->toArray();

                        if (count($ids) === 0) {
                            Notification::make()
                                ->title(__('No Records Found'))
                                ->warning()
                                ->send();

                            return;
                        }

                        $url = route('reports.bulk-pdf', [
                            'ids' => implode(',', $ids),
                            'mode' => $data['print_mode'],
                        ]);

                        $livewire->js("window.open('{$url}', '_blank');");
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printSelected')
                        ->label(__('Print Selected'))
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('print_mode')
                                ->label(__('Select Output Type'))
                                ->options([
                                    'combined' => __('Single Combined PDF (Best for printing)'),
                                    'zip' => __('ZIP Archive (Individual PDF files)'),
                                ])
                                ->default('combined')
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records, Page $livewire) {
                            $ids = $records->pluck('id')->toArray();

                            $url = route('reports.bulk-pdf', [
                                'ids' => implode(',', $ids),
                                'mode' => $data['print_mode'],
                            ]);

                            $livewire->js("window.open('{$url}', '_blank');");
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicReports::route('/'),
            'create' => Pages\CreateAcademicReport::route('/create'),
            'edit' => Pages\EditAcademicReport::route('/{record}/edit'),
        ];
    }

    protected static function resolveScopeStudents(array $data, $schoolId)
    {
        $scopeType = $data['scope_type'] ?? 'single_class';

        return match ($scopeType) {
            'single_class' => Student::where('school_id', $schoolId)
                ->whereHas('enrollments', fn (Builder $q) => $q->where('section_id', $data['section_id']))
                ->get(),

            'whole_stream' => Student::where('school_id', $schoolId)
                ->whereHas('enrollments.section', fn (Builder $q) => $q->where('course_id', $data['course_id']))
                ->get(),

            'whole_school' => Student::where('school_id', $schoolId)->get(),

            'fee_paid' => Student::where('school_id', $schoolId)
                ->whereIn('id', self::studentsPaidAtLeast((float) ($data['min_paid_percentage'] ?? 0)))
                ->get(),

            default => collect(),
        };
    }

    protected static function studentsPaidAtLeast(float $percentage): array
    {
        $schoolId = app('current_tenant')->id ?? auth()->user()?->school_id;

        $rows = Invoice::where('school_id', $schoolId)
            ->selectRaw('student_id, SUM(total_amount) as total, SUM(paid_amount) as paid')
            ->groupBy('student_id')
            ->get();

        return $rows->filter(function ($row) use ($percentage) {
            if ((float) $row->total <= 0) {
                return false;
            }

            return ((float) $row->paid / (float) $row->total) * 100 >= $percentage;
        })->pluck('student_id')->all();
    }
}
