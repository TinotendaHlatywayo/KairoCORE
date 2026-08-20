<?php

namespace App\Filament\App\Concerns;

use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Modules\Admin\Enums\EmailCategory;
use Modules\Admin\Services\TenantEmailConfigurationService;

/**
 * Shared implementation of the tenant email configuration UI used by both the
 * dedicated "Email Configuration" page and the admissions settings page.
 *
 * State layout (namespaced under the page form's `data`):
 *   emailcfg.{category}.{field}
 *
 * Both entry points read and write the SAME underlying
 * `email_configurations` rows, so there is never a second, divergent copy.
 */
trait ManagesEmailConfiguration
{
    protected function emailConfigStatePrefix(EmailCategory $category): string
    {
        return 'emailcfg.'.$category->value;
    }

    /**
     * Build the schema for all four email categories.
     *
     * @return Section[]
     */
    public function emailConfigurationSections(): array
    {
        return array_map(
            fn (EmailCategory $category) => $this->emailConfigurationSection($category),
            EmailCategory::cases()
        );
    }

    /**
     * Build the schema for a single category. Each field is namespaced under
     * `emailcfg.{category}` so the same keys work on every hosting page.
     */
    protected function emailConfigurationSection(EmailCategory $category): Section
    {
        $prefix = $this->emailConfigStatePrefix($category);

        return Section::make($category->label().' '.__('Email Configuration'))
            ->description($this->emailCategoryDescription($category))
            ->icon($category->icon())
            ->collapsible()
            ->schema([
                Grid::make(4)->schema([
                    TextInput::make($prefix.'.from_name')
                        ->label(__('From Name'))
                        ->placeholder(__('Admissions Office'))
                        ->columnSpan(1),
                    TextInput::make($prefix.'.from_email')
                        ->label(__('From Email Address'))
                        ->email()
                        ->placeholder(__('admissions@yourschool.edu'))
                        ->helperText(__('This is the visible sender.'))
                        ->columnSpan(2),
                    Select::make($prefix.'.mailer')
                        ->label(__('Mailer'))
                        ->options([
                            'platform' => __('Platform transport'),
                            'smtp' => __('School SMTP'),
                            'log' => __('Log only (testing)'),
                            'sendmail' => __('Sendmail'),
                        ])
                        ->default('platform')
                        ->live()
                        ->columnSpan(1),
                    TextInput::make($prefix.'.reply_to_name')
                        ->label(__('Reply-To Name'))
                        ->placeholder(__('Admissions Office'))
                        ->columnSpan(1),
                    TextInput::make($prefix.'.reply_to_email')
                        ->label(__('Reply-To Email'))
                        ->email()
                        ->helperText(__('Optional. When replies are handled by a different address.'))
                        ->columnSpan(2),
                    Toggle::make($prefix.'.is_enabled')
                        ->label(__('Enabled'))
                        ->helperText(__('Must be on for this category to send email.'))
                        ->columnSpan(1),
                ]),

                Section::make(__('School SMTP Server'))
                    ->description(__('Only needed when the mailer above is set to "School SMTP". Credentials are encrypted at rest and never logged.'))
                    ->schema([
                        TextInput::make($prefix.'.host')
                            ->label(__('SMTP Host'))
                            ->placeholder(__('smtp.yourschool.edu')),
                        TextInput::make($prefix.'.port')
                            ->label(__('SMTP Port'))
                            ->numeric()
                            ->placeholder(__('587'))
                            ->suffix('port'),
                        Select::make($prefix.'.encryption')
                            ->label(__('Encryption'))
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                                '' => __('None'),
                            ])
                            ->default('tls'),
                        TextInput::make($prefix.'.username')
                            ->label(__('SMTP Username'))
                            ->autocomplete(false),
                        TextInput::make($prefix.'.password')
                            ->label(__('SMTP Password'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->helperText(__('Leave blank to keep the existing password.')),
                    ])
                    ->visible(fn (Get $get) => $get($prefix.'.mailer') === 'smtp')
                    ->columns(3),

                Grid::make(2)->schema([
                    Placeholder::make($prefix.'.status')
                        ->label(__('Verification Status'))
                        ->content(fn () => new HtmlString(
                            $this->emailConfigurationStatusBadge($category)
                        )),
                    Actions::make([
                        Action::make('send_test_'.$category->value)
                            ->label(__('Send Test Email'))
                            ->icon('heroicon-o-paper-airplane')
                            ->color('primary')
                            ->form([
                                TextInput::make('recipient')
                                    ->label(__('Recipient'))
                                    ->email()
                                    ->required()
                                    ->default(fn () => data_get($this->form->getState(), $prefix.'.from_email') ?? ''),
                            ])
                            ->action(function (Action $action, array $data) use ($category) {
                                $result = $this->sendTestEmailForCategory($category, $data['recipient'] ?? '');

                                Notification::make()
                                    ->title($result['success'] ? __('Test email sent') : __('Test email failed'))
                                    ->body($result['message'])
                                    ->{$result['success'] ? 'success' : 'danger'}()
                                    ->send();
                            }),
                    ]),
                ]),
            ]);
    }

