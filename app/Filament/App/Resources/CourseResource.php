<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CourseResource\Pages;
use App\Services\Academic\AcademicValidationEngine;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Academics\Models\Course;
use Modules\Admin\Services\PermissionRegistry;

class CourseResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Academics');
    }

    protected static ?string $model = Course::class;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isPageVisible('academics', 'courses')) {
            return false;
        }

        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('academic_ops.manage_curriculum');
        }

        return true;
    }

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Academics';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return term('label.course_plural', 'Forms');
    }

    public static function getModelLabel(): string
    {
        return term('label.course', 'Form');
    }

    public static function getPluralModelLabel(): string
    {
        return term('label.course_plural', 'Forms');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Form Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(term('label.course', 'Form'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('e.g., Form 1 or Grade 10')),

                        Forms\Components\TextInput::make('code')
                            ->label(__('Code'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('e.g., F1 or G10')),

                        Forms\Components\Select::make('teacher_id')
                            ->label(__('Form Teacher'))
                            ->relationship('teacher', 'name')
                            ->searchable()
                            ->helperText(__('Assign a teacher to this Form.')),
                    ])->columns(3),

                Forms\Components\Section::make('Workflow Status')
                    ->description(__('Track the setup progress of this Form.'))
                    ->schema([
                        Forms\Components\Select::make('workflow_status')
                            ->label(__('Setup Stage'))
                            ->options([
                                'pending' => __('Pending'),
                                'in_progress' => __('In Progress'),
                                'complete' => __('Complete'),
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\DateTimePicker::make('workflow_completed_at')
                            ->label(__('Completed At')),
                    ])->columns(2),

                Forms\Components\Section::make('Classes (Streams)')
                    ->description(__('Create subdivision classes for this Form (e.g. A, B, C or Red, Blue, Green).'))
                    ->schema([
                        Forms\Components\Repeater::make('sections')
                            ->relationship('sections')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(term('label.section', 'Class'))
                                    ->required()
                                    ->placeholder(__('e.g., A or Red')),

                                Forms\Components\TextInput::make('code')
                                    ->placeholder(__('e.g., F1A or F1R')),

                                Forms\Components\TextInput::make('capacity')
                                    ->numeric()
                                    ->default(40)
                                    ->minValue(1)
                                    ->required(),
                            ])
                            ->grid(3)
                            ->defaultItems(1),
                    ]),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(term('label.form_level', 'Form / Level'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label(__('Teacher'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sections_count')
                    ->counts('sections')
                    ->label(term('label.section', 'Streams')),

                Tables\Columns\BadgeColumn::make('workflow_status')
                    ->label(__('Stage'))
                    ->colors([
                        'gray' => 'pending',
                        'info' => 'in_progress',
                        'success' => 'complete',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label(__('Teacher'))
                    ->relationship('teacher', 'name')
                    ->searchable(),
                Tables\Filters\Filter::make('without_teacher')
                    ->label(__('Unassigned Teachers Only'))
                    ->query(fn (Tables\Query $query) => $query->whereNull('teacher_id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Course $record) {
                        $validator = new AcademicValidationEngine($record->school_id);
                        $result = $validator->validateAction('delete_form', ['form_id' => $record->id]);

                        if (! $result['valid']) {
                            Notification::make()
                                ->title(__('Cannot delete form'))
                                ->body(implode(' ', $result['errors']))
                                ->danger()
                                ->send();

                            throw new Halt;
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $validator = new AcademicValidationEngine(auth()->user()?->school_id);

                            foreach ($records as $record) {
                                $result = $validator->validateAction('delete_form', ['form_id' => $record->id]);
                                if (! $result['valid']) {
                                    Notification::make()
                                        ->title('Cannot delete form: '.$record->name)
                                        ->body(implode(' ', $result['errors']))
                                        ->danger()
                                        ->send();

                                    throw new Halt;
                                }
                            }
                        }),
                    Tables\Actions\BulkAction::make('bulkAssignTeacher')
                        ->label(__('Assign Teacher'))
                        ->icon('heroicon-o-user-group')
                        ->form(function () {
                            return [
                                Forms\Components\Select::make('teacher_id')
                                    ->label(__('Teacher'))
                                    ->relationship('teacher', 'name')
                                    ->required(),
                            ];
                        })
                        ->action(function ($records, $data) {
                            $records->each->update(['teacher_id' => $data['teacher_id']]);
                        }),
                    Tables\Actions\BulkAction::make('bulkMarkComplete')
                        ->label(__('Mark Complete'))
                        ->icon('heroicon-o-check')
                        ->action(function ($records) {
                            $records->each->update([
                                'workflow_status' => 'complete',
                                'workflow_completed_at' => now(),
                            ]);
                        }),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}
