<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
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
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'SaaS & Finance';

    protected static ?string $navigationLabel = 'Document Configuration';

    protected static ?string $title = 'Platform Document Configuration';

    protected string $view = 'filament.admin.pages.platform-document-config';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public function mount(): void
    {
        $state = [];
        foreach (array_keys($this->schemaDefaults()) as $defaultKey) {
            $state['documents_'.$defaultKey] = platform_document_config($defaultKey);
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
                                ColorPicker::make('documents_primary_color')->label(__('Primary Accent Color'))->default('#EE5D4B'),
                                ColorPicker::make('documents_dark_color')->label(__('Dark / Navy Color'))->default('#1E2A38'),
                                ColorPicker::make('documents_light_fill')->label(__('Light Background Fill'))->default('#F8F9FA'),
                                Toggle::make('documents_watermark_enabled')->label(__('Background Watermark'))->default(true),
                                TextInput::make('documents_watermark_opacity')
                                    ->label(__('Watermark Opacity'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(0.2)
                                    ->step(0.01)
                                    ->default(0.06),
                            ])->columns(2),

                        Tab::make('From / Contact')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                TextInput::make('documents_business_name')->label(__('Business Name')),
                                TextInput::make('documents_from_line_1')->label(__('Address Line 1')),
                                TextInput::make('documents_from_line_2')->label(__('Address Line 2')),
                                TextInput::make('documents_from_phone')->label(__('Phone')),
                                TextInput::make('documents_from_email')->label(__('Email')),
                            ])->columns(2),

                        Tab::make('Payment Channels')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Repeater::make('documents_payment_channels')
                                    ->label(__('Payment Methods & Account Numbers'))
                                    ->schema([
                                        TextInput::make('method')->label(__('Method'))->required(),
                                        TextInput::make('number')->label(__('Account / Number'))->required(),
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
                                TextInput::make('documents_callout_body')->label(__('Callout Body')),
                                TextInput::make('documents_cross_border_notice')
                                    ->label(__('Cross-Border Notice'))
                                    ->default('All international invoices can be settled via multi-currency or cross-border payment rails.'),
                            ]),

                        Tab::make('Closing & Typography')
                            ->icon('heroicon-o-language')
                            ->schema([
                                TextInput::make('documents_closing_title')->label(__('Closing Title'))->default('Thank you for your business!'),
                                TextInput::make('documents_closing_subtitle')->label(__('Closing Subtitle'))->default('Please use the invoice number as your payment reference.'),
                                TextInput::make('documents_closing_font_size')->label(__('Closing Font Size (px)'))->default('13'),
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
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $compoundKey => $value) {
            if (! str_starts_with($compoundKey, 'documents_')) {
                continue;
            }

            $key = substr($compoundKey, strlen('documents_'));

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
            ->title(__('Document configuration updated'))
            ->success()
            ->send();
    }

    private function schemaDefaults(): array
    {
        return [
            'primary_color' => '#EE5D4B',
            'dark_color' => '#1E2A38',
            'light_fill' => '#F8F9FA',
            'watermark_enabled' => true,
            'watermark_opacity' => 0.06,
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
