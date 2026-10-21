<?php

namespace App\Filament\Admin\Pages;

use App\Jobs\ConvertWebsiteMediaToWebp;
use App\Models\User;
use App\Rules\MaxVideoDuration;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs; // Explicit import resolving layout compilation errors [1]
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
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

    /** Currently edited website-content language (en = base keys). */
    public string $marketingLocale = 'en';

    /**
     * Snapshot of the marketing fields as displayed for $marketingLocale.
     * Save() only persists marketing fields whose current state differs from
     * this snapshot, so saving unrelated tabs never snapshots a language.
     *
     * @var array<string, mixed>
     */
    public array $marketingSnapshot = [];

    /** Website media slots accept photos, GIFs and short videos alike. */
    protected const WEBSITE_MEDIA_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif',
        'image/bmp', 'image/tiff',
        'video/mp4', 'video/quicktime', 'video/webm', 'video/x-m4v',
        'video/x-msvideo', 'video/mpeg', 'video/3gpp', 'video/x-matroska',
        'video/x-ms-wmv',
    ];

    /** Website conversions queued during the current save() run. */
    protected int $queuedMediaConversions = 0;

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
                // Upload scratch fields are never persisted settings; skip so
                // the live FileUpload components receive a clean null state.
                if (str_ends_with((string) $setting->key, '_upload')) {
                    continue;
                }
                $decoded = json_decode((string) $setting->value, true);
                $value = ($decoded !== null) ? $decoded : $setting->value;
                // Strip any stale upload scratch values nested in arrays.
                if (is_array($value)) {
                    $value = self::stripUploadKeys($value);
                }
                $state[$setting->group.'_'.$setting->key] = $value;
            }
        } catch (\Exception $e) {
            // Guard during database migrations sync phase
        }

        // Per-locale marketing overrides are addressed as "{key}@{locale}" and
        // are never form fields in their own right.
        foreach (array_keys($state) as $compoundKey) {
            if (str_starts_with((string) $compoundKey, 'marketing_') && str_contains(substr((string) $compoundKey, 10), '@')) {
                unset($state[$compoundKey]);
            }
        }

        // Prefill the Website Content fields with what the public site
        // currently renders for the selected language (en by default).
        $this->marketingLocale = 'en';
        foreach ($this->marketingScalarKeys() as $key) {
            $state['marketing_'.$key] = $this->marketingDisplayValue($key, 'en');
        }
        foreach ($this->marketingListKeys() as $key) {
            $state['marketing_'.$key] = $this->marketingDisplayList($key, 'en');
        }
        $state['marketing_language'] = 'en';

        // Slots whose video is still converting show the pending upload
        // instead of the current image (source kept under "{key}_source").
        foreach (array_keys($state) as $compoundKey) {
            if (! is_string($compoundKey) || ! str_ends_with($compoundKey, '_source')) {
                continue;
            }

            $target = substr($compoundKey, 0, -strlen('_source'));
            $pending = $state[$compoundKey];
            unset($state[$compoundKey]);

            if (is_string($pending) && $pending !== '' && $this->isImageUploadKey($target)) {
                $state[$target] = [$pending];
            }
        }

        foreach (array_keys($state) as $k) {
            if (is_string($k) && str_ends_with($k, '_upload')) {
                unset($state[$k]);

                continue;
            }
            // FileUpload state must be an array; wrap legacy string paths.
            if (is_string($state[$k]) && $this->isImageUploadKey($k)) {
                $state[$k] = $state[$k] === '' ? null : [$state[$k]];
            }
        }

        $this->form->fill($state);
        $this->snapshotMarketingState();
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
                                FileUpload::make('branding_platform_logo')->label(__('Super Admin Console Logo'))->disk('public')->directory('platform/branding')->image(),
                                FileUpload::make('branding_platform_favicon')->label(__('Platform Favicon'))->disk('public')->directory('platform/branding')->image(),
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
                                Toggle::make('automation_auto_generate_invoices')
                                    ->label(__('Auto-generate SaaS subscription invoices'))
                                    ->helperText(__('Raises the upcoming invoice for every billable subscription daily at 05:00. Skips schools that already have an open invoice.'))
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
                                TextInput::make('contact_location_url')
                                    ->label(__('Office location URL'))
                                    ->url()
                                    ->helperText(__('Google Maps link for the office, e.g. https://maps.google.com/?q=... — shown on the contact page location card. Falls back to a Google Maps search for the office location text when blank.')),
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
                                    ->disk('public')
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

    /**
     * Fields that sit above the page-section tabs on the Website Content
     * page: the intro copy, the edit-language picker and the animation
     * frame rate. Extracted from the old Platform Settings tab so the
     * standalone sidebar page and the settings class share one definition.
     *
     * @return array<int, mixed>
     */
    protected function websiteContentPrelude(): array
    {
        return [
            Placeholder::make('marketing_intro')
                ->label(__('Public website content'))
                ->content(new HtmlString(
                    'Every text and image on the public <strong>Home</strong>, <strong>Features</strong>, '
                    .'<strong>About</strong> and <strong>Contact</strong> pages is edited here. Pick a language first: fields show '
                    .'what the site currently renders in that language, and <strong>Save writes only the fields you changed</strong> '
                    .'for that language. Leave a field unchanged (or blank) to keep following the shipped, translated copy. '
                    .'Button links accept a full URL or a site-relative path such as <strong>/platform</strong>.'
                ))
                ->columnSpanFull(),

            Select::make('marketing_language')
                ->label(__('Edit language'))
                ->options([
                    'en' => __('English (default)'),
                    'sn' => 'Shona',
                    'sw' => 'Swahili',
                    'fr' => 'Français',
                    'pt' => 'Português',
                    'es' => 'Español',
                ])
                ->default('en')
                ->live()
                ->helperText(__('Switching language discards unsaved edits for the current language.'))
                ->afterStateUpdated(fn ($state) => $this->switchMarketingLocale((string) $state))
                ->columnSpanFull(),

            Select::make('media_video_fps')
                ->label(__('Animation frame rate'))
                ->options([
                    '10' => __('10 fps (smallest files)'),
                    '20' => __('20 fps (balanced)'),
                    '30' => __('30 fps (smoothest, default)'),
                ])
                ->default('30')
                ->helperText(__('Frame rate used when a website video or GIF is converted to an animated WebP in the background. Photos are converted to WebP automatically on save; the source video is deleted afterwards.'))
                ->columnSpanFull(),
        ];
    }

    /**
     * The public site's page-section tabs: Home, Features, About, Contact
     * and Global & SEO. Rendered by the standalone Website Content sidebar
     * page (Platform Settings no longer hosts website copy).
     *
     * @return array<int, mixed>
     */
    protected function marketingSectionTabs(): array
    {
        return [
            Tab::make('Home')
                ->icon('heroicon-o-home')
                ->columns(2)
                ->schema([
                    TextInput::make('marketing_announcement')
                        ->label(__('Announcement bar (optional)'))
                        ->maxLength(160),
                    FileUpload::make('marketing_nav_logo')
                        ->label(__('Navbar logo (light background)'))
                        ->disk('public')
                        ->directory('platform/website')
                        ->acceptedFileTypes(self::WEBSITE_MEDIA_MIME_TYPES)
                        ->maxSize(51200)
                        ->rules(fn (Get $get) => [
                            new MaxVideoDuration(8, (float) ($get('marketing_nav_logo_trim_start') ?? 0)),
                        ])
                        ->helperText(__('Image, GIF or a video — converted to WebP (animated for GIF/video) in the background. An 8-second clip is taken from the start point set below.'))
                        ->columnSpanFull(),
                    TextInput::make('marketing_nav_logo_trim_start')
                        ->label(__('Navbar video trim start (seconds)'))
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->helperText(__('Videos only: the animated clip is 8 seconds long, starting at this point. Ignored for photos.'))
                        ->columnSpanFull(),
                    FileUpload::make('marketing_footer_logo')
                        ->label(__('Footer logo (dark background)'))
                        ->disk('public')
                        ->directory('platform/website')
                        ->acceptedFileTypes(self::WEBSITE_MEDIA_MIME_TYPES)
                        ->maxSize(51200)
                        ->rules(fn (Get $get) => [
                            new MaxVideoDuration(8, (float) ($get('marketing_footer_logo_trim_start') ?? 0)),
                        ])
                        ->helperText(__('Image, GIF or a video — converted to WebP (animated for GIF/video) in the background. An 8-second clip is taken from the start point set below.'))
                        ->columnSpanFull(),
                    TextInput::make('marketing_footer_logo_trim_start')
                        ->label(__('Footer video trim start (seconds)'))
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->helperText(__('Videos only: the animated clip is 8 seconds long, starting at this point. Ignored for photos.'))
                        ->columnSpanFull(),

                    $this->mText('hero_badge', __('Hero badge'), null, true),
                    $this->mText('hero_title', __('Hero title'), null, true),
                    $this->mText('hero_subtitle', __('Hero subtitle'), null, true),
                    ...$this->mImage('hero_image', __('Hero image')),
                    $this->mText('hero_primary_label', __('Hero primary button label')),
                    $this->mText('hero_primary_url', __('Hero primary button link'), __('Defaults to the registration page when blank.')),
                    $this->mText('hero_secondary_label', __('Hero secondary button label')),
                    $this->mText('hero_secondary_url', __('Hero secondary button link'), __('Defaults to the features page when blank.')),
                    $this->mText('hero_trust_1', __('Hero trust point 1')),
                    $this->mText('hero_trust_2', __('Hero trust point 2')),
                    $this->mText('hero_trust_3', __('Hero trust point 3')),
                    $this->mText('hero_stat_1_label', __('Hero floating card 1 label')),
                    $this->mText('hero_stat_1_value', __('Hero floating card 1 value')),
                    $this->mText('hero_stat_2_label', __('Hero floating card 2 label')),
                    $this->mText('hero_stat_2_value', __('Hero floating card 2 value')),

                    Repeater::make('marketing_home_stats')
                        ->label(__('Stats marquee'))
                        ->collapsible()
                        ->itemLabel(fn (array $state) => $state['label'] ?? '#')
                        ->columns(3)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('value')->label(__('Number')),
                            TextInput::make('suffix')->label(__('Suffix'))->helperText(__('e.g. +, %')),
                            TextInput::make('label')->label(__('Label')),
                        ]),

                    $this->mText('modules_title', __('Modules section title'), null, true),
                    $this->mText('modules_subtitle', __('Modules section subtitle'), null, true),
                    $this->mText('modules_cta_label', __('Modules section button label')),

                    Repeater::make('marketing_home_modules')
                        ->label(__('Module cards (Home)'))
                        ->collapsible()
                        ->itemLabel(fn (array $state) => $state['name'] ?? '#')
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('name')->label(__('Name')),
                            TextInput::make('icon')->label(__('Icon path'))->hidden(),
                            Textarea::make('desc')->label(__('Description'))->rows(2)->columnSpanFull(),
                        ]),

                    $this->mText('benefits_title', __('Benefits section title'), null, true),
                    $this->mText('benefits_subtitle', __('Benefits section subtitle'), null, true),

                    Repeater::make('marketing_home_benefits')
                        ->label(__('Benefit cards (Home, dark section)'))
                        ->collapsible()
                        ->itemLabel(fn (array $state) => $state['name'] ?? '#')
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('name')->label(__('Name')),
                            TextInput::make('icon')->label(__('Icon path'))->hidden(),
                            Textarea::make('desc')->label(__('Description'))->rows(2)->columnSpanFull(),
                        ]),

                    $this->mText('steps_title', __('How-it-works section title'), null, true),
                    $this->mText('steps_subtitle', __('How-it-works section subtitle'), null, true),

                    Repeater::make('marketing_home_steps')
                        ->label(__('How-it-works steps'))
                        ->collapsible()
                        ->itemLabel(fn (array $state) => $state['title'] ?? '#')
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('title')->label(__('Title')),
                            Textarea::make('desc')->label(__('Description'))->rows(2),
                        ]),

                    $this->mText('cta_title', __('Closing CTA title'), null, true),
                    $this->mText('cta_subtitle', __('Closing CTA subtitle'), null, true),
                    $this->mText('cta_primary_label', __('Closing CTA primary label')),
                    $this->mText('cta_primary_url', __('Closing CTA primary link'), __('Defaults to the registration page when blank.')),
                    $this->mText('cta_secondary_label', __('Closing CTA secondary label')),
                    $this->mText('cta_secondary_url', __('Closing CTA secondary link'), __('Defaults to the features page when blank.')),
                ]),

            Tab::make('Features')
                ->icon('heroicon-o-squares-2x2')
                ->columns(2)
                ->schema([
                    $this->mText('features_badge', __('Page badge'), null, true),
                    $this->mText('features_title', __('Page title'), null, true),
                    $this->mText('features_subtitle', __('Page subtitle'), null, true),

                    Repeater::make('marketing_features_list')
                        ->label(__('Feature cards'))
                        ->collapsible()
                        ->itemLabel(fn (array $state) => $state['name'] ?? ($state['key'] ?? '#'))
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('name')->label(__('Name'))->columnSpanFull(),
                            Select::make('cat')
                                ->label(__('Filter category'))
                                ->options([
                                    'academic' => __('Academic'),
                                    'admin' => __('Administration'),
                                    'campus' => __('Campus life'),
                                ]),
                            TextInput::make('key')->label(__('Key'))->hidden(),
                            Textarea::make('desc')->label(__('Card summary'))->rows(2)->columnSpanFull(),
                            Textarea::make('detail')->label(__('Modal detail text'))->rows(3)->columnSpanFull(),
                            TextInput::make('sub1')->label(__('Sub-feature 1')),
                            TextInput::make('sub2')->label(__('Sub-feature 2')),
                            TextInput::make('sub3')->label(__('Sub-feature 3')),
                            TextInput::make('trim_start')
                                ->label(__('Video trim start (seconds)'))
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->helperText(__('Videos only: the animated clip is 8 seconds long, starting at this point. Ignored for photos.'))
                                ->columnSpanFull(),
                            FileUpload::make('image')
                                ->label(__('Feature image'))
                                ->disk('public')
                                ->directory('platform/website')
                                ->acceptedFileTypes(self::WEBSITE_MEDIA_MIME_TYPES)
                                ->maxSize(51200)
                                ->rules(fn (Get $get) => [
                                    new MaxVideoDuration(8, (float) ($get('trim_start') ?? 0)),
                                ])
                                ->helperText(__('Image, GIF or a video — converted to WebP (animated for GIF/video) in the background. An 8-second clip is taken from the trim start set above.'))
                                ->columnSpanFull(),
                        ]),

                    $this->mText('features_cta_title', __('Bottom CTA title'), null, true),
                    $this->mText('features_cta_subtitle', __('Bottom CTA subtitle'), null, true),
                    $this->mText('features_cta_primary_label', __('Bottom CTA primary label')),
                    $this->mText('features_cta_secondary_label', __('Bottom CTA secondary label')),
                ]),

            Tab::make('About')
                ->icon('heroicon-o-building-office-2')
                ->columns(2)
                ->schema([
                    $this->mText('about_badge', __('Page badge'), null, true),
                    $this->mText('about_title', __('Page title'), null, true),
                    $this->mText('about_intro', __('Page intro paragraph'), null, true),
                    ...$this->mImage('about_image', __('Hero image')),
                    $this->mText('about_founded_label', __('Founded card label')),
                    $this->mText('about_founded_value', __('Founded card value')),

                    Repeater::make('marketing_about_pillars')
                        ->label(__('Mission / vision / how-we-build cards'))
                        ->collapsible()
                        ->itemLabel(fn (array $state) => $state['title'] ?? '#')
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('title')->label(__('Title')),
                            TextInput::make('icon')->label(__('Icon path'))->hidden(),
                            Textarea::make('desc')->label(__('Description'))->rows(3)->columnSpanFull(),
                        ]),

                    $this->mText('about_why_badge', __('Why section badge'), null, true),
                    $this->mText('about_why_title', __('Why section title'), null, true),

                    Repeater::make('marketing_about_why_points')
                        ->label(__('Why-choose-us checklist'))
                        ->collapsible()
                        ->itemLabel(fn (array $state) => mb_substr((string) ($state['text'] ?? '#'), 0, 50))
                        ->columnSpanFull()
                        ->schema([
                            Textarea::make('text')->label(__('Point'))->rows(2)->columnSpanFull(),
                        ]),

                    $this->mText('about_story_badge', __('Story badge'), null, true),
                    $this->mText('about_story_title', __('Story title'), null, true),
                    $this->mText('about_story_p1', __('Story paragraph 1'), null, true),
                    $this->mText('about_story_p2', __('Story paragraph 2'), null, true),

                    Repeater::make('marketing_about_stats')
                        ->label(__('Story numbers'))
                        ->collapsible()
                        ->itemLabel(fn (array $state) => $state['label'] ?? '#')
                        ->columns(3)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('value')->label(__('Number')),
                            TextInput::make('suffix')->label(__('Suffix')),
                            TextInput::make('label')->label(__('Label')),
                        ]),

                    $this->mText('about_cta_title', __('Closing CTA title'), null, true),
                    $this->mText('about_cta_subtitle', __('Closing CTA subtitle'), null, true),
                ]),

            Tab::make('Contact')
                ->icon('heroicon-o-envelope')
                ->columns(2)
                ->schema([
                    $this->mText('contact_badge', __('Page badge'), null, true),
                    $this->mText('contact_title', __('Page title'), null, true),
                    $this->mText('contact_intro', __('Page intro paragraph'), null, true),
                    $this->mText('contact_form_title', __('Form title'), null, true),
                    $this->mText('contact_form_name_label', __('Form: name label')),
                    $this->mText('contact_form_email_label', __('Form: email label')),
                    $this->mText('contact_form_message_label', __('Form: message label')),
                    $this->mText('contact_form_placeholder_name', __('Form: name placeholder')),
                    $this->mText('contact_form_placeholder_message', __('Form: message placeholder'), null, true),
                    $this->mText('contact_form_submit_label', __('Form: submit button')),
                    $this->mText('contact_whatsapp_title', __('WhatsApp card title')),
                    $this->mText('contact_whatsapp_note', __('WhatsApp card note')),
                    $this->mText('contact_email_title', __('Email card title')),
                    $this->mText('contact_email_note', __('Email card note')),
                    $this->mText('contact_office_title', __('Office hours card title')),
                    $this->mText('contact_office_hours', __('Office hours')),
                    $this->mText('contact_office_location', __('Office location')),
                    $this->mText('contact_follow_label', __('Social label')),
                    ...$this->mImage('contact_image', __('Side image')),
                    $this->mText('contact_card_title', __('Side card title'), null, true),
                    $this->mText('contact_card_text', __('Side card text'), null, true),
                ]),

            Tab::make('Global & SEO')
                ->icon('heroicon-o-magnifying-glass')
                ->columns(2)
                ->schema([
                    $this->mText('footer_tagline', __('Footer tagline'), null, true),
                    $this->mText('meta_title', __('SEO title'), __('Leave blank to use the page default.'), true),
                    $this->mText('meta_description', __('SEO description'), __('Leave blank to use the default platform description.'), true),
                ]),
        ];
    }

    /**
     * In-view save button label — the blade is shared with the standalone
     * Website Content page, which saves a different scope of settings.
     */
    public function getSaveButtonLabel(): string
    {
        return __('Save System Settings');
    }

    public function save(): void
    {
        // Normalise any FileUpload state that leaked through as a bare string.
        // Intentionally NOT calling $this->form->fill() here: Form::fill()
        // re-runs afterStateHydrated, which existence-filters FileUpload state
        // against the target disk and discards Livewire TemporaryUploadedFile
        // instances (their temp paths live on the upload disk, not the public
        // disk) — silently wiping the upload before it can be persisted.
        $data = $this->data ?? [];
        $this->normaliseFileUploadState($data);
        $this->data = $data;

        $state = $this->form->getState();

        $locale = (string) ($this->data['marketing_language'] ?? $this->marketingLocale ?? 'en');
        if (! in_array($locale, array_keys(supported_locales()), true)) {
            $locale = 'en';
        }

        $this->queuedMediaConversions = 0;

        foreach ($state as $compoundKey => $value) {
            $parts = explode('_', (string) $compoundKey, 2);
            if (count($parts) < 2) {
                continue;
            }

            [$group, $key] = $parts;

            if ($group === 'marketing') {
                // Language picker and upload scratch fields are never settings.
                if ($key === 'language' || str_ends_with($key, '_upload')) {
                    continue;
                }
                // Only persist website-content fields the admin actually
                // changed for the selected language; untouched fields keep
                // following the shipped translated copy.
                if (! $this->marketingValueIsDirty($compoundKey, $value)) {
                    continue;
                }
                // Trim start is a technical value, not translated copy —
                // it stays shared across languages.
                if (! str_ends_with($key, '_trim_start')) {
                    $key = $locale === 'en' ? $key : $key.'@'.$locale;
                }
            }

            $featureJobs = [];
            $stillJob = null;

            $settingValue = $value;
            if ($compoundKey === 'marketing_features_list' && is_array($value)) {
                // Feature-card videos are queued for conversion; the public
                // list keeps serving the current images until WebP is ready.
                [$preparedItems, $featureJobs] = $this->prepareFeatureCardMedia($value, $group, $key);
                $settingValue = json_encode($preparedItems);
            } elseif ($this->isImageUploadKey($compoundKey) && is_array($settingValue)) {
                $settingValue = array_values($settingValue)[0] ?? null;
            } else {
                $settingValue = is_array($value) ? json_encode($value) : $value;
            }

            // Never let an empty FileUpload state overwrite a stored asset:
            // empty state dehydrates to null and would wipe the previous value.
            if ($this->isImageUploadKey($compoundKey)
                && ($settingValue === null || $settingValue === '' || $settingValue === [] || $settingValue === '[]')) {
                continue;
            }

            // Never persist a media path whose file is missing from the
            // public disk (stale form state from before a reset, or an
            // upload consumed elsewhere) — the site would render a blank
            // slot pointing at nothing.
            if ($this->isImageUploadKey($compoundKey)
                && str_starts_with($compoundKey, 'marketing_')
                && is_string($settingValue)
                && ! str_contains($settingValue, '://')
                && ! Storage::disk('public')->exists($settingValue)) {
                continue;
            }

            // Website media uploads convert to WebP in the background.
            // Videos keep the current public value until the job swaps in
            // the animated WebP, so their conversion can start right away;
            // photos are queued after the row write below, so the job can
            // safely point the setting at the resulting WebP.
            if ($this->isImageUploadKey($compoundKey)
                && str_starts_with($compoundKey, 'marketing_')
                && is_string($settingValue)
                && ! str_contains($settingValue, '://')) {
                if (ConvertWebsiteMediaToWebp::isAnimatedMediaPath($settingValue)) {
                    if (! Storage::disk('public')->exists($settingValue)) {
                        // Stale state (the video was already consumed by a
                        // previous conversion) — keep the current value.
                        continue;
                    }

                    $sourceKey = $key.'_source';

                    // Read the trim start from the validated form state for
                    // this exact compound key (it carries any locale suffix).
                    $trimStart = 0.0;
                    if (str_starts_with($compoundKey, 'marketing_')) {
                        $trimStart = (float) ($state[$compoundKey.'_trim_start'] ?? $this->data[$compoundKey.'_trim_start'] ?? 0);
                    }

                    PlatformSetting::updateOrCreate(
                        [
                            'group' => $group,
                            'key' => $sourceKey,
                        ],
                        [
                            'value' => $settingValue,
                        ]
                    );

                    ConvertWebsiteMediaToWebp::dispatch($group, $key, $settingValue, $this->websiteAnimationFps(), $sourceKey, null, 'image', $trimStart);
                    $this->queuedMediaConversions++;

                    continue;
                }

                if (ConvertWebsiteMediaToWebp::isStillImagePath($settingValue)
                    && Storage::disk('public')->exists($settingValue)) {
                    $stillJob = new ConvertWebsiteMediaToWebp(
                        $group,
                        $key,
                        $settingValue,
                        $this->websiteAnimationFps()
                    );
                }
            }

            PlatformSetting::updateOrCreate(
                [
                    'group' => $group,
                    'key' => $key,
                ],
                [
                    'value' => $settingValue,
                ]
            );

            // Conversions start only once the owning row is persisted, so a
            // synchronous worker cannot read a stale value.
            if ($stillJob !== null) {
                dispatch($stillJob);
                $this->queuedMediaConversions++;
            }
            foreach ($featureJobs as $featureJob) {
                dispatch($featureJob);
                $this->queuedMediaConversions++;
            }
        }

        // Reflect saved values back into the live form and re-snapshot so a
        // second Save in the same session stays idempotent. Image keys are
        // skipped: their form state already holds the hydrated array (writing
        // the dehydrated string back would break the FileUpload component).
        foreach ($state as $compoundKey => $value) {
            if (is_string($compoundKey)
                && str_starts_with($compoundKey, 'marketing_')
                && ! $this->isImageUploadKey($compoundKey)) {
                $this->data[$compoundKey] = $value;
            }
        }
        $this->snapshotMarketingState();

        Notification::make()
            ->title(__('Platform configurations updated'))
            ->success()
            ->send();

        if ($this->queuedMediaConversions > 0) {
            Notification::make()
                ->title(__('Media queued for WebP conversion'))
                ->body(__('Your upload is being converted in the background — the website picks up the new WebP (animated for videos and GIFs) within about a minute, and the source video is deleted afterwards.'))
                ->info()
                ->send();
        }
    }

    /**
     * Surface a client-side upload rejection (too long, too large, or the
     * server refused the body) with a specific reason instead of the generic
     * "failed to upload" the FileUpload component shows on its own.
     */
    #[On('websiteMediaRejected')]
    public function websiteMediaRejected(string $title, string $body): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->warning()
            ->send();
    }

    // ───────────────────────────────────────────────────────────────
    // Website content (marketing) helpers
    // ───────────────────────────────────────────────────────────────

    /**
     * Prepare a features-card list for persistence: card videos/GIFs get a
     * deferred conversion job while the list keeps serving the images the
     * site currently displays (the pending upload is remembered under
     * "image_source" so the editor can re-display it after a reload).
     *
     * Jobs are returned undispatched — the caller dispatches them after the
     * row has been written, so a synchronous worker cannot read stale data.
     *
     * @param  array<int|string, array<string, mixed>>  $items
     * @return array{0: array<int|string, array<string, mixed>>, 1: array<int, ConvertWebsiteMediaToWebp>}
     */
    protected function prepareFeatureCardMedia(array $items, string $group, string $key): array
    {
        // Previous public images by position: the stored row is
        // authoritative (it holds the value the site renders even while a
        // conversion is pending); the mount snapshot covers the case where
        // no row exists yet. Lists align positionally through fill(), the
        // same assumption marketingDisplayList() makes.
        $previousImages = [];
        $storedList = marketing_settings_map()[$key] ?? null;

        $previousSource = (is_array($storedList) && $storedList !== [])
            ? $storedList
            : (array) ($this->marketingSnapshot['marketing_features_list'] ?? []);

        foreach (array_values($previousSource) as $previous) {
            $previousImages[] = is_array($previous) ? ($previous['image'] ?? null) : null;
        }

        $jobs = [];
        $position = 0;

        foreach ($items as $i => $item) {
            if (! is_array($item)) {
                $position++;

                continue;
            }

            $media = $item['image'] ?? null;
            if (is_array($media)) {
                $media = array_values($media)[0] ?? null;
            }

            $trimStart = (float) ($item['trim_start'] ?? 0);

            if (is_string($media) && $media !== '' && ! str_contains($media, '://')) {
                if (ConvertWebsiteMediaToWebp::isAnimatedMediaPath($media)) {
                    if (Storage::disk('public')->exists($media)) {
                        // Never let a raw video path reach the public
                        // markup: queue it and remember the pending upload.
                        $jobs[] = new ConvertWebsiteMediaToWebp(
                            $group,
                            $key,
                            $media,
                            $this->websiteAnimationFps(),
                            null,
                            (string) $i,
                            'image',
                            $trimStart
                        );

                        $items[$i]['image_source'] = $media;
                    }

                    // Keep serving the previous image until the WebP lands
                    // (also covers stale state whose video was consumed).
                    $items[$i]['image'] = $previousImages[$position] ?? null;
                } elseif (ConvertWebsiteMediaToWebp::isStillImagePath($media)
                    && Storage::disk('public')->exists($media)) {
                    $jobs[] = new ConvertWebsiteMediaToWebp(
                        $group,
                        $key,
                        $media,
                        $this->websiteAnimationFps(),
                        null,
                        (string) $i
                    );
                }
            }

            $position++;
        }

        return [$items, $jobs];
    }

    /** Frame rate for background WebP animations (media/video_fps, default 30). */
    protected function websiteAnimationFps(): int
    {
        $fps = (int) PlatformSetting::get('media', 'video_fps', 30);

        return in_array($fps, [10, 20, 30], true) ? $fps : 30;
    }

    /**
     * Scalar website-content keys (edit targets of the form fields).
     *
     * @return array<int, string>
     */
    protected function marketingScalarKeys(): array
    {
        return array_keys(marketing_content_defaults());
    }

    /**
     * Repeater-backed website-content list keys.
     *
     * @return array<int, string>
     */
    protected function marketingListKeys(): array
    {
        return array_keys(marketing_list_defaults());
    }

    /**
     * Shipped scalar defaults always evaluated in English, regardless of the
     * console locale, so per-language editing starts from the real source copy.
     *
     * @return array<string, string>
     */
    protected function marketingDefaultsEn(): array
    {
        $previous = app()->getLocale();
        app()->setLocale('en');

        try {
            return marketing_content_defaults();
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * Shipped list defaults always evaluated in English (see above).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function marketingListDefaultsEn(): array
    {
        $previous = app()->getLocale();
        app()->setLocale('en');

        try {
            return marketing_list_defaults();
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * What the public site currently renders for $key in $locale — mirrors
     * marketing_content(): "{key}@{locale}" → "{key}" → translated default.
     */
    protected function marketingDisplayValue(string $key, string $locale): string
    {
        $map = marketing_settings_map();

        foreach ([$key.'@'.$locale, $key] as $lookup) {
            $value = $map[$lookup] ?? null;
            if (is_array($value)) {
                $value = $value[0] ?? null;
            }
            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        $default = (string) ($this->marketingDefaultsEn()[$key] ?? '');
        if ($default === '' || $locale === 'en') {
            return $default;
        }

        return (string) __($default, [], $locale);
    }

    /**
     * What the public site currently renders for a list in $locale — mirrors
     * marketing_merged_list(): stored "{key}@{locale}" / "{key}" wins,
     * otherwise the shipped defaults translated into $locale.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function marketingDisplayList(string $key, string $locale): array
    {
        $map = marketing_settings_map();
        $stored = $map[$key.'@'.$locale] ?? null;
        if (! is_array($stored) || $stored === []) {
            $stored = $map[$key] ?? null;
        }

        $defaults = $this->marketingListDefaultsEn()[$key] ?? [];

        if (is_array($stored) && $stored !== []) {
            $merged = [];
            foreach (array_values($stored) as $i => $item) {
                if (! is_array($item)) {
                    continue;
                }
                $clean = array_filter($item, fn ($v) => $v !== '' && $v !== null);
                $merged[] = array_merge($defaults[$i] ?? [], $clean);
            }

            // A card video still converting: re-display the pending upload
            // in the editor while the site keeps rendering "image".
            foreach ($merged as $i => $item) {
                if (is_array($item)
                    && isset($item['image_source'])
                    && is_string($item['image_source'])
                    && $item['image_source'] !== '') {
                    $merged[$i]['image'] = $item['image_source'];
                }
            }

            return $merged ?: $defaults;
        }

        if ($locale === 'en') {
            return $defaults;
        }

        return $this->translateMarketingList($defaults, $locale);
    }

    /**
     * Translate the human-readable fields of shipped list defaults into the
     * language being edited (structural fields such as icons stay untouched).
     *
     * @param  array<int, array<string, mixed>>  $list
     * @return array<int, array<string, mixed>>
     */
    protected function translateMarketingList(array $list, string $locale): array
    {
        $textFields = ['name', 'desc', 'detail', 'title', 'label', 'text', 'sub1', 'sub2', 'sub3'];

        foreach ($list as $i => $item) {
            if (! is_array($item)) {
                continue;
            }
            foreach ($textFields as $field) {
                if (isset($item[$field]) && is_string($item[$field]) && $item[$field] !== '') {
                    $list[$i][$field] = (string) __($item[$field], [], $locale);
                }
            }
        }

        return $list;
    }

    /**
     * Swap every Website Content field (and the dirty snapshot) to another
     * language without a round-trip to the server.
     */
    public function switchMarketingLocale(string $locale): void
    {
        if (! in_array($locale, array_keys(supported_locales()), true)) {
            $locale = 'en';
        }

        $this->marketingLocale = $locale;

        $updates = ['marketing_language' => $locale];
        foreach ($this->marketingScalarKeys() as $key) {
            $updates['marketing_'.$key] = $this->marketingDisplayValue($key, $locale);
        }
        foreach ($this->marketingListKeys() as $key) {
            $updates['marketing_'.$key] = $this->marketingDisplayList($key, $locale);
        }

        foreach ($updates as $compoundKey => $value) {
            $this->data[$compoundKey] = $value;
        }

        $this->marketingSnapshot = $updates;
    }

    /**
     * Remember the displayed marketing state so save() can skip untouched
     * fields.
     */
    protected function snapshotMarketingState(): void
    {
        $snapshot = [];

        foreach ($this->data ?? [] as $compoundKey => $value) {
            if (! is_string($compoundKey) || ! str_starts_with($compoundKey, 'marketing_')) {
                continue;
            }

            // FileUpload state is a (uuid-keyed) array of stored paths; the
            // dirty check compares against the dehydrated scalar, so store
            // just the effective value here.
            if ($this->isImageUploadKey($compoundKey) && is_array($value)) {
                $value = array_values($value)[0] ?? null;
            }

            $snapshot[$compoundKey] = $value;
        }

        $this->marketingSnapshot = $snapshot;
    }

    /**
     * True when the current form state differs from the displayed snapshot.
     */
    protected function marketingValueIsDirty(string $compoundKey, mixed $current): bool
    {
        if (! array_key_exists($compoundKey, $this->marketingSnapshot)) {
            return true;
        }

        return ! $this->marketingLooseEquals($current, $this->marketingSnapshot[$compoundKey]);
    }

    /**
     * Order-insensitive comparison for dirty checks: repeater items are
     * matched by field name (schema order may differ from defaults order),
     * lists are compared positionally (so reordering counts as an edit), and
     * uuid-keyed repeater state is matched against its snapshot positionally.
     */
    protected function marketingLooseEquals(mixed $a, mixed $b): bool
    {
        // Empty FileUpload state arrives as null on one side and [] on the other.
        if (($a === null && $b === []) || ($b === null && $a === [])) {
            return true;
        }

        if (is_array($a) && is_array($b)) {
            $bothList = array_is_list($a) && array_is_list($b);
            $bothAssoc = ! $bothList && ! array_is_list($a) && ! array_is_list($b);

            if ($bothAssoc) {
                // $a is the current state, $b the displayed snapshot. Matched
                // by key so field order inside a repeater item does not matter.
                // Snapshot-only keys are the hidden structural fields (icon/key)
                // that getState() drops — ignore them. A key that only appears
                // in the current state counts as dirty only when it carries a
                // value.
                foreach ($a as $key => $stateValue) {
                    if (! array_key_exists($key, $b)) {
                        if ($stateValue !== null && $stateValue !== '' && $stateValue !== []) {
                            return false;
                        }

                        continue;
                    }

                    if (! $this->marketingLooseEquals($stateValue, $b[$key])) {
                        return false;
                    }
                }

                return true;
            }

            if (count($a) !== count($b)) {
                return false;
            }

            $a = array_values($a);
            $b = array_values($b);

            foreach ($a as $i => $item) {
                if (! $this->marketingLooseEquals($item, $b[$i])) {
                    return false;
                }
            }

            return true;
        }

        if (is_array($a) !== is_array($b)) {
            return false;
        }

        return $a == $b;
    }

    /** Recursively remove any '*_upload' scratch keys from persisted state. */
    protected static function stripUploadKeys(array $data): array
    {
        foreach ($data as $k => &$v) {
            if (is_string($k) && str_ends_with($k, '_upload')) {
                unset($data[$k]);

                continue;
            }
            if (is_array($v)) {
                $v = self::stripUploadKeys($v);
            }
        }
        unset($v);

        return $data;
    }

    protected function isImageUploadKey(string $key): bool
    {
        $imageKeys = [
            'branding_platform_logo',
            'branding_platform_favicon',
            'email_logo_path',
            'marketing_nav_logo',
            'marketing_footer_logo',
            'marketing_hero_image',
            'marketing_about_image',
            'marketing_contact_image',
        ];

        return in_array($key, $imageKeys, true);
    }

    /**
     * Wrap any FileUpload state that leaked through as a string into an array
     * (top level and inside repeaters — feature-card images dehydrate to a
     * plain path string on every save and would otherwise reach Filament's
     * validation/dehydration as a string).
     *
     * @param  array<string, mixed>  $data
     */
    protected function normaliseFileUploadState(array &$data): void
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $this->normaliseFileUploadState($value);

                continue;
            }

            $isFileState = is_string($key) && (
                str_ends_with($key, '_upload')
                || $this->isImageUploadKey($key)
                || $key === 'image' // feature-card FileUpload inside the repeater
            );

            if (is_string($value) && $isFileState) {
                $value = $value === '' ? null : [$value];
            }
        }
        unset($value);
    }

    public function getFormUploadedFiles(string $statePath): ?array
    {
        // The file-upload blade asks the page for this field's stored-file
        // info before every upload/save. A field hydrated from the database
        // (or echoed back after a dehydrating save) holds a plain string
        // path, but Filament iterates file state as a uuid-keyed array —
        // wrap the string here for whatever FileUpload path was requested
        // (hero, logos, feature-card images) so the walk cannot foreach a
        // string and 500. Mutating $this->data is enough: component state
        // is read straight from the Livewire property, and skipping fill()
        // keeps other fields' fresh TemporaryUploadedFile state intact.
        $state = data_get($this, $statePath);

        if (is_string($state)) {
            data_set($this, $statePath, $state === '' ? null : [(string) Str::uuid() => $state]);
        }

        return parent::getFormUploadedFiles($statePath);
    }

    /**
     * Fold every "*_upload" field into its target field (top level and inside
     * repeaters): an upload replaces the URL/path value being edited.
     *
     * @param  array<string, mixed>  $data
     */
    /**
     * Standard single-line website-content text field.
     */
    protected function mText(string $key, string $label, ?string $hint = null, bool $full = false): TextInput
    {
        $field = TextInput::make('marketing_'.$key)->label($label);

        if ($hint !== null) {
            $field->helperText($hint);
        }

        if ($full) {
            $field->columnSpanFull();
        }

        return $field;
    }

    /**
     * Website-content media field: photos, GIFs and short videos are all
     * accepted; uploads are converted to WebP in the background (see
     * ConvertWebsiteMediaToWebp) so static images become animations. A
     * "Video trim start" lets a longer source video play a chosen 8-second
     * window instead of having to be pre-cut to 8 seconds.
     *
     * @return array<int, FileUpload|TextInput>
     */
    protected function mImage(string $key, string $label): array
    {
        $trimKey = 'marketing_'.$key.'_trim_start';

        return [
            FileUpload::make('marketing_'.$key)
                ->label($label)
                ->disk('public')
                ->directory('platform/website')
                ->acceptedFileTypes(self::WEBSITE_MEDIA_MIME_TYPES)
                ->maxSize(51200)
                ->rules(fn (Get $get) => [
                    new MaxVideoDuration(8, (float) ($get($trimKey) ?? 0)),
                ])
                ->helperText(fn () => __('Image, GIF or a video. Videos play as an 8-second clip (start point set below) converted to WebP in the background, animated, at the Animation frame rate. Current: :value', [
                    'value' => $this->marketingDisplayValue($key, $this->marketingLocale) ?: __('(bundled default)'),
                ]))
                ->columnSpanFull(),

            TextInput::make($trimKey)
                ->label(__('Video trim start (seconds)'))
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->helperText(__('Only used for videos: the animated clip is 8 seconds long, starting at this point (e.g. 5 keeps seconds 5–13 of a longer video). Ignored for photos.'))
                ->columnSpanFull(),
        ];
    }
}
