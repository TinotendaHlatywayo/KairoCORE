<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AssessmentWorkflowResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;

class AssessmentWorkflowResource extends Resource
{
    protected static ?string $model = AssessmentType::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?int $navigationSort = 1;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return __('Exams & Grading');
    }

    public static function getNavigationLabel(): string
    {
        return __('Assessment Workflow');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Assessment Workflow')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Create')
                            ->label(__('1. Create Assessment'))
                            ->schema([
                                Forms\Components\Section::make('Assessment Details')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('Assessment / Test Name'))
                                            ->required()
                                            ->placeholder(__('e.g., Test 1, Test 2, End of Term Exam')),
                                        Forms\Components\Select::make('subject_id')
                                            ->label(__('Subject'))
                                            ->options(Subject::all()->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->placeholder(__('All Subjects')),
                                        Forms\Components\Select::make('section_id')
                                            ->label(__('Class Stream'))
                                            ->options(function () {
                                                return Section::with('course')
                                                    ->where('school_id', (current_tenant()?->id ?? auth()->user()?->school_id ?? 1))
                                                    ->get()
                                                    ->pluck('full_name', 'id');
                                            })
                                            ->searchable()
                                            ->placeholder(__('All Streams')),
                                        Forms\Components\Select::make('term_id')
                                            ->label(__('Term'))
                                            ->options(function () {
                                                $activeYear = AcademicYear::where('is_active', true)->first();
                                                if (! $activeYear) {
                                                    return [];
                                                }

                                                return Term::where('academic_year_id', $activeYear->id)
                                                    ->pluck('name', 'id');
                                            })
                                            ->required()
                                            ->preload()
                                            ->placeholder(__('Select Term...')),
                                    ])->columns(2),

                                Forms\Components\Section::make('Grading & Weighting')
                                    ->schema([
                                        Forms\Components\TextInput::make('max_mark')
                                            ->label(__('Max Attainable Mark'))
                                            ->numeric()
                                            ->default(100)
                                            ->required(),
                                        Forms\Components\TextInput::make('weight_percentage')
                                            ->label(__('Weight towards Final Term Grade (%)'))
                                            ->numeric()
                                            ->default(20)
                                            ->required()
                                            ->helperText(__('e.g. Test 1 = 20%, Test 2 = 20%, Exam = 60%')),
                                        Forms\Components\Select::make('status')
                                            ->label(__('Workflow Status'))
                                            ->options([
                                                'draft' => __('Draft'),
                                                'scheduled' => __('Scheduled'),
                                                'open' => __('Open for Marking'),
                                                'marking' => __('Marking in Progress'),
                                                'review' => __('Under Review'),
                                                'published' => __('Published'),
                                                'locked' => __('Locked'),
                                            ])
                                            ->default('draft'),
                                    ])->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make('Scope')
                            ->label(__('2. Define Scope'))
                            ->schema([
                                Forms\Components\Section::make('Assessment Scope Constraints (Optional)')
                                    ->description(__('Leave scope values empty if this test applies globally to all levels and subjects.'))
                                    ->schema([
                                        Forms\Components\Select::make('course_id')
                                            ->label(__('Restrict to Specific Grade / Form'))
                                            ->options(Course::all()->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->placeholder(__('All Form Levels'))
                                            ->live(),
                                    ])->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Hidden::make('created_by_id')
                    ->default(fn () => Auth::id()),
                Forms\Components\Hidden::make('school_id')
                    ->default(fn () => app('current_tenant')->id ?? auth()->user()?->school_id ?? 1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Assessment Name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->subject?->name ?? 'Global (All Subjects)'),

                Tables\Columns\TextColumn::make('max_mark')
                    ->label(__('Max Mark'))
                    ->numeric()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('weight_percentage')
                    ->label(__('Weight %'))
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('section.full_name')
                    ->label(__('Class Stream'))
                    ->default('Global (All Streams)')
                    ->searchable(),

                Tables\Columns\TextColumn::make('term.name')
                    ->label(__('Term'))
                    ->default('N/A'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'scheduled',
                        'blue' => 'open',
                        'warning' => 'marking',
                        'purple' => 'review',
                        'success' => 'published',
                        'danger' => 'locked',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'open' => 'Open',
                        'marking' => 'Marking',
                        'review' => 'Review',
                        'published' => 'Published',
                        'locked' => 'Locked',
                    ]),
                Tables\Filters\SelectFilter::make('subject_id')
                    ->relationship('subject', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('enter_marks')
                    ->label(__('Enter Marks'))
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.app.resources.assessment-marks.index', [
                        'tableFilters[assessment_type_id][value]' => $record->id,
                    ])),
                Tables\Actions\Action::make('publish')
                    ->label(__('Publish'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'published']);
                        Notification::make()->title(__('Assessment Published'))->success()->send();
                    })
                    ->visible(fn ($record) => $record->status === 'review'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulkOpen')
                        ->label(__('Open for Marking'))
                        ->icon('heroicon-o-lock-open')
                        ->action(fn ($records) => $records->each->update(['status' => 'open'])),
                    Tables\Actions\BulkAction::make('bulkPublish')
                        ->label(__('Publish Results'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'published'])),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssessmentWorkflows::route('/'),
            'create' => Pages\CreateAssessmentWorkflow::route('/create'),
            'edit' => Pages\EditAssessmentWorkflow::route('/{record}/edit'),
        ];
    }
}