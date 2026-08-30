<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\StaffAttendanceResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Attendance\Models\StaffAttendance;

class StaffAttendanceResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('HR & Payroll');
    }

    use ModuleAwareActiveNavigation;

    protected static ?string $model = StaffAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    // Grouping configuration:
    protected static ?string $navigationGroup = 'HR & Payroll';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('Staff Attendance');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Staff Attendance Entry')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label(__('Employee'))
                            ->options(User::whereNotNull('school_id')->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\DatePicker::make('date')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'present' => __('Present'),
                                'absent' => __('Absent'),
                                'late' => __('Late'),
                                'half_day' => __('Half Day'),
                                'excused' => __('Excused'),
                            ])
                            ->default('present')
                            ->required(),
                        Forms\Components\TimePicker::make('check_in_time')
                            ->label(__('Check-In Time (HH:MM)'))
                            ->default(now()->format('H:i')),
                        Forms\Components\TimePicker::make('check_out_time')
                            ->label(__('Check-Out Time (HH:MM)')),
                        Forms\Components\Select::make('method')
                            ->options([
                                'manual' => __('Manual Ledger'),
                                'rfid' => __('RFID Card Scan'),
                                'biometric' => __('Biometric Fingerprint'),
                                'qr' => __('QR Kiosk Check-In'),
                            ])
                            ->default('manual')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Employee'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'absent' => 'danger',
                        'late' => 'warning',
                        'half_day' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('check_in_time')
                    ->time('H:i')
                    ->label(__('Check-In')),
                Tables\Columns\TextColumn::make('check_out_time')
                    ->time('H:i')
                    ->label(__('Check-Out')),
                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'late' => 'Late',
                        'half_day' => 'Half Day',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListStaffAttendances::route('/'),
            'create' => Pages\CreateStaffAttendance::route('/create'),
            'edit' => Pages\EditStaffAttendance::route('/{record}/edit'),
        ];
    }
}
