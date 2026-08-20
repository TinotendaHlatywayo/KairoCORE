<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TimetableLessonResource\Pages;
use App\Models\User;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Classroom;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Timetables\Models\TimeSlot;
use Modules\Timetables\Models\TimetableLesson;

class TimetableLessonResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Academics');
    }

    protected static ?string $model = TimetableLesson::class;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isVisible('academics')) {
            return false;
        }

        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('academic_ops.manage_timetable');
        }

        return true;
    }

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    // Grouping configuration:
    protected static ?string $navigationGroup = 'Academics';

    public static function getNavigationLabel(): string
    {
        return __('School Timetable');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Calendar & Class Configuration')
                    ->schema([
                        Forms\Components\Select::make('academic_year_id')
                            ->label(__('Academic Year'))
                            ->options(AcademicYear::pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->default(fn () => AcademicYear::where('is_active', true)->first()?->id),

                        Forms\Components\Select::make('term_id')
                            ->label(__('Term'))
                            ->options(fn (Forms\Get $get) => Term::where('academic_year_id', $get('academic_year_id'))->pluck('name', 'id'))
                            ->required(),

                        Forms\Components\Select::make('course_id')
                            ->label(term('label.course', 'Form'))
                            ->options(Course::pluck('name', 'id'))
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('section_id')
                            ->label(term('label.section', 'Class'))
                            ->options(fn (Forms\Get $get) => Section::where('course_id', $get('course_id'))->pluck('name', 'id'))
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Schedule & Resource Allocation')
                    ->schema([
                        Forms\Components\Select::make('day_of_week')
                            ->options([
                                'monday' => __('Monday'),
                                'tuesday' => __('Tuesday'),
                                'wednesday' => __('Wednesday'),
                                'thursday' => __('Thursday'),
                                'friday' => __('Friday'),
                            ])
                            ->required(),

                        Forms\Components\Select::make('time_slot_id')
                            ->label(__('Time Slot / Period'))
                            ->options(TimeSlot::pluck('name', 'id'))
                            ->required(),

                        Forms\Components\Select::make('subject_id')
                            ->label(__('Subject'))
                            ->options(Subject::pluck('name', 'id'))
                            ->required(),

                        Forms\Components\Select::make('teacher_id')
                            ->label(__('Teacher / Faculty'))
                            ->options(User::whereNotNull('school_id')->pluck('name', 'id'))
                            ->required(),

                        Forms\Components\Select::make('classroom_id')
                            ->label(__('Classroom / Facility'))
                            ->options(Classroom::pluck('name', 'id'))
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('day_of_week')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('timeSlot.name')
                    ->label(__('Period'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.name')
                    ->label(term('label.course', 'Form')),
                Tables\Columns\TextColumn::make('section.name')
                    ->label(term('label.section', 'Class')),
                Tables\Columns\TextColumn::make('subject.name')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label(__('Teacher'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->label(__('Room'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('day_of_week')
                    ->options([
                        'monday' => __('Monday'),
                        'tuesday' => __('Tuesday'),
                        'wednesday' => __('Wednesday'),
                        'thursday' => __('Thursday'),
                        'friday' => __('Friday'),
                    ]),
                Tables\Filters\SelectFilter::make('course_id')
                    ->label(__('Form Level'))
                    ->options(Course::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // Corrected and Scoped Filament Route Name
                Tables\Actions\Action::make('record_attendance')
                    ->label(__('Take Attendance'))
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->url(fn (TimetableLesson $record): string => route('filament.app.resources.timetable-lessons.record-attendance', ['record' => $record->id])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListTimetableLessons::route('/'),
            'create' => Pages\CreateTimetableLesson::route('/create'),
            'edit' => Pages\EditTimetableLesson::route('/{record}/edit'),

            // Crucial: Register the custom attendance page inside Filament's router map
            'record-attendance' => Pages\RecordAttendance::route('/{record}/attendance'),
        ];
    }
}