    protected function emailCategoryDescription(EmailCategory $category): string
    {
        return match ($category) {
            EmailCategory::Admissions => 'Used for admission confirmation emails and new-application alerts.',
            EmailCategory::Finance => 'Used for invoices, receipts and payment reminders.',
            EmailCategory::Academic => 'Used for report cards, results and academic notifications.',
            EmailCategory::Communication => 'Used for general announcements and school-wide messaging.',
        };
    }

    /**
     * Fill the emailcfg.* state slice from the persisted tenant configuration.
     */
    protected function fillEmailConfigurationState(array &$data, ?array $categories = null): void
    {
        $categories ??= EmailCategory::cases();
        $service = app(TenantEmailConfigurationService::class);

        foreach ($categories as $category) {
            $config = $service->forCurrentTenant($category);
            $prefix = $this->emailConfigStatePrefix($category);

            data_set($data, $prefix, $config ? [
                'from_name' => $config->from_name,
                'from_email' => $config->from_email,
                'reply_to_name' => $config->reply_to_name,
                'reply_to_email' => $config->reply_to_email,
                'mailer' => $config->mailer,
                'host' => $config->host,
                'port' => $config->port,
                'username' => $config->username,
                'password' => '',
                'encryption' => $config->encryption,
                'is_enabled' => $config->is_enabled,
            ] : [
                'from_name' => null,
                'from_email' => null,
                'reply_to_name' => null,
                'reply_to_email' => null,
                'mailer' => 'platform',
                'host' => null,
                'port' => null,
                'username' => null,
                'password' => '',
                'encryption' => 'tls',
                'is_enabled' => false,
            ]);
        }
    }

    /**
     * Persist the emailcfg.* state slice into the tenant configuration table.
     */
    protected function saveEmailConfiguration(array $state, ?array $categories = null): void
    {
        $school = current_tenant();
        if (! $school) {
            return;
        }

        $categories ??= EmailCategory::cases();
        $service = app(TenantEmailConfigurationService::class);

        foreach ($categories as $category) {
            $prefix = $this->emailConfigStatePrefix($category);
            $values = data_get($state, $prefix);
            if (! is_array($values)) {
                continue;
            }

            $service->upsert($school, $category, $values);
        }
    }

    protected function sendTestEmailForCategory(EmailCategory $category, string $recipient): array
    {
        $school = current_tenant();
        if (! $school) {
            return ['success' => false, 'message' => 'No active school context.'];
        }

        $state = $this->form->getState() ?? [];
        $values = data_get($state, $this->emailConfigStatePrefix($category)) ?? [];

        return app(TenantEmailConfigurationService::class)
            ->sendTestEmail($school, $category, $values, $recipient);
    }

    protected function emailConfigurationStatusBadge(EmailCategory $category): string
    {
        $config = app(TenantEmailConfigurationService::class)->forCurrentTenant($category);

        if (! $config || ! $config->is_enabled) {
            return '<span class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">Not configured</span>';
        }

        $errors = app(TenantEmailConfigurationService::class)->validate($config);
        if (! empty($errors)) {
            return '<span class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Needs attention</span>';
        }

        if ($config->is_verified) {
            return '<span class="inline-flex items-center gap-1 rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">Verified</span>';
        }

        return '<span class="inline-flex items-center gap-1 rounded-md bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">Configured</span>';
    }
}
