<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeAsset;

class EmployeeAssetResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('HR & Payroll');
    }

    protected static ?string $model = EmployeeAsset::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationGroup = 'HR & Payroll';

    protected static ?string $modelLabel = 'Employee Asset';

    public static function getModelLabel(): string
    {
        return __(static::$modelLabel);
    }

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->label(__('Employee Name'))
                    ->options(fn () => Employee::all()->mapWithKeys(function ($emp) {
                        return [$emp->id => "{$emp->first_name} {$emp->last_name} ({$emp->employee_number})"];
                    }))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('asset_name')->required(),
                Forms\Components\TextInput::make('serial_number')->required(),
                Forms\Components\DatePicker::make('issued_date')->required(),
                Forms\Components\DatePicker::make('returned_date'),
                Forms\Components\Select::make('status')
                    ->options([
                        'issued' => __('Issued'),
                        'returned' => __('Returned'),
                        'damaged' => __('Damaged'),
                    ])->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.first_name')->label(__('Assigned To')),
                Tables\Columns\TextColumn::make('asset_name'),
                Tables\Columns\TextColumn::make('serial_number'),
                Tables\Columns\TextColumn::make('issued_date')->date(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'issued',
                        'success' => 'returned',
                        'danger' => 'damaged',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('return')
                    ->label(__('Return'))
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'issued')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'returned',
                            'returned_date' => now(),
                        ]);

                        Notification::make()
                            ->title(__('Asset Return Logged'))
                            ->success()
                            ->send();
                    }),

                Action::make('reportDamage')
                    ->label(__('Damage'))
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'issued')
                    ->form([
                        Forms\Components\Textarea::make('damage_notes')
                            ->label(__('Details of Damage'))
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'damaged',
                            'returned_date' => now(),
                        ]);

                        Notification::make()
                            ->title(__('Asset Logged as Damaged'))
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeAssets::route('/'),
        ];
    }
}

class ListEmployeeAssets extends ListRecords
{
    protected static string $resource = EmployeeAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('New Employee Asset')),
        ];
    }
}
