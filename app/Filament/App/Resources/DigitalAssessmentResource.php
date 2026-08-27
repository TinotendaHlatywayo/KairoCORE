<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\DigitalAssessmentResource\Pages;
use App\Filament\App\Resources\DigitalAssessmentResource\RelationManagers\QuestionsRelationManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\DigitalAssessment\Enums\AssessmentCategory;
use Modules\DigitalAssessment\Enums\AssessmentMode;
use Modules\DigitalAssessment\Enums\AssessmentStatus;
use Modules\DigitalAssessment\Enums\FeedbackMode;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\DigitalAssessment\Services\DigitalAssessmentService;

class DigitalAssessmentResource extends Resource
{
    use ModulePermissionAccess;
    use ModuleAwareActiveNavigation;

    protected static ?string $model = DigitalAssessment::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?int $navigationSort = 62;

    protected static ?string $modelLabel = 'Digital Assessment';

    protected static ?string $pluralModelLabel = 'Digital Assessments';

    public static function getNavigationGroup(): ?string
    {
        return __('Exams & Grading');
    }

    public static function getNavigationLabel(): string
    {
        return __('Digital Assessments');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['subject', 'section.course', 'createdBy'])
            ->latest();
    }

    // ── Form ────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([
            static::detailsSection(),
            static::scopeSection(),
            static::configurationSection(),
            static::behaviorSection(),
            static::availabilitySection(),
        ]);
    }

    protected static function detailsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Assessment Details')
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Mid-Term Mathematics Exam')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(2)
                    ->placeholder('Brief description for internal reference...')
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('instructions')
                    ->label('Instructions to Learners')
                    ->toolbarButtons([
                        'bold', 'italic', 'underline', 'strike',
                        'link', 'orderedList', 'bulletList',
                    ])
                    ->placeholder('Instructions displayed to learners before they start...')
                    ->columnSpanFull(),
            ]);
    }

    protected static function scopeSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Academic Scope')
            ->schema([
                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\Select::make('subject_id')
                            ->label('Subject')
                            ->relationship('subject', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Select Subject...'),

                        Forms\Components\Select::make('section_id')
                            ->label('Class Stream')
                            ->relationship('section', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('All Classes (optional)'),

                        Forms\Components\Select::make('academic_year_id')
                            ->label('Academic Year')
                            ->relationship('academicYear', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Current Year'),

                        Forms\Components\Select::make('term_id')
                            ->label('Term')
                            ->relationship('term', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Current Term'),
                    ]),

                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\Select::make('assessment_type_id')
                            ->label('Assessment Type')
                            ->relationship('assessmentType', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Optional'),

                        Forms\Components\Select::make('difficulty')
                            ->label('Difficulty')
                            ->options([
                                'foundation' => 'Foundation',
                                'developing' => 'Developing',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                                'expert' => 'Expert',
                            ])
                            ->placeholder('Optional'),

                        Forms\Components\Placeholder::make('question_count_display')
                            ->label('Questions Attached')
                            ->content(fn (?DigitalAssessment $record) => $record
                                ? $record->questions()->count() . ' questions'
                                : '0 questions (add after creation)'),

                        Forms\Components\Placeholder::make('total_marks_display')
                            ->label('Calculated Total Marks')
                            ->content(fn (?DigitalAssessment $record) => number_format($record?->getCalculatedTotalMarks() ?? 0, 2)),
                    ]),
            ]);
    }

    protected static function configurationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Assessment Configuration')
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Select::make('assessment_mode')
                            ->label('Mode')
                            ->options(collect(AssessmentMode::cases())
                                ->mapWithKeys(fn ($m) => [$m->value => $m->label()])
                                ->toArray())
                            ->default(AssessmentMode::Standard)
                            ->required(),

                        Forms\Components\Select::make('assessment_category')
                            ->label('Category')
                            ->options(collect(AssessmentCategory::cases())
                                ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                                ->toArray())
                            ->default(AssessmentCategory::Formative)
                            ->required()
                            ->helperText(fn (Forms\Get $get) => ($get('assessment_category') instanceof \BackedEnum ? $get('assessment_category') : AssessmentCategory::tryFrom($get('assessment_category')))?->description() ?? ''),

                        Forms\Components\Toggle::make('contributes_to_grade')
                            ->label('Contributes to Grade')
                            ->default(false),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(480)
                            ->placeholder('e.g., 60'),

                        Forms\Components\TextInput::make('total_marks')
                            ->label('Total Marks')
                            ->numeric()
                            ->step(0.25)
                            ->minValue(0)
                            ->helperText('Auto-calculated from questions. Override if needed.'),

                        Forms\Components\TextInput::make('pass_mark')
                            ->label('Pass Mark (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(50),

                        Forms\Components\TextInput::make('max_attempts')
                            ->label('Max Attempts')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->default(1),

                        Forms\Components\TextInput::make('attempts_allowed')
                            ->label('Attempts Allowed')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->default(1),

                        Forms\Components\Select::make('feedback_mode')
                            ->label('Feedback Mode')
                            ->options(collect(FeedbackMode::cases())
                                ->mapWithKeys(fn ($f) => [$f->value => $f->label()])
                                ->toArray())
                            ->default(FeedbackMode::Delayed),
                    ]),
            ]);
    }

    protected static function behaviorSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Learner Experience')
            ->description('Control what learners see and can do during the assessment.')
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Toggle::make('randomize_questions')
                            ->label('Randomize Questions')
                            ->default(false),

                        Forms\Components\Toggle::make('randomize_options')
                            ->label('Randomize Options')
                            ->default(false),

                        Forms\Components\Toggle::make('shuffle_question_pool')
                            ->label('Shuffle Question Pool')
                            ->default(false),

                        Forms\Components\Toggle::make('show_feedback')
                            ->label('Show Feedback')
                            ->default(true),

                        Forms\Components\Toggle::make('allow_backward_navigation')
                            ->label('Allow Backward Navigation')
                            ->default(true),

                        Forms\Components\Toggle::make('allow_question_skipping')
                            ->label('Allow Question Skipping')
                            ->default(true),

                        Forms\Components\Toggle::make('calculator_enabled')
                            ->label('Calculator Enabled')
                            ->default(false),

                        Forms\Components\Toggle::make('password_protection')
                            ->label('Password Protection')
                            ->default(false),

                        Forms\Components\Toggle::make('anti_cheating_enabled')
                            ->label('Anti-Cheating')
                            ->default(false),

                        Forms\Components\Toggle::make('late_submission_allowed')
                            ->label('Late Submission Allowed')
                            ->default(false),

                        Forms\Components\Toggle::make('auto_submit')
                            ->label('Auto-Submit at Deadline')
                            ->default(true),

                        Forms\Components\Toggle::make('adaptive_mode')
                            ->label('Adaptive Difficulty')
                            ->helperText('Questions adapt based on learner performance')
                            ->default(false)
                            ->live(),
                    ]),

                Forms\Components\Section::make('Adaptive Settings')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('adaptive_base_difficulty')
                                    ->label('Base Difficulty (0-100)')
                                    ->numeric()
                                    ->default(50)
                                    ->minValue(0)
                                    ->maxValue(100),

                                Forms\Components\TextInput::make('adaptive_window_size')
                                    ->label('Window Size (recent responses)')
                                    ->numeric()
                                    ->default(3)
                                    ->minValue(1)
                                    ->maxValue(20),

                                Forms\Components\TextInput::make('adaptive_adjustment_rate')
                                    ->label('Adjustment Rate')
                                    ->numeric()
                                    ->default(10)
                                    ->minValue(1)
                                    ->maxValue(50),
                            ]),
                    ])
                    ->visible(fn (Forms\Get $get) => (bool) $get('adaptive_mode'))
                    ->reactive(),
            ]);
    }

    protected static function availabilitySection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Availability & Scheduling')
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\DateTimePicker::make('availability_start_at')
                            ->label('Available From')
                            ->placeholder('Immediately (optional)')
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('availability_end_at')
                            ->label('Available Until')
                            ->placeholder('No end (optional)')
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('deadline_at')
                            ->label('Submission Deadline')
                            ->placeholder('Optional')
                            ->seconds(false),
                    ]),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(collect(AssessmentStatus::cases())
                        ->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                        ->toArray())
                    ->default(AssessmentStatus::Draft)
                    ->required(),
            ]);
    }

    // ── Table ───────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn (DigitalAssessment $record) => $record->title),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->sortable(),

                Tables\Columns\TextColumn::make('section.name')
                    ->label('Class')
                    ->formatStateUsing(fn ($record) => $record->section
                        ? ($record->section->course?->name ?? '') . ' ' . $record->section->name
                        : 'All Classes')
                    ->placeholder('All'),

                Tables\Columns\TextColumn::make('assessment_mode')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->label() : (AssessmentMode::tryFrom($state)?->label() ?? $state)),

                Tables\Columns\TextColumn::make('assessment_category')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->label() : (AssessmentCategory::tryFrom($state)?->label() ?? $state))
                    ->color(fn ($state) => $state instanceof \BackedEnum ? $state->color() : (AssessmentCategory::tryFrom($state)?->color() ?? 'gray')),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' min' : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_marks')
                    ->label('Marks')
                    ->sortable(),

                Tables\Columns\TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('Questions')
                    ->sortable(),

                Tables\Columns\TextColumn::make('attempts_count')
                    ->counts('attempts')
                    ->label('Attempts')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->label() : (AssessmentStatus::tryFrom($state)?->label() ?? $state))
                    ->color(fn ($state) => $state instanceof \BackedEnum ? $state->color() : (AssessmentStatus::tryFrom($state)?->color() ?? 'gray')),

                Tables\Columns\TextColumn::make('availability_start_at')
                    ->label('Available From')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Immediately')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->relationship('subject', 'name')
                    ->preload()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('section_id')
                    ->label('Class')
                    ->relationship('section', 'name')
                    ->preload()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('assessment_mode')
                    ->label('Mode')
                    ->options(collect(AssessmentMode::cases())
                        ->mapWithKeys(fn ($m) => [$m->value => $m->label()])
                        ->toArray()),

                Tables\Filters\SelectFilter::make('assessment_category')
                    ->label('Category')
                    ->options(collect(AssessmentCategory::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                        ->toArray()),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(AssessmentStatus::cases())
                        ->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                        ->toArray())
                    ->default(AssessmentStatus::Draft->value),

                Tables\Filters\Filter::make('contributes_to_grade')
                    ->label('Contributes to Grade')
                    ->query(fn ($query) => $query->where('contributes_to_grade', true)),

                Tables\Filters\Filter::make('with_attempts')
                    ->label('Has Attempts')
                    ->query(fn ($query) => $query->has('attempts')),

                Tables\Filters\Filter::make('no_questions')
                    ->label('No Questions Attached')
                    ->query(fn ($query) => $query->doesntHave('questions')),
            ])
            ->actions([
                Tables\Actions\Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->iconButton()
                    ->tooltip('Duplicate Assessment')
                    ->action(fn (DigitalAssessment $record) => app(DigitalAssessmentService::class)->duplicateAssessment($record))
                    ->after(function () {
                        Notification::make()->title('Assessment duplicated')->success()->send();
                    }),

                Tables\Actions\Action::make('publish')
                    ->icon('heroicon-o-check-circle')
                    ->iconButton()
                    ->tooltip('Publish')
                    ->color('success')
                    ->visible(fn (DigitalAssessment $record) => in_array($record->status, [
                        AssessmentStatus::Draft,
                        AssessmentStatus::Scheduled,
                    ]))
                    ->action(function (DigitalAssessment $record) {
                        try {
                            app(DigitalAssessmentService::class)->publishAssessment($record);
                            Notification::make()->title('Assessment published')->success()->send();
                        } catch (\DomainException $e) {
                            Notification::make()->title('Cannot publish')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\Action::make('close')
                    ->icon('heroicon-o-x-circle')
                    ->iconButton()
                    ->tooltip('Close Assessment')
                    ->color('warning')
                    ->visible(fn (DigitalAssessment $record) => in_array($record->status, [
                        AssessmentStatus::Published,
                        AssessmentStatus::Active,
                    ]))
                    ->action(fn (DigitalAssessment $record) => app(DigitalAssessmentService::class)->closeAssessment($record)),

                Tables\Actions\Action::make('analytics')
                    ->icon('heroicon-o-chart-bar')
                    ->iconButton()
                    ->tooltip('View Analytics')
                    ->color('success')
                    ->url(fn (DigitalAssessment $record) => \App\Filament\App\Pages\AssessmentAnalyticsPage::getUrl([$record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn (DigitalAssessment $record) => $record->attempts()->complete()->count() > 0),

                Tables\Actions\Action::make('markResponses')
                    ->icon('heroicon-o-pencil-square')
                    ->iconButton()
                    ->tooltip('Mark Responses')
                    ->color('info')
                    ->url(fn (DigitalAssessment $record) => \App\Filament\App\Pages\ManualMarkingPage::getUrl([$record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn (DigitalAssessment $record) => $record->attempts()->complete()->count() > 0),

                Tables\Actions\EditAction::make()->iconButton(),

                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkPublish')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $service = app(DigitalAssessmentService::class);
                            $published = 0;
                            foreach ($records as $record) {
                                try {
                                    $service->publishAssessment($record);
                                    $published++;
                                } catch (\DomainException $e) {
                                    // skip — no questions
                                }
                            }
                            Notification::make()
                                ->title("{$published} assessment(s) published")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ── Relations ───────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
        ];
    }

    // ── Pages ───────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDigitalAssessments::route('/'),
            'create' => Pages\CreateDigitalAssessment::route('/create'),
            'edit' => Pages\EditDigitalAssessment::route('/{record}/edit'),
        ];
    }
}
