<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\PlatformBillingSetting;

/**
 * Super-admin control of the automated SaaS billing lifecycle: the reminder
 * offsets, the suspension window and the data-retention promise.
 */
class PlatformBillingSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Billing & Subscriptions';

    protected static ?string $navigationLabel = 'Billing Automation';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.admin.pages.platform-billing-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public function mount(): void
    {
        $this->form->fill(PlatformBillingSetting::current()->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('Reminder schedule'))
                    ->description(__('Days relative to each tenant\'s billing date. Set 0 to disable a step.'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('remind_days_before')
                            ->label(__('First reminder (days before)'))
                            ->numeric()->minValue(1)->maxValue(30)->required()->default(3),
                        TextInput::make('day_before_reminder_offset')
                            ->label(__('Day-before reminder (days before)'))
                            ->numeric()->minValue(0)->maxValue(30)->required()->default(1),
                        TextInput::make('overdue_reminder_offset')
                            ->label(__('Overdue reminder (days after)'))
                            ->numeric()->minValue(1)->maxValue(30)->required()->default(1),
                        TextInput::make('suspension_warning_offset')
                            ->label(__('Suspension warning (days after)'))
                            ->numeric()->minValue(1)->maxValue(60)->required()->default(4),
                        TextInput::make('suspension_offset')
                            ->label(__('Suspend access (days after)'))
                            ->numeric()->minValue(1)->maxValue(60)->required()->default(5),
                    ]),

                Section::make(__('Tenant grace period'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('default_free_days')
                            ->label(__('Free days after registration'))
                            ->helperText(__('How long a newly registered school is free before its first billing date.'))
                            ->numeric()->minValue(0)->maxValue(365)->required()->default(30),
                        TextInput::make('data_retention_months')
                            ->label(__('Data retention after suspension (months)'))
                            ->numeric()->minValue(1)->maxValue(120)->required()->default(3),
                    ]),

                Section::make(__('Super admin alerts'))
                    ->columns(2)
                    ->schema([
                        Toggle::make('notify_super_admin_billing')
                            ->label(__('Email me on each billing day'))
                            ->default(true),
                        TextInput::make('super_admin_billing_email')
                            ->label(__('Billing notification inbox'))
                            ->email()
                            ->maxLength(150),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $settings = PlatformBillingSetting::current();
        $settings->update($this->form->getState());

        Notification::make()
            ->title(__('Billing automation updated'))
            ->success()
            ->send();
    }
}
