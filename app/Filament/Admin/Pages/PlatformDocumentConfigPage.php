<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\PlatformSetting;

/**
 * Super-admin editor for the look, content and typography of every PDF document
 * the platform prints (invoices, receipts and account statements). Every value
 * is stored in PlatformSetting under the `documents` group and read back by the
 * platform_document_config() helper, so no code change is needed to rebrand.
 */
class PlatformDocumentConfigPage extends Page
{
    public ?array $data = [];

    /**
     * Which document the content edits apply to. 'all' writes the shared
     * keys; 'invoice' / 'receipt' write prefixed overrides (invoice_*, *)
     * that only that document type reads.
     */
    public string $docScope = 'all';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Document Configuration';

    public function getTitle(): string
    {
        return __('Platform Document Configuration');
    }

    protected static string $view = 'filament.admin.pages.platform-document-config';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public function mount(): void
    {
        $this->fillFromStorage();
    }

    /**
     * Reload the form with the values effective for the chosen document scope.
     */
    public function updatedDocScope(): void
    {
        $this->fillFromStorage();
    }

    private function fillFromStorage(): void
    {
        $state = [];
        foreach (array_keys($this->schemaDefaults()) as $defaultKey) {
            $state['documents_'.$defaultKey] = platform_document_config(
                $defaultKey,
                $this->docScope === 'all' ? null : $this->docScope
            );
        }
        $this->form->fill($state);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('DocumentConfigTabs')
                    ->tabs([
                        Tab::make('Branding')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                FileUpload::make('documents_logo')
                                    ->label(__('Document Logo'))
                                    ->helperText(__('Upload the full-colour logo printed on invoices, receipts and statements. Leave empty to use the bundled Kairo CORE logo.'))
                                    ->disk('public')
                                    ->directory('platform/documents')
                                    ->image()
                                    ->maxSize(4096)
                                    ->columnSpanFull(),
                                ColorPicker::make('documents_primary_color')
                                    ->label(__('Primary Accent Color'))
                                    ->default('#EF5F4D')
                                    ->rules(['regex:/^#[0-9a-fA-F]{6}$/']),
                                ColorPicker::make('documents_dark_color')
                                    ->label(__('Dark / Navy Color'))
                                    ->default('#1F2E43')
                                    ->rules(['regex:/^#[0-9a-fA-F]{6}$/']),
                                ColorPicker::make('documents_light_fill')
                                    ->label(__('Light Background Fill'))
                                    ->default('#F3F5F8')
                                    ->rules(['regex:/^#[0-9a-fA-F]{6}$/']),
                                Toggle::make('documents_watermark_enabled')->label(__('Background Watermark'))->default(true),
                                TextInput::make('documents_watermark_opacity')
                                    ->label(__('Watermark Opacity'))
                                    ->helperText(__('0.06 means the logo prints at about 6% intensity. The faded version is generated from your logo and cached.'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1)
                                    ->step(0.01)
                                    ->default(0.06),
                                FileUpload::make('documents_watermark_image')
                                    ->label(__('Custom Watermark Image (optional)'))
                                    ->helperText(__('When set, this image is used as-is instead of the generated faded logo.'))
                                    ->disk('public')
                                    ->directory('platform/documents')
                                    ->image()
                                    ->maxSize(4096),
                            ])->columns(2),

                        Tab::make('From / Contact')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                TextInput::make('documents_business_name')
                                    ->label(__('Business Name'))
                                    ->maxLength(150),
                                TextInput::make('documents_from_line_1')->label(__('Address Line 1'))->maxLength(150),
                                TextInput::make('documents_from_line_2')->label(__('Address Line 2'))->maxLength(150),
                                TextInput::make('documents_from_phone')->label(__('Phone'))->maxLength(50),
                                TextInput::make('documents_from_email')
                                    ->label(__('Email'))
                                    ->email()
                                    ->maxLength(150),
                            ])->columns(2),

