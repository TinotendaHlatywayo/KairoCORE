<?php

namespace App\Filament\App\Resources;

use App\Events\HostelOutPassRequested;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\HostelOutPassResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Hostels\Models\HostelOutPass;
use Modules\Hostels\Services\OutPassService;

class HostelOutPassResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Boarding & Welfare');
    }

    protected static ?string $model = HostelOutPass::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Boarding & Welfare';

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'first_name')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('hostel_id')
                            ->relationship('hostel', 'name')
                            ->required(),
                        Forms\Components\Hidden::make('requester_id')
                            ->default(fn () => Auth::id()),
                        Forms\Components\Select::make('type')
                            ->options([
                                'emergency' => __('Emergency Out-Pass'),
                                'weekend' => __('Weekend Leave'),
                                'medical' => __('Medical Check'),
                                'home' => __('Home Visit'),
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('expected_departure')->required(),
                        Forms\Components\DateTimePicker::make('expected_return')->required(),
                        Forms\Components\Textarea::make('reason')->required()->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.first_name')->searchable(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('expected_departure')->dateTime(),
            ])
            ->actions([
                Tables\Actions\Action::make('dispatch_otp')
                    ->label(__('Request Parental Approval'))
                    ->color('warning')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(function ($record) {
                        $record->update(['status' => 'pending_parent_otp']);
                        event(new HostelOutPassRequested($record));
                        Notification::make()->title(__('Authentication code dispatched to guardian.'))->success()->send();
                    }),

                Tables\Actions\Action::make('verify_otp')
                    ->label(__('Verify OTP'))
                    ->color('success')
                    ->icon('heroicon-o-shield-check')
                    ->visible(fn ($record) => $record->status === 'pending_parent_otp')
                    ->form([
                        Forms\Components\TextInput::make('otp')->length(6)->required(),
                    ])
                    ->action(function ($record, $data) {
                        $verified = app(OutPassService::class)->verifyOtp($record->school_id, $record->id, $data['otp']);
                        if ($verified) {
                            Notification::make()->title(__('OTP validation successful. Approved for final warden confirmation.'))->success()->send();
                        } else {
                            Notification::make()->title(__('OTP validation failed. Code is invalid.'))->danger()->send();
                        }
                    }),

                Tables\Actions\Action::make('warden_approve')
                    ->label(__('Confirm Warden Exit'))
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn ($record) => $record->status === 'pending_warden')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'warden_approver_id' => Auth::id(),
                            'warden_approved_at' => now(),
                        ]);
                        Notification::make()->title(__('Out-pass fully approved for scanner checkpoint.'))->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostelOutPasses::route('/'),
            'create' => Pages\CreateHostelOutPass::route('/create'),
            'edit' => Pages\EditHostelOutPass::route('/{record}/edit'),
        ];
    }
}
