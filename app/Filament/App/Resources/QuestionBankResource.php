<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\QuestionBankResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\DigitalAssessment\Enums\QuestionDifficulty;
use Modules\DigitalAssessment\Enums\QuestionStatus;
use Modules\DigitalAssessment\Enums\QuestionType;
use Modules\DigitalAssessment\Models\QuestionBank;
use Modules\DigitalAssessment\Services\QuestionBankService;

class QuestionBankResource extends Resource
{
    use ModulePermissionAccess;
    use ModuleAwareActiveNavigation;

    protected static ?string $model = QuestionBank::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?int $navigationSort = 63;

    protected static ?string $modelLabel = 'Question';

    protected static ?string $pluralModelLabel = 'Question Bank';

    public static function getNavigationGroup(): ?string
    {
        return __('Exams & Grading');
    }

    public static function getNavigationLabel(): string
    {
        return __('Question Bank');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['subject', 'createdBy'])
            ->latest();
    }

    // ── Form ────────────────────────────────────────────────

    /**
     * Map the per-type answer inputs onto the single `correct_answer` column.
     * Called from the Create/Save page hooks.
     */
    public static function normalizeAnswerPayload(array &$data): void
    {
        $type = $data['question_type'] ?? null;

        if ($type === QuestionType::TrueFalse->value && array_key_exists('tf_correct_answer', $data)) {
            $data['correct_answer'] = $data['tf_correct_answer'];
        }

        if ($type === QuestionType::MultipleChoice->value && array_key_exists('mcq_correct', $data)) {
            $data['correct_answer'] = $data['mcq_correct'];
        }

        if ($type === QuestionType::MultipleSelect->value && array_key_exists('ms_correct', $data)) {
            $data['correct_answer'] = $data['ms_correct'];
        }

        unset($data['tf_correct_answer'], $data['mcq_correct'], $data['ms_correct']);

        // Manual-marked questions never carry an automatic answer key.
        if (! empty($data['manual_marking'])) {
            $data['correct_answer'] = null;
        }
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            static::questionDetailsSection(),
            static::questionContentSection(),
            static::answerSection(),
            static::metadataSection(),
        ]);
    }

    protected static function questionDetailsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Question Details')
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Question Title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Quadratic Formula Application')
                    ->helperText('A short descriptive title for this question.')
                    ->columnSpanFull(),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Select::make('subject_id')
                            ->label('Subject')
                            ->relationship('subject', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Select Subject...'),

                        Forms\Components\Select::make('question_type')
                            ->label('Question Type')
                            ->options(collect(QuestionType::cases())
                                ->mapWithKeys(fn ($t) => [$t->value => $t->label()])
                                ->toArray())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($set) {
                                $set('options', null);
                                $set('correct_answer', null);
                                $set('matching_pairs', null);
                                $set('ordering_items', null);
                                $set('fill_blank_answer', null);
                                $set('short_answer', null);
                                $set('numeric_answer', null);
                            })
                            ->placeholder('Select Type...'),

                        Forms\Components\Select::make('difficulty')
                            ->label('Difficulty')
                            ->options(collect(QuestionDifficulty::cases())
                                ->mapWithKeys(fn ($d) => [$d->value => $d->label()])
                                ->toArray())
                            ->default(QuestionDifficulty::Intermediate)
                            ->required(),

                        Forms\Components\TextInput::make('marks')
                            ->label('Marks')
                            ->numeric()
                            ->default(1.00)
                            ->step(0.25)
                            ->minValue(0.25)
                            ->maxValue(100)
                            ->required(),

                        Forms\Components\TextInput::make('topic')
                            ->label('Topic')
                            ->maxLength(255)
                            ->placeholder('e.g., Algebra'),

                        Forms\Components\TextInput::make('subtopic')
                            ->label('Sub-topic')
                            ->maxLength(255)
                            ->placeholder('e.g., Quadratic Equations'),

                        Forms\Components\TextInput::make('grade_level')
                            ->label('Grade Level')
                            ->maxLength(50)
                            ->placeholder('e.g., Grade 10'),

                        Forms\Components\TextInput::make('learning_objective')
                            ->label('Learning Objective')
                            ->maxLength(255)
                            ->placeholder('What should the learner demonstrate?'),

                        Forms\Components\TextInput::make('competency')
                            ->label('Competency')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('curriculum_reference')
                            ->label('Curriculum Reference')
                            ->maxLength(255)
                            ->placeholder('e.g., KCSE 2024 Syllabus Section 2.1'),
                    ]),

                Forms\Components\TagsInput::make('tags')
                    ->label('Tags')
                    ->placeholder('Add tag and press Enter'),
            ])
            ->columns(1);
    }

    protected static function questionContentSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Question Content')
            ->schema([
                Forms\Components\RichEditor::make('question_text')
                    ->label('Question Text')
                    ->required()
                    ->toolbarButtons([
                        'bold', 'italic', 'underline', 'strike',
                        'link', 'orderedList', 'bulletList',
                        'blockquote', 'codeBlock',
                    ])
                    ->columnSpanFull()
                    ->helperText('The question stem presented to the learner.'),

                Forms\Components\RichEditor::make('explanation')
                    ->label('Explanation / Solution')
                    ->toolbarButtons([
                        'bold', 'italic', 'underline', 'strike',
                        'link', 'orderedList', 'bulletList',
                        'blockquote', 'codeBlock',
                    ])
                    ->columnSpanFull()
                    ->placeholder('Show this explanation after the learner answers...'),

                Forms\Components\FileUpload::make('images')
                    ->label('Images')
                    ->multiple()
                    ->directory('question-images')
                    ->visibility('private')
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                    ->columnSpanFull(),
            ]);
    }

    protected static function answerSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Answer Options')
            ->description('Configure the answer fields based on the selected question type.')
            ->schema([
                // ── Manual / auto marking mode ──
                Forms\Components\Toggle::make('manual_marking')
                    ->label('Mark Manually (disable auto marking)')
                    ->helperText('When ON, this question is graded by the teacher during marking — no correct answer is required and students receive no automatic score.')
                    ->default(false)
                    ->live()
                    ->columnSpanFull(),

                // ── Multiple Choice ──
                Forms\Components\Repeater::make('options')
                    ->label('Choices')
                    ->schema([
                        Forms\Components\TextInput::make('text')
                            ->label('Option Text')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->defaultItems(4)
                    ->minItems(2)
                    ->maxItems(10)
                    ->reorderable()
                    ->addActionLabel('Add Option')
                    ->visible(fn (Forms\Get $get) => in_array($get('question_type'), [
                        QuestionType::MultipleChoice->value,
                        QuestionType::MultipleSelect->value,
                    ])),

                Forms\Components\Select::make('mcq_correct')
                    ->label('Correct Answer')
                    ->options(fn (Forms\Get $get) => collect($get('options') ?? [])
                        ->values()
                        ->mapWithKeys(fn ($opt, $i) => [
                            $i => $opt['text'] ?? ('Option '.($i + 1)),
                        ])
                        ->toArray())
                    ->required(fn (Forms\Get $get) => ! $get('manual_marking'))
                    ->visible(fn (Forms\Get $get) => $get('question_type') === QuestionType::MultipleChoice->value
                        && ! $get('manual_marking'))
                    ->dehydrated(fn (Forms\Get $get) => $get('question_type') === QuestionType::MultipleChoice->value
                        && ! $get('manual_marking'))
                    ->reactive(),

                Forms\Components\Select::make('ms_correct')
                    ->label('Correct Answers (select multiple)')
                    ->multiple()
                    ->options(fn (Forms\Get $get) => collect($get('options') ?? [])
                        ->values()
                        ->mapWithKeys(fn ($opt, $i) => [
                            $i => $opt['text'] ?? ('Option '.($i + 1)),
                        ])
                        ->toArray())
                    ->required(fn (Forms\Get $get) => ! $get('manual_marking'))
                    ->visible(fn (Forms\Get $get) => $get('question_type') === QuestionType::MultipleSelect->value
                        && ! $get('manual_marking'))
                    ->dehydrated(fn (Forms\Get $get) => $get('question_type') === QuestionType::MultipleSelect->value
                        && ! $get('manual_marking'))
                    ->reactive(),

                // ── True / False ──
                // Dedicated state key: sharing 'correct_answer' across the
                // toggle and the selects made them overwrite each other.
                Forms\Components\Toggle::make('tf_correct_answer')
                    ->label('Correct Answer')
                    ->default(true)
                    ->required(fn (Forms\Get $get) => ! $get('manual_marking'))
                    ->visible(fn (Forms\Get $get) => $get('question_type') === QuestionType::TrueFalse->value
                        && ! $get('manual_marking'))
                    ->dehydrated(fn (Forms\Get $get) => $get('question_type') === QuestionType::TrueFalse->value
                        && ! $get('manual_marking')),

                // ── Short Answer ──
                Forms\Components\TextInput::make('short_answer')
                    ->label('Expected Answer')
                    ->maxLength(500)
                    ->required(fn (Forms\Get $get) => ! $get('manual_marking'))
                    ->placeholder('Enter the expected short answer...')
                    ->visible(fn (Forms\Get $get) => $get('question_type') === QuestionType::ShortAnswer->value)
                    ->dehydrated(fn (Forms\Get $get) => $get('question_type') === QuestionType::ShortAnswer->value),

                // ── Numeric ──
                Forms\Components\TextInput::make('numeric_answer')
                    ->label('Numeric Answer')
                    ->numeric()
                    ->step(0.001)
                    ->required(fn (Forms\Get $get) => ! $get('manual_marking'))
                    ->placeholder('e.g., 42.5')
                    ->visible(fn (Forms\Get $get) => $get('question_type') === QuestionType::Numeric->value)
                    ->dehydrated(fn (Forms\Get $get) => $get('question_type') === QuestionType::Numeric->value),

                // ── Matching ──
                Forms\Components\Repeater::make('matching_pairs')
                    ->label('Matching Pairs')
                    ->schema([
                        Forms\Components\TextInput::make('left')
                            ->label('Left Item')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('right')
                            ->label('Right Item')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->defaultItems(3)
                    ->minItems(2)
                    ->maxItems(10)
                    ->reorderable()
                    ->addActionLabel('Add Pair')
                    ->visible(fn (Forms\Get $get) => $get('question_type') === QuestionType::Matching->value),

                // ── Ordering ──
                Forms\Components\Repeater::make('ordering_items')
                    ->label('Items (drag to set correct order)')
                    ->schema([
                        Forms\Components\TextInput::make('text')
                            ->label('Item')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->defaultItems(4)
                    ->minItems(2)
                    ->maxItems(15)
                    ->reorderable()
                    ->addActionLabel('Add Item')
                    ->visible(fn (Forms\Get $get) => $get('question_type') === QuestionType::Ordering->value),

                // ── Fill in the Blank ──
                Forms\Components\TextInput::make('fill_blank_answer')
                    ->label('Blank Answer')
                    ->maxLength(255)
                    ->required(fn (Forms\Get $get) => ! $get('manual_marking'))
                    ->placeholder('The word or phrase to fill in the blank')
                    ->visible(fn (Forms\Get $get) => $get('question_type') === QuestionType::FillInTheBlank->value)
                    ->dehydrated(fn (Forms\Get $get) => $get('question_type') === QuestionType::FillInTheBlank->value),

                // ── Essay / File Upload ──
                Forms\Components\Placeholder::make('manual_marking_notice')
                    ->label('')
                    ->content(fn (Forms\Get $get) => match ($get('question_type')) {
                        QuestionType::Essay->value => 'This question type requires manual marking by the teacher.',
                        QuestionType::FileUpload->value => 'The learner will upload a file. Manual marking required.',
                        default => '',
                    })
                    ->visible(fn (Forms\Get $get) => in_array($get('question_type'), [
                        QuestionType::Essay->value,
                        QuestionType::FileUpload->value,
                    ])),
            ]);
    }

    protected static function metadataSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Status & Metadata')
            ->schema([
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(collect(QuestionStatus::cases())
                        ->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                        ->toArray())
                    ->default(QuestionStatus::Draft)
                    ->required(),

                Forms\Components\Placeholder::make('usage_count_display')
                    ->label('Usage Count')
                    ->content(fn (?QuestionBank $record) => $record?->usage_count ?? 0),

                Forms\Components\Placeholder::make('last_used_display')
                    ->label('Last Used')
                    ->content(fn (?QuestionBank $record) => $record?->last_used_at?->diffForHumans() ?? 'Never'),
            ])
            ->columns(3);
    }

    // ── Table ───────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn (QuestionBank $record) => $record->title),

                Tables\Columns\TextColumn::make('question_type')
                    ->badge()
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->label() : (QuestionType::tryFrom($state)?->label() ?? $state)),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->sortable(),

                Tables\Columns\TextColumn::make('difficulty')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->label() : (QuestionDifficulty::tryFrom($state)?->label() ?? $state))
                    ->color(fn ($state) => $state instanceof \BackedEnum ? $state->color() : (QuestionDifficulty::tryFrom($state)?->color() ?? 'gray')),

                Tables\Columns\TextColumn::make('marks')
                    ->sortable(),

                Tables\Columns\TextColumn::make('topic')
                    ->searchable()
                    ->limit(20)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->label() : (QuestionStatus::tryFrom($state)?->label() ?? $state))
                    ->color(fn ($state) => $state instanceof \BackedEnum ? $state->color() : (QuestionStatus::tryFrom($state)?->color() ?? 'gray')),

                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Uses')
                    ->sortable()
                    ->default(0),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->relationship('subject', 'name')
                    ->preload()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('question_type')
                    ->label('Type')
                    ->options(collect(QuestionType::cases())
                        ->mapWithKeys(fn ($t) => [$t->value => $t->label()])
                        ->toArray()),

                Tables\Filters\SelectFilter::make('difficulty')
                    ->label('Difficulty')
                    ->options(collect(QuestionDifficulty::cases())
                        ->mapWithKeys(fn ($d) => [$d->value => $d->label()])
                        ->toArray()),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(QuestionStatus::cases())
                        ->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                        ->toArray())
                    ->default(QuestionStatus::Published->value),

                Tables\Filters\Filter::make('auto_markable')
                    ->label('Auto-markable Only')
                    ->query(fn ($query) => $query->whereIn('question_type', array_column(QuestionType::autoMarkable(), 'value'))),

                Tables\Filters\Filter::make('never_used')
                    ->label('Never Used')
                    ->query(fn ($query) => $query->where('usage_count', 0)),

                Tables\Filters\Filter::make('with_images')
                    ->label('Has Images')
                    ->query(fn ($query) => $query->whereNotNull('images')->where('images', '!=', '[]')),
            ])
            ->actions([
                Tables\Actions\Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->iconButton()
                    ->tooltip('Duplicate Question')
                    ->action(fn (QuestionBank $record) => app(QuestionBankService::class)->duplicateQuestion($record))
                    ->after(function () {
                        Notification::make()->title('Question duplicated')->success()->send();
                    }),

                Tables\Actions\Action::make('publish')
                    ->icon('heroicon-o-check-circle')
                    ->iconButton()
                    ->tooltip('Publish')
                    ->color('success')
                    ->visible(fn (QuestionBank $record) => $record->status !== QuestionStatus::Published)
                    ->action(fn (QuestionBank $record) => app(QuestionBankService::class)->publishQuestion($record)),

                Tables\Actions\EditAction::make()->iconButton(),

                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkPublish')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => app(QuestionBankService::class)->bulkPublish($records->pluck('id')->toArray()))
                        ->after(function () {
                            Notification::make()->title('Questions published')->success()->send();
                        }),

                    Tables\Actions\BulkAction::make('bulkArchive')
                        ->label('Archive Selected')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->action(fn ($records) => app(QuestionBankService::class)->bulkArchive($records->pluck('id')->toArray()))
                        ->after(function () {
                            Notification::make()->title('Questions archived')->success()->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ── Pages ───────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit' => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