                        Tab::make('Payment Channels')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Repeater::make('documents_payment_channels')
                                    ->label(__('Payment Methods & Account Numbers'))
                                    ->schema([
                                        TextInput::make('method')->label(__('Method'))->required()->maxLength(80),
                                        TextInput::make('number')->label(__('Account / Number'))->required()->maxLength(120),
                                    ])
                                    ->columns(2)
                                    ->default([
                                        ['method' => 'EcoCash', 'number' => '0785556855'],
                                        ['method' => 'Bank (CABS USD Account)', 'number' => '1149411511'],
                                    ]),
                            ]),

                        Tab::make('Callouts & Notes')
                            ->icon('heroicon-o-chat-bubble-bottom-center')
                            ->schema([
                                TextInput::make('documents_callout_title')->label(__('Callout Title'))->default('Payment Details'),
                                Textarea::make('documents_callout_body')
                                    ->label(__('Callout Body'))
                                    ->rows(3)
                                    ->helperText(__('Printed in the note box on the document. Changes here appear on the next generated invoice/receipt.')),
                                TextInput::make('documents_cross_border_notice')
                                    ->label(__('Cross-Border Notice'))
                                    ->default('All international invoices can be settled via multi-currency or cross-border payment rails.'),
                            ]),

                        Tab::make('Closing & Typography')
                            ->icon('heroicon-o-language')
                            ->schema([
                                TextInput::make('documents_closing_title')->label(__('Closing Title'))->default('Thank you for your business!'),
                                TextInput::make('documents_closing_subtitle')->label(__('Closing Subtitle'))->default('Please use the invoice number as your payment reference.'),
                                TextInput::make('documents_closing_font_size')
                                    ->label(__('Closing Font Size (px)'))
                                    ->numeric()
                                    ->minValue(8)
                                    ->maxValue(40)
                                    ->default('13'),
                                Select::make('documents_closing_font_weight')
                                    ->label(__('Closing Font Weight'))
                                    ->options([
                                        'normal' => 'Normal',
                                        'bold' => 'Bold',
                                    ])
                                    ->default('bold'),
                                Select::make('documents_closing_align')
                                    ->label(__('Closing Alignment'))
                                    ->options([
                                        'left' => __('Left'),
                                        'center' => __('Center'),
                                        'right' => __('Right'),
                                    ])
                                    ->default('center'),
                                TextInput::make('documents_date_format')->label(__('Date Format (PHP)'))->default('d F Y'),
                            ])->columns(2),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Invoice / Receipt scope prefixes every saved key so each document
        // type can carry its own content while sharing the global fallbacks.
        $prefix = $this->docScope === 'all' ? '' : $this->docScope.'_';

        foreach ($state as $compoundKey => $value) {
            if (! str_starts_with($compoundKey, 'documents_')) {
                continue;
            }

            $key = $prefix.substr($compoundKey, strlen('documents_'));

            PlatformSetting::updateOrCreate(
                [
                    'group' => 'documents',
                    'key' => $key,
                ],
                [
                    'value' => is_array($value) ? json_encode($value) : $value,
                ]
            );
        }

        Notification::make()
            ->title($this->docScope === 'all'
                ? __('Document configuration updated')
                : __('Configuration updated for :scope documents', ['scope' => ucfirst($this->docScope)]))
            ->success()
            ->send();
    }

    private function schemaDefaults(): array
    {
        return [
            'primary_color' => '#EF5F4D',
            'dark_color' => '#1F2E43',
            'light_fill' => '#F3F5F8',
            'watermark_enabled' => true,
            'watermark_opacity' => 0.06,
            'logo' => null,
            'watermark_image' => null,
            'business_name' => null,
            'from_line_1' => null,
            'from_line_2' => null,
            'from_phone' => null,
            'from_email' => null,
            'payment_channels' => [
                ['method' => 'EcoCash', 'number' => '0785556855'],
                ['method' => 'Bank (CABS USD Account)', 'number' => '1149411511'],
            ],
            'callout_title' => 'Payment Details',
            'callout_body' => null,
            'cross_border_notice' => 'All international invoices can be settled via multi-currency or cross-border payment rails.',
            'closing_title' => 'Thank you for your business!',
            'closing_subtitle' => 'Please use the invoice number as your payment reference.',
            'closing_font_size' => '13',
            'closing_font_weight' => 'bold',
            'closing_align' => 'center',
            'date_format' => 'd F Y',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Save Document Configuration'))
                ->action('save')
                ->color('primary'),
        ];
    }
}
