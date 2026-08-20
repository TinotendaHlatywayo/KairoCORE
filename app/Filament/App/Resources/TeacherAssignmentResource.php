<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\TeacherAssignmentResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\CourseSubject;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;

class TeacherAssignmentResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Academics');
    }

    protected static ?string $model = CourseSubject::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Academics';

    protected static ?int $navigationSort = 7;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('Teacher Assignments');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assignment Details')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label(__('Form / Grade'))
                            ->options(Course::where('school_id', config('current_tenant_id'))->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->searchable(),

                        Forms\Components\Select::make('section_id')
                            ->label(__('Stream (Optional)'))
                            ->options(fn (Forms\Get $get) => Section::where('course_id', $get('course_id'))->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder(__('All streams in this form')),

                        Forms\Components\Select::make('subject_id')
                            ->label(__('Subject'))
                            ->options(Subject::where('school_id', config('current_tenant_id'))->pluck('name', 'id'))
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('teacher_id')
                            ->label(__('Teacher'))
                            ->options(User::where('school_id', config('current_tenant_id'))
                                ->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))
                                ->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->live(),

                        Forms\Components\Select::make('role')
                            ->label(__('Role'))
                            ->options([
                                'main' => __('Main Teacher'),
                                'assistant' => __('Assistant Teacher'),
                                'substitute' => __('Substitute'),
                            ])
                            ->default('main'),

                        Forms\Components\TextInput::make('periods_per_week')
                            ->label(__('Periods per Week'))
                            ->numeric()
                            ->default(4)
                            ->minValue(1),

                        Forms\Components\TextInput::make('room_preference')
                            ->label(__('Preferred Room'))
                            ->placeholder(__('e.g., Room 101, Science Lab')),
                    ])->columns(3),

                Forms\Components\Section::make('Validation')
                    ->schema([
                        Forms\Components\Placeholder::make('validation_note')
                            ->label(__('Conflict Check'))
                            ->content('Teacher conflicts will be checked automatically on save.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course.name')
                    ->label(__('Form'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('section.name')
                    ->label(__('Stream'))
                    ->placeholder(__('All')),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label(__('Subject'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label(__('Teacher'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'primary' => 'main',
                        'warning' => 'assistant',
                        'gray' => 'substitute',
                    ]),
                Tables\Columns\TextColumn::make('periods_per_week')
                    ->label(__('Periods/Week'))
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_conflict')
                    ->label(__('Schedule Conflict'))
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->hasScheduleConflict()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label(__('Form'))
                    ->relationship('course', 'name'),
                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label(__('Teacher'))
                    ->relationship('teacher', 'name'),
                Tables\Filters\SelectFilter::make('role'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('checkConflicts')
                    ->label(__('Check Conflicts'))
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->action(function ($record) {
                        $conflicts = self::detectTeacherConflicts($record);
                        if ($conflicts->isEmpty()) {
                            Notification::make()->title(__('No schedule conflicts for this teacher'))->success()->send();
                        } else {
                            Notification::make()
                                ->title($conflicts->count().' schedule conflicts found')
                                ->body($conflicts->pluck('name')->implode(', '))
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulkValidate')
                        ->label(__('Validate All Assignments'))
                        ->icon('heroicon-o-shield-check')
                        ->action(function ($records) {
                            $issues = 0;
                            foreach ($records as $record) {
                                if ($record->hasScheduleConflict()) {
                                    $issues++;
                                }
                            }
                            if ($issues === 0) {
                                Notification::make()->title(__('All assignments valid - no conflicts'))->success()->send();
                            } else {
                                Notification::make()
                                    ->title("$issues assignments have schedule conflicts")
                                    ->warning()
                                    ->send();
                            }
                        }),
                    Tables\Actions\BulkAction::make('bulkAssign')
                        ->label(__('Bulk Assign Teacher'))
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            Forms\Components\Select::make('teacher_id')
                                ->label(__('Teacher'))
                                ->options(User::where('school_id', config('current_tenant_id'))
                                    ->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))
                                    ->pluck('name', 'id'))
                                ->required(),
                            Forms\Components\Select::make('role')
                                ->options([
                                    'main' => __('Main Teacher'),
                                    'assistant' => __('Assistant Teacher'),
                                ])
                                ->default('main'),
                        ])
                        ->action(function ($records, $data) {
                            $records->each->update([
                                'teacher_id' => $data['teacher_id'],
                                'role' => $data['role'],
                            ]);
                            Notification::make()->title(count($records).' assignments updated')->success()->send();
                        }),
                ]),
            ])
            ->defaultSort('course.name', 'asc');
    }

    protected static function detectTeacherConflicts($record): Collection
    {
        if (! $record->teacher_id) {
            return collect();
        }

        return TimetableLesson::where('teacher_id', $record->teacher_id)
            ->where('school_id', $record->school_id)
            ->get()
            ->filter(function ($lesson) use ($record) {
                // Check if teacher has overlapping lessons
                return TimetableLesson::where('teacher_id', $record->teacher_id)
                    ->where('id', '!=', $lesson->id)
                    ->where('day', $lesson->day)
                    ->where(function ($q) use ($lesson) {
                        $q->where('start_time', '<', $lesson->end_time)
                            ->where('end_time', '>', $lesson->start_time);
                    })
                    ->exists();
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherAssignments::route('/'),
            'create' => Pages\CreateTeacherAssignment::route('/create'),
            'edit' => Pages\EditTeacherAssignment::route('/{record}/edit'),
        ];
    }
}
