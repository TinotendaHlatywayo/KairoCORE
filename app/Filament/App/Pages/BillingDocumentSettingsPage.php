<?php

namespace App\Filament\App\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Modules\Admin\Models\SystemSetting;
use Modules\Admin\Services\AuditLogger;
use Modules\Admin\Services\PermissionRegistry;

/**
 * Configures the layout and wording printed on invoices, receipts and
 * statements of account. Banking details live in System Settings; everything
 * else that appears on these documents is configured here.
 */
class BillingDocumentSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Finance';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    protected static ?string $navigationLabel = 'Invoice / Receipt Document Settings';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.app.pages.billing-document-settings';

    public ?array $data = [];

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return PermissionRegistry::checkPermission('finance.manage_fees')
            || PermissionRegistry::checkPermission('finance.view_reports')
            || PermissionRegistry::checkPermission('administration.manage_settings');
    }

    public function mount(): void
    {
        $settings = SystemSetting::where('group', 'invoice_docs')->get();
        $state = [];
        foreach ($settings as $setting) {
            $state[$setting->key] = json_decode($setting->value, true) ?? $setting->value;
        }

        $this->form->fill($state);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('InvoiceDocumentCategories')
                    ->tabs([
                        Tab::make('Invoice Layout')
                            ->icon('heroicon-o-document-duplicate')
                            ->schema([
                                Section::make('Invoice Display Options')
                                    ->schema([
                                        Toggle::make('show_logo')->label(__('Show School Logo'))->default(true),
                                        Toggle::make('show_boarding_status')->label(__('Show Boarding Status / Residence'))->default(true),
                                        Toggle::make('show_parent_address')->label(__('Show Parent & Billing Information'))->default(true),
                                    ])->columns(2),
                            ]),

                        Tab::make('Payment Instructions')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Section::make('Payment Channels & Reference Instructions')
                                    ->description('Banking details are configured under System Settings → Banking & Payments. Use the {ADMISSION_NUMBER} placeholder to insert each student\'s admission number.')
                                    ->schema([
                                        Textarea::make('reference_notice')
                                            ->label(__('Reference Notice'))
                                            ->rows(3)
                                            ->helperText(__('Use {ADMISSION_NUMBER} for the student admission number and {REGISTRATION_NUMBER} for the school ministry registration number.'))
                                            ->default('You MUST quote the Student Admission Number ({ADMISSION_NUMBER}) as the transaction reference. Payments without proper reference numbers may experience delay in reconciliation.'),
                                    ]),
                            ]),

                        Tab::make('Terms & Conditions')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Textarea::make('terms_conditions')
                                    ->label(__('Terms & Conditions'))
                                    ->rows(4)
                                    ->default('Fees are non-refundable. Please make payments through the listed bank channels only.'),
                            ]),

                        Tab::make('Signatures & Footers')
                            ->icon('heroicon-o-pencil')
                            ->schema([
                                Section::make('Signature Labels')
                                    ->description(__('Labels printed above the signature lines on each document.'))
                                    ->schema([
                                        TextInput::make('invoice_signature_left')->label(__('Invoice — Left Signature'))->default('Class Teacher Signature'),
                                        TextInput::make('invoice_signature_right')->label(__('Invoice — Right Signature'))->default('Accounts Clerk / Bursar Stamp'),
                                        TextInput::make('receipt_signature_left')->label(__('Receipt — Left Signature'))->default('Receiving Cashier / Bursar'),
                                        TextInput::make('receipt_signature_right')->label(__('Receipt — Right Signature'))->default('Official School Stamp'),
                                    ])->columns(2),

                                Section::make('Footer Notes')
                                    ->description(__('Small text printed at the bottom of each document.'))
                                    ->schema([
                                        Textarea::make('receipt_footer')
                                            ->label(__('Receipt Footer Note'))
                                            ->rows(2)
                                            ->default('Generated securely by {SCHOOL_NAME}. This is an official system-generated payment confirmation.'),
                                        Textarea::make('statement_footer')
                                            ->label(__('Statement of Account Footer Note'))
                                            ->rows(2)
                                            ->default('Generated securely by {SCHOOL_NAME}. Any query regarding these transactions should be directed to the school bursar.'),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $user = Auth::user();
        $schoolId = session('current_tenant')?->id ?? ($user ? $user->school_id : null);

        if (! $schoolId) {
            return;
        }

        $oldValues = [];
        $newValues = [];

        foreach ($state as $key => $value) {
            $setting = SystemSetting::where('school_id', $schoolId)
                ->where('group', 'invoice_docs')
                ->where('key', $key)
                ->first();

            $oldValues[$key] = $setting ? $setting->value : null;
            $newValues[$key] = is_array($value) ? json_encode($value) : $value;

            SystemSetting::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'group' => 'invoice_docs',
                    'key' => $key,
                ],
                [
                    'value' => is_array($value) ? json_encode($value) : $value,
                ]
            );
        }

        AuditLogger::log('Update Invoice Document Settings', 'Billing & Invoicing', $oldValues, $newValues);

        Notification::make()
            ->title(__('Invoice / receipt document settings saved'))
            ->success()
            ->send();
    }
}
