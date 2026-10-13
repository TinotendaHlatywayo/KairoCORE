<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs; // Explicit import resolving layout compilation errors [1]
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Modules\SaaS\Models\PlatformSetting;

class PlatformSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-vertical';

    protected static ?string $navigationGroup = 'Platform Control';

    protected static ?string $navigationLabel = 'Platform Settings';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public function getTitle(): string
    {
        return __('Platform Settings');
    }

    protected static ?int $navigationSort = 1;

    protected static string $view = 'modules.saas.platform-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public function mount(): void
    {
        $state = [];
        try {
            $settings = PlatformSetting::all();
            foreach ($settings as $setting) {
                $state[$setting->group.'_'.$setting->key] = json_decode($setting->value, true) ?? $setting->value;
            }
        } catch (\Exception $e) {
            // Guard during database migrations sync phase
        }
        $this->form->fill($state);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('PlatformSettingsTabs')
                    ->tabs([
                        Tab::make('SaaS Branding')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                TextInput::make('branding_platform_name')->label(__('SaaS Platform Name'))->required(),
                                ColorPicker::make('branding_default_primary')->label(__('Global Primary Color'))->default('#4f46e5'),
                                FileUpload::make('branding_platform_logo')->label(__('Super Admin Console Logo'))->directory('platform/branding'),
                                FileUpload::make('branding_platform_favicon')->label(__('Platform Favicon'))->directory('platform/branding'),
                            ])->columns(2),

                        Tab::make('Module Control & Flags')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Select::make('modules_academics_release')
                                    ->label(__('Academics & SIS Module Release Stage'))
                                    ->options([
                                        'stable' => __('Stable Release (Fully Available)'),
                                        'beta' => __('Beta Stage'),
                                        'preview' => __('Feature Preview Only'),
                                        'deprecated' => __('Deprecated / Legacy Support'),
                                    ])->default('stable'),
                                Select::make('modules_boarding_release')
                                    ->label(__('Boarding & Welfare Module Release Stage'))
                                    ->options([
                                        'stable' => __('Stable Release'),
                                        'beta' => __('Beta Stage'),
                                        'preview' => __('Preview'),
                                    ])->default('stable'),
                                Select::make('modules_clinic_release')
                                    ->label(__('Clinic & Health Module Release Stage'))
                                    ->options([
                                        'stable' => __('Stable'),
                                        'beta' => __('Beta'),
                                    ])->default('beta'),
                            ])->columns(2),

                        Tab::make('SaaS Security Policies')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Select::make('security_password_rules')
                                    ->label(__('Global Password Requirements'))
                                    ->options([
                                        'standard' => __('Minimum 8 Characters'),
                                        'strong' => __('Minimum 10 Characters with Numbers & Special symbols'),
                                    ])->default('standard'),
                                Toggle::make('security_force_mfa')
                                    ->label(__('Enforce Multi-Factor Authentication (MFA) Globally'))
                                    ->default(false),
                                TextInput::make('security_api_rate_limit')
                                    ->label(__('Global API Rate Limit (Requests per minute)'))
                                    ->numeric()
                                    ->default(60),
                            ])->columns(2),

                        Tab::make('SaaS Automation Rules')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                Toggle::make('automation_auto_expire_trials')
                                    ->label(__('Enable Automatic Trial Expiration Checks'))
                                    ->helperText(__('Suspends schools whose free trial windows have expired.'))
                                    ->default(true),
                                Toggle::make('automation_auto_generate_recurring_expenses')
                                    ->label(__('Auto-generate recurring KairoCORE expenses'))
                                    ->helperText(__('Materialises each due occurrence of recurring platform expenses.'))
                                    ->default(true),
                                Toggle::make('automation_send_expense_reminders')
                                    ->label(__('Email reminders for recurring KairoCORE expenses'))
                                    ->helperText(__('Sends a warning before, the day before and on the due date of each recurring platform expense.'))
                                    ->default(true),
                                TextInput::make('automation_expense_reminder_days')
                                    ->label(__('First expense reminder (days before)'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(60)
                                    ->default(5)
                                    ->helperText(__('How many days ahead the first warning is emailed.'))
                                    ->visible(fn (callable $get) => (bool) $get('automation_send_expense_reminders')),
                                Toggle::make('automation_database_cleanup')
                                    ->label(__('Enable Scheduled Platform Cache/Log Optimization'))
                                    ->default(true),
                            ])->columns(2),

                        Tab::make('Notifications')
                            ->icon('heroicon-o-bell-alert')
                            ->schema([
                                Placeholder::make('notifications_intro')
                                    ->label(__('Platform Notifications'))
                                    ->content(new HtmlString(
                                        'Control where the platform sends its alert emails. In-app notifications are always '.
                                        'delivered inside this console; these addresses only decide the email copies. Defaults to '.
                                        '<strong>hlatwayne@gmail.com</strong> and can be changed at any time.'
                                    ))
                                    ->columnSpanFull(),
                                TextInput::make('notifications_super_admin_email')
                                    ->label(__('New school registration alert email'))
                                    ->email()
                                    ->default('hlatwayne@gmail.com')
                                    ->helperText(__('Receives an email every time a new institution registers.')),
                                TextInput::make('notifications_tenant_message_email')
                                    ->label(__('Tenant message alert email'))
                                    ->email()
                                    ->default('hlatwayne@gmail.com')
                                    ->helperText(__('Receives an email whenever a tenant sends a message to Kairo CORE.')),
                                Toggle::make('notifications_email_on_school_registration')
                                    ->label(__('Email me when a new school registers'))
                                    ->default(true)
                                    ->helperText(__('In-app notifications are always delivered; this only controls the email copy.')),
                                TextInput::make('notifications_system_from_email')
                                    ->label(__('System email (From address)'))
                                    ->email()
                                    ->default(platform_email_address())
                                    ->helperText(__('The address platform emails are sent from. Use one of the inboxes above, or a verified alias of the SMTP account.')),
                            ])->columns(2),

                        Tab::make('Contact Channels')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Placeholder::make('contact_intro')
                                    ->label(__('Public contact channels'))
                                    ->content(new HtmlString(
                                        'These details power the public website contact page and decide where the contact form delivers. '
                                        .'Leave a channel blank to hide it on the site.'
                                    ))
                                    ->columnSpanFull(),
                                TextInput::make('contact_email')
                                    ->label(__('Contact email address'))
                                    ->email()
                                    ->default('hlatwayne@gmail.com')
                                    ->helperText(__('Shown on the website and receives messages sent through the public contact form.')),
                                TextInput::make('contact_whatsapp')
                                    ->label(__('WhatsApp number'))
                                    ->tel()
                                    ->default('263786366855')
                                    ->helperText(__('Full international number, digits only — no +, spaces or dashes.')),
                                TextInput::make('contact_facebook')
                                    ->label(__('Facebook page URL'))
                                    ->url()
                                    ->default('https://facebook.com'),
                                TextInput::make('contact_instagram')
                                    ->label(__('Instagram profile URL'))
                                    ->url()
                                    ->default('https://instagram.com'),
                                TextInput::make('contact_tiktok')
                                    ->label(__('TikTok profile URL'))
                                    ->url()
                                    ->default('https://tiktok.com'),
                            ])->columns(2),

                        Tab::make('Website Content')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Placeholder::make('marketing_intro')
                                    ->label(__('Public website content'))
                                    ->content(new HtmlString(
                                        'These fields edit the public marketing site. Leave any field blank to fall back to the shipped copy. '
                                        .'Button links accept a full URL or a site-relative path such as <strong>/platform</strong>.'
                                    ))
                                    ->columnSpanFull(),
                                TextInput::make('marketing_announcement')
                                    ->label(__('Announcement bar'))
                                    ->maxLength(160)
                                    ->default(marketing_content('announcement'))
                                    ->columnSpanFull(),
                                TextInput::make('marketing_hero_title')
                                    ->label(__('Hero title'))
                                    ->maxLength(160)
                                    ->default(marketing_content('hero_title'))
                                    ->columnSpanFull(),
                                Textarea::make('marketing_hero_subtitle')
                                    ->label(__('Hero subtitle'))
                                    ->rows(2)
                                    ->maxLength(400)
                                    ->default(marketing_content('hero_subtitle'))
                                    ->columnSpanFull(),
                                TextInput::make('marketing_hero_image')
                                    ->label(__('Hero image URL'))
                                    ->url()
                                    ->default(marketing_content('hero_image'))
                                    ->helperText(__('Link to a hosted image, or leave blank to keep the bundled default.'))
                                    ->columnSpanFull(),
                                TextInput::make('marketing_hero_primary_label')
                                    ->label(__('Hero primary button label'))
                                    ->default(marketing_content('hero_primary_label')),
                                TextInput::make('marketing_hero_primary_url')
                                    ->label(__('Hero primary button link'))
                                    ->default(marketing_content('hero_primary_url'))
                                    ->helperText(__('Defaults to the registration page when blank.')),
                                TextInput::make('marketing_hero_secondary_label')
                                    ->label(__('Hero secondary button label'))
                                    ->default(marketing_content('hero_secondary_label')),
                                TextInput::make('marketing_hero_secondary_url')
                                    ->label(__('Hero secondary button link'))
                                    ->default(marketing_content('hero_secondary_url'))
                                    ->helperText(__('Defaults to the features page when blank.')),
                                TextInput::make('marketing_modules_title')
                                    ->label(__('Modules section title'))
                                    ->default(marketing_content('modules_title'))
                                    ->columnSpanFull(),
                                Textarea::make('marketing_modules_subtitle')
                                    ->label(__('Modules section subtitle'))
                                    ->rows(2)
                                    ->default(marketing_content('modules_subtitle'))
                                    ->columnSpanFull(),
                                TextInput::make('marketing_benefits_title')
                                    ->label(__('Benefits section title'))
                                    ->default(marketing_content('benefits_title'))
                                    ->columnSpanFull(),
                                Textarea::make('marketing_benefits_subtitle')
                                    ->label(__('Benefits section subtitle'))
                                    ->rows(2)
                                    ->default(marketing_content('benefits_subtitle'))
                                    ->columnSpanFull(),
                                TextInput::make('marketing_steps_title')
                                    ->label(__('How-it-works section title'))
                                    ->default(marketing_content('steps_title'))
                                    ->columnSpanFull(),
                                Textarea::make('marketing_steps_subtitle')
                                    ->label(__('How-it-works section subtitle'))
                                    ->rows(2)
                                    ->default(marketing_content('steps_subtitle'))
                                    ->columnSpanFull(),
                                TextInput::make('marketing_cta_title')
                                    ->label(__('Closing CTA title'))
                                    ->default(marketing_content('cta_title'))
                                    ->columnSpanFull(),
                                Textarea::make('marketing_cta_subtitle')
                                    ->label(__('Closing CTA subtitle'))
                                    ->rows(2)
                                    ->default(marketing_content('cta_subtitle'))
                                    ->columnSpanFull(),
                                TextInput::make('marketing_cta_primary_label')
                                    ->label(__('Closing CTA primary label'))
                                    ->default(marketing_content('cta_primary_label')),
                                TextInput::make('marketing_cta_primary_url')
                                    ->label(__('Closing CTA primary link'))
                                    ->default(marketing_content('cta_primary_url'))
                                    ->helperText(__('Defaults to the registration page when blank.')),
                                TextInput::make('marketing_cta_secondary_label')
                                    ->label(__('Closing CTA secondary label'))
                                    ->default(marketing_content('cta_secondary_label')),
                                TextInput::make('marketing_cta_secondary_url')
                                    ->label(__('Closing CTA secondary link'))
                                    ->default(marketing_content('cta_secondary_url'))
                                    ->helperText(__('Defaults to the features page when blank.')),
                                TextInput::make('marketing_footer_tagline')
                                    ->label(__('Footer tagline'))
                                    ->maxLength(200)
                                    ->default(marketing_content('footer_tagline'))
                                    ->columnSpanFull(),
                                TextInput::make('marketing_meta_title')
                                    ->label(__('Home SEO title'))
                                    ->maxLength(70)
                                    ->default(marketing_content('meta_title'))
                                    ->helperText(__('Leave blank to use the platform name.'))
                                    ->columnSpanFull(),
                                Textarea::make('marketing_meta_description')
                                    ->label(__('Home SEO description'))
                                    ->rows(2)
                                    ->maxLength(180)
                                    ->default(marketing_content('meta_description'))
                                    ->helperText(__('Leave blank to use the default platform description.'))
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Tab::make('Email Branding')
                            ->icon('heroicon-o-envelope-open')
                            ->schema([
                                Placeholder::make('email_branding_intro')
                                    ->label(__('Platform Email Branding'))
                                    ->content(new HtmlString(
                                        'These details brand every email <strong>the platform itself</strong> sends — registration alerts to administrators, billing receipts and dunning notices. '
                                        .'Schools configure branding for their own automatically-sent emails in their own workspace settings.'
                                    ))
                                    ->columnSpanFull(),
                                FileUpload::make('email_logo_path')
                                    ->label(__('Email logo'))
                                    ->image()
                                    ->imageEditor()
                                    ->directory('platform/email-branding')
                                    ->maxSize(2048)
                                    ->helperText(__('Shown at the top of outgoing platform emails. Falls back to the console logo when empty.')),
                                TextInput::make('email_company_name')
                                    ->label(__('Company / platform name'))
                                    ->default(config('app.name'))
                                    ->maxLength(120),
                                TextInput::make('email_company_address')
                                    ->label(__('Address'))
                                    ->maxLength(255),
                                TextInput::make('email_company_phone')
                                    ->label(__('Phone number'))
                                    ->tel()
                                    ->maxLength(40),
                                TextInput::make('email_company_email')
                                    ->label(__('Contact email address'))
                                    ->email()
                                    ->maxLength(120),
                            ])->columns(2),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $compoundKey => $value) {
            $parts = explode('_', $compoundKey, 2);
            if (count($parts) < 2) {
                continue;
            }

            $group = $parts[0];
            $key = $parts[1];

            PlatformSetting::updateOrCreate(
                [
                    'group' => $group,
                    'key' => $key,
                ],
                [
                    'value' => is_array($value) ? json_encode($value) : $value,
                ]
            );
        }

        Notification::make()
            ->title(__('Platform configurations updated'))
            ->success()
            ->send();
    }
}
