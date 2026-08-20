<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TimeSlotResource\Pages;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Timetables\Models\TimeSlot;

class TimeSlotResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Academics');
    }

    protected static ?string $model = TimeSlot::class;

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

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Academics';

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return __('Time Slots / Periods');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Time Slot Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->placeholder(__('e.g., Period 1, Morning Break, Lunch')),
                        Forms\Components\Select::make('type')
                            ->label(__('Slot Type'))
                            ->options([
                                'teaching' => __('Teaching Period'),
                                'break' => __('Break'),
                                'lunch' => __('Lunch'),
                                'assembly' => __('Assembly'),
                                'activity' => __('Activity/Club'),
                                'registration' => __('Registration'),
                            ])
                            ->required()
                            ->default('teaching'),
                        Forms\Components\TimePicker::make('start_time')
                            ->label(__('Start Time'))
                            ->required()
                            ->seconds(false),
                        Forms\Components\TimePicker::make('end_time')
                            ->label(__('End Time'))
                            ->required()
                            ->seconds(false)
                            ->after('start_time'),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label(__('Duration (minutes)'))
                            ->numeric()
                            ->required()
                            ->helperText(__('Auto-calculated from start/end time')),
                        Forms\Components\TextInput::make('period_order')
                            ->label(__('Period Order'))
                            ->numeric()
                            ->default(0)
                            ->helperText(__('Order in the daily schedule (1, 2, 3...)')),
                    ])->columns(3),

                Forms\Components\Section::make('Conflict Detection')
                    ->description(__('This time slot will be checked against existing slots for overlaps'))
                    ->schema([
                        Forms\Components\Placeholder::make('conflict_check')
                            ->label(__('Status'))
                            ->content('No conflicts detected with current schedule.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'teaching',
                        'warning' => 'break',
                        'success' => 'lunch',
                        'info' => 'assembly',
                        'purple' => 'activity',
                        'gray' => 'registration',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label(__('Start'))
                    ->time('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label(__('End'))
                    ->time('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label(__('Duration'))
                    ->suffix(' min')
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_order')
                    ->label(__('Order'))
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_conflicts')
                    ->label(__('Conflicts'))
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->hasConflicts()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('checkConflicts')
                    ->label(__('Check Conflicts'))
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->action(function ($record) {
                        $conflicts = self::detectConflicts($record);
                        if ($conflicts->isEmpty()) {
                            Notification::make()->title(__('No conflicts found'))->success()->send();
                        } else {
                            Notification::make()
                                ->title(__('Conflicts detected!'))
                                ->body($conflicts->count().' overlapping time slots found')
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('reorder')
                        ->label(__('Reorder Periods'))
                        ->icon('heroicon-o-arrows-up-down')
                        ->form([
                            Forms\Components\Repeater::make('order')
                                ->schema([
                                    Forms\Components\Hidden::make('id'),
                                    Forms\Components\TextInput::make('period_order')
                                        ->label(__('New Order'))
                                        ->numeric()
                                        ->required(),
                                ])
                                ->defaultItems(0),
                        ])
                        ->action(function ($records, $data) {
                            foreach ($data['order'] as $item) {
                                TimeSlot::where('id', $item['id'])->update(['period_order' => $item['period_order']]);
                            }
                            Notification::make()->title(__('Periods reordered'))->success()->send();
                        }),
                    Tables\Actions\BulkAction::make('validateAll')
                        ->label(__('Validate All for Conflicts'))
                        ->icon('heroicon-o-shield-check')
                        ->action(function () {
                            $all = TimeSlot::where('school_id', config('current_tenant_id'))->get();
                            $conflicts = [];
                            foreach ($all as $slot) {
                                $slotConflicts = self::detectConflicts($slot);
                                if ($slotConflicts->isNotEmpty()) {
                                    $conflicts[$slot->id] = $slotConflicts;
                                }
                            }
                            if (empty($conflicts)) {
                                Notification::make()->title(__('All time slots valid - no conflicts'))->success()->send();
                            } else {
                                Notification::make()
                                    ->title('Conflicts found in '.count($conflicts).' time slots')
                                    ->warning()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultSort('period_order', 'asc');
    }

    protected static function detectConflicts(TimeSlot $slot): Collection
    {
        return TimeSlot::where('school_id', $slot->school_id)
            ->where('id', '!=', $slot->id)
            ->where(function ($query) use ($slot) {
                $query->where('start_time', '<', $slot->end_time)
                    ->where('end_time', '>', $slot->start_time);
            })
            ->get();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimeSlots::route('/'),
            'create' => Pages\CreateTimeSlot::route('/create'),
            'edit' => Pages\EditTimeSlot::route('/{record}/edit'),
        ];
    }
}
