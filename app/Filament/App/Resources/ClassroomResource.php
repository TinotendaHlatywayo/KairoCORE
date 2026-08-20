<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ClassroomResource\Pages;
use App\Services\Academic\AcademicValidationEngine;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Academics\Models\Classroom;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Timetables\Models\TimetableLesson;

class ClassroomResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Academics');
    }

    protected static ?string $model = Classroom::class;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isPageVisible('academics', 'streams')) {
            return false;
        }

        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('academic_ops.manage_classrooms');
        }

        return true;
    }

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Academics';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Classroom Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('e.g., Room 101 or Physics Lab'))
                            ->helperText(__('Unique identifier for this room.')),

                        Forms\Components\TextInput::make('capacity')
                            ->label(__('Capacity'))
                            ->numeric()
                            ->placeholder(__('e.g., 40'))
                            ->helperText(__('Maximum number of students this room can accommodate.')),

                        Forms\Components\TextInput::make('location')
                            ->label(__('Building / Location'))
                            ->maxLength(255)
                            ->placeholder(__('e.g., Main Building, Wing A'))
                            ->helperText(__('Where this classroom is located.')),

                        Forms\Components\Textarea::make('description')
                            ->label(__('Description'))
                            ->maxLength(500)
                            ->helperText(__('Additional notes about this classroom.')),
                    ])->columns(2),

                Forms\Components\Section::make('Room Features')
                    ->schema([
                        Forms\Components\CheckboxList::make('features')
                            ->label(__('Available Features'))
                            ->options([
                                'projector' => __('Projector'),
                                'whiteboard' => __('Whiteboard'),
                                'computers' => __('Computer Station'),
                                'science_lab' => __('Science Lab Equipment'),
                                'wifi' => __('WiFi Access'),
                                'ac' => __('Air Conditioning'),
                            ])
                            ->columns(2),
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
                    ->description(fn ($record) => $record->location ?? null),

                Tables\Columns\TextColumn::make('location')
                    ->label(__('Building'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('capacity')
                    ->label(__('Capacity'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('timetable_count')
                    ->label(__('Usage'))
                    ->state(fn ($record) => TimetableLesson::where('classroom_id', $record->id)->count())
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_capacity')
                    ->label(__('Has Capacity > 0'))
                    ->query(fn (Tables\Query $query) => $query->where('capacity', '>', 0)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make()
                    ->label(__('Usage')),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Classroom $record) {
                        $validator = new AcademicValidationEngine($record->school_id);
                        $result = $validator->validateAction('delete_classroom', ['classroom_id' => $record->id]);

                        if (! $result['valid']) {
                            Notification::make()
                                ->title(__('Cannot delete classroom'))
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
                                $result = $validator->validateAction('delete_classroom', ['classroom_id' => $record->id]);
                                if (! $result['valid']) {
                                    Notification::make()
                                        ->title('Cannot delete classroom: '.$record->name)
                                        ->body(implode(' ', $result['errors']))
                                        ->danger()
                                        ->send();

                                    throw new Halt;
                                }
                            }
                        }),
                    Tables\Actions\BulkAction::make('bulkExport')
                        ->label(__('Export'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            return redirect()->route('filament.app.resources.classrooms.export', [
                                'ids' => $records->pluck('id'),
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
            'index' => Pages\ListClassrooms::route('/'),
            'create' => Pages\CreateClassroom::route('/create'),
            'edit' => Pages\EditClassroom::route('/{record}/edit'),
        ];
    }
}
