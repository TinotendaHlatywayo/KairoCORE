<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\AssessmentWorkflowResource\Pages;
use Filament\Forms;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\Section;

class AssessmentWorkflowResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Exams & Grading');
    }

    protected static ?string $model = AssessmentType::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?int $navigationSort = 1;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

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
                        Tab::make('Create')
                            ->label(__('1. Create Assessment'))
                            ->schema([
                                Forms\Components\Section::make('Assessment Details')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->placeholder(__('e.g., Mid-Year Exam, Term 2 Test')),
                                        Forms\Components\Select::make('subject_id')
                                            ->label(__('Subject'))
                                            ->relationship('subject', 'name')
                                            ->required()
                                            ->searchable(),
                                        Forms\Components\Select::make('section_id')
                                            ->label(__('Class Stream'))
                                            ->options(function () {
                                                return Section::with('course')
                                                    ->where('school_id', config('current_tenant_id'))
                                                    ->get()
                                                    ->pluck('full_name', 'id');
                                            })
                                            ->required()
                                            ->searchable(),
                                        Forms\Components\Select::make('type')
                                            ->label(__('Assessment Type'))
                                            ->options([
                                                'exam' => __('Exam'),
                                                'test' => __('Test'),
                                                'assignment' => __('Assignment'),
                                                'project' => __('Project'),
                                                'practical' => __('Practical'),
                                                'oral' => __('Oral'),
                                            ])
                                            ->required(),
                                        Forms\Components\Select::make('term_id')
                                            ->label(__('Term'))
                                            ->relationship('term', 'name')
                                            ->required(),
                                    ])->columns(2),

                                Forms\Components\Section::make('Grading & Scheduling')
                                    ->schema([
                                        Forms\Components\TextInput::make('max_marks')
                                            ->label(__('Max Marks'))
                                            ->numeric()
                                            ->default(100)
                                            ->required(),
                                        Forms\Components\DatePicker::make('scheduled_date')
                                            ->label(__('Scheduled Date'))
                                            ->required(),
                                        Forms\Components\DatePicker::make('due_date')
                                            ->label(__('Submission Due Date')),
                                        Forms\Components\Select::make('status')
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

                        Tab::make('Rubric')
                            ->label(__('2. Attach Rubric'))
                            ->schema([
                                Forms\Components\Section::make('Rubric Criteria')
                                    ->description(__('Define marking criteria for this assessment'))
                                    ->schema([
                                        Forms\Components\Repeater::make('rubric_criteria')
                                            ->schema([
                                                Forms\Components\TextInput::make('criterion')
                                                    ->label(__('Criterion'))
                                                    ->required(),
                                                Forms\Components\Textarea::make('description')
                                                    ->label(__('Description')),
                                                Forms\Components\TextInput::make('max_points')
                                                    ->label(__('Max Points'))
                                                    ->numeric()
                                                    ->required(),
                                                Forms\Components\Select::make('criterion_type')
                                                    ->label(__('Type'))
                                                    ->options([
                                                        'knowledge' => __('Knowledge'),
                                                        'understanding' => __('Understanding'),
                                                        'application' => __('Application'),
                                                        'analysis' => __('Analysis'),
                                                        'creativity' => __('Creativity'),
                                                    ]),
                                            ])
                                            ->grid(3)
                                            ->defaultItems(1),
                                    ]),
                            ]),

                        Tab::make('Assign')
                            ->label(__('3. Assign & Notify'))
                            ->schema([
                                Forms\Components\Section::make('Teacher Assignment')
                                    ->schema([
                                        Forms\Components\Select::make('examiner_id')
                                            ->label(__('Examiner / Marker'))
                                            ->relationship('examiner', 'name')
                                            ->searchable(),
                                        Forms\Components\Select::make('moderator_id')
                                            ->label(__('Moderator'))
                                            ->relationship('moderator', 'name')
                                            ->searchable(),
                                    ])->columns(2),

                                Forms\Components\Section::make('Notifications')
                                    ->schema([
                                        Forms\Components\Toggle::make('notify_students')
                                            ->label(__('Notify Students'))
                                            ->default(true),
                                        Forms\Components\Toggle::make('notify_parents')
                                            ->label(__('Notify Parents'))
                                            ->default(false),
                                        Forms\Components\DatePicker::make('notification_date')
                                            ->label(__('Notification Date'))
                                            ->default(now()),
                                    ]),
                            ]),

                        Tab::make('Marking')
                            ->label(__('4. Mark Entry'))
                            ->schema([
                                Forms\Components\Section::make('Marking Status')
                                    ->schema([
                                        Forms\Components\TextInput::make('marks_entered')
                                            ->label(__('Marks Entered'))
                                            ->disabled(),
                                        Forms\Components\TextInput::make('total_students')
                                            ->label(__('Total Students'))
                                            ->disabled(),
                                        Forms\Components\TextInput::make('completion_percentage')
                                            ->label(__('Completion %'))
                                            ->disabled(),
                                    ])->columns(3),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->subject?->name ?? ''),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label(__('Subject'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('section.full_name')
                    ->label(__('Class Stream'))
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'info' => 'exam',
                        'warning' => 'test',
                        'success' => 'assignment',
                        'primary' => 'project',
                        'gray' => 'practical',
                        'purple' => 'oral',
                    ]),
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
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label(__('Scheduled'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('marks_entered')
                    ->label(__('Marked'))
                    ->state(fn ($record) => $record->marks()->whereNotNull('marks_obtained')->count()),
                Tables\Columns\TextColumn::make('total_students')
                    ->label(__('Total'))
                    ->state(fn ($record) => $record->section?->enrollments()->count() ?? 0),
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
                Tables\Filters\SelectFilter::make('type'),
                Tables\Filters\SelectFilter::make('term_id')
                    ->relationship('term', 'name'),
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
                Tables\Actions\Action::make('moderate')
                    ->label(__('Moderate'))
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->visible(fn ($record) => in_array($record->status, ['marking', 'review'])),
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
                    Tables\Actions\BulkAction::make('bulkSchedule')
                        ->label(__('Schedule Selected'))
                        ->icon('heroicon-o-calendar')
                        ->form([
                            Forms\Components\DatePicker::make('scheduled_date')->required(),
                        ])
                        ->action(function ($records, $data) {
                            $records->each->update([
                                'status' => 'scheduled',
                                'scheduled_date' => $data['scheduled_date'],
                            ]);
                        }),
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
            ->defaultSort('scheduled_date', 'desc');
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
