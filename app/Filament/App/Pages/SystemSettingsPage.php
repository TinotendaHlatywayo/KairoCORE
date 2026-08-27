<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ManagesEmailConfiguration;
use App\Filament\App\Resources\SchoolBankAccountResource;
use App\Models\School;
use App\Models\User;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Modules\Admin\Models\SystemSetting;
use Modules\Admin\Services\AuditLogger;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Finance\Models\SchoolBankAccount;

class SystemSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;
    use ManagesEmailConfiguration;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'System Administration';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    protected static ?string $navigationLabel = 'System Settings';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 1;

    protected static string $view = 'modules.saas.platform-settings';

    public ?array $data = [];

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return PermissionRegistry::checkPermission('administration.manage_settings');
    }

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        $schoolId = session('current_tenant')?->id ?? ($user ? $user->school_id : null);

        $state = [];
        if ($schoolId) {
            $settings = SystemSetting::where('school_id', $schoolId)->get();
            foreach ($settings as $setting) {
                $state[$setting->group.'_'.$setting->key] = json_decode($setting->value, true) ?? $setting->value;
            }

            if (empty($state['legal_terms_content'])) {
                $state['legal_terms_content'] = default_school_terms();
            }
            if (empty($state['banking_banks']) && ! empty($state['banking_bank_name'])) {
                $state['banking_banks'] = [[
                    'bank_name' => $state['banking_bank_name'] ?? '',
                    'account_number' => $state['banking_account_number'] ?? '',
                    'branch_code' => $state['banking_branch_code'] ?? '',
                ]];
            }

            // Ensure every module master toggle defaults to ENABLED when no
            // persisted value exists (new schools start with all modules on),
            // and normalise persisted values to strict booleans. This guarantees
            // the Manage Modules panel never shows every module switched off
            // simply because a tenant record has no stored module preferences.
            $moduleToggles = [
                'modules_admissions', 'modules_students', 'modules_academics', 'modules_exams',
                'modules_attendance', 'modules_hr', 'modules_boarding', 'modules_clinic',
                'modules_library', 'modules_inventory', 'modules_finance', 'modules_communication',
                'modules_website', 'modules_lms', 'modules_knowledge', 'modules_reports',
                'modules_administration', 'modules_saas',
            ];
            foreach ($moduleToggles as $field) {
                if (! array_key_exists($field, $state)) {
                    $state[$field] = true;
                } elseif (is_string($state[$field])) {
                    $state[$field] = filter_var($state[$field], FILTER_VALIDATE_BOOLEAN);
                }
            }
        }

        $this->fillEmailConfigurationState($state);

        $this->form->fill($state);
    }

    /**
     * Master Font Catalog config containing clean styling structures.
     */
    public function getFontCatalog(): array
    {
        return [
            // Sans-Serif (Block Style)
            'inter' => ['label' => __('Inter Sans (Clean Tech)'), 'css' => '"Inter", sans-serif', 'import' => 'Inter:wght@400;700'],
            'roboto' => ['label' => __('Roboto Sans (Highly Structured)'), 'css' => '"Roboto", sans-serif', 'import' => 'Roboto:wght@400;700'],
            'plus_jakarta' => ['label' => __('Plus Jakarta Sans (Modern Editorial)'), 'css' => '"Plus Jakarta Sans", sans-serif', 'import' => 'Plus+Jakarta+Sans:wght@400;700'],
            'outfit' => ['label' => __('Outfit Sans (Contemporary Soft)'), 'css' => '"Outfit", sans-serif', 'import' => 'Outfit:wght@400;700'],
            'montserrat' => ['label' => __('Montserrat (Avant Garde Block)'), 'css' => '"Montserrat", sans-serif', 'import' => 'Montserrat:wght@400;700'],
            'poppins' => ['label' => __('Poppins (Soft Modern)'), 'css' => '"Poppins", sans-serif', 'import' => 'Poppins:wght@400;700'],
            'lexend' => ['label' => __('Lexend (Academic Legibility)'), 'css' => '"Lexend", sans-serif', 'import' => 'Lexend:wght@400;600'],
            'nunito' => ['label' => __('Nunito (Rounded Sans)'), 'css' => '"Nunito", sans-serif', 'import' => 'Nunito:wght@400;700'],
            'oswald' => ['label' => __('Oswald (Impact Block Sans)'), 'css' => '"Oswald", sans-serif', 'import' => 'Oswald:wght@400;700'],
            'syncopate' => ['label' => __('Syncopate (Wide Tech Sans)'), 'css' => '"Syncopate", sans-serif', 'import' => 'Syncopate:wght@400;700'],

            // Serif / Decorative
            'playfair' => ['label' => __('Playfair Display (Ivy League Serif)'), 'css' => '"Playfair Display", serif', 'import' => 'Playfair+Display:ital,wght@0,400;0,700'],
            'merriweather' => ['label' => __('Merriweather (Classic Editorial Serif)'), 'css' => '"Merriweather", serif', 'import' => 'Merriweather:wght@400;700'],
            'lora' => ['label' => __('Lora (Contemporary University Serif)'), 'css' => '"Lora", serif', 'import' => 'Lora:wght@400;500;700'],
            'cinzel' => ['label' => __('Cinzel (Albertus Roman Engraved)'), 'css' => '"Cinzel", serif', 'import' => 'Cinzel:wght@400;700'],
            'crimson' => ['label' => __('Crimson Text (Traditional Times Roman)'), 'css' => '"Crimson Text", serif', 'import' => 'Crimson+Text:wght@400;700'],
            'eb_garamond' => ['label' => __('EB Garamond (Caslon / Baskerville Serif)'), 'css' => '"EB Garamond", serif', 'import' => 'EB+Garamond:wght@400;700'],
            'arvo' => ['label' => __('Arvo (Slab-Serif / Rockwell Style)'), 'css' => '"Arvo", serif', 'import' => 'Arvo:wght@400;700'],
            'pt_serif' => ['label' => __('PT Serif (Elegant Corporate Serif)'), 'css' => '"PT Serif", serif', 'import' => 'PT+Serif:wght@400;700'],
            'libre_baskerville' => ['label' => __('Libre Baskerville (Bookman / Caslon)'), 'css' => '"Libre Baskerville", serif', 'import' => 'Libre+Baskerville:wght@400;700'],
            'bodoni_moda' => ['label' => __('Bodoni Moda (Bodoni High-Contrast Serif)'), 'css' => '"Bodoni Moda", serif', 'import' => 'Bodoni+Moda:wght@400;700'],

            // Script & Handwriting
            'great_vibes' => ['label' => __('Great Vibes (Amaze Elegant Script)'), 'css' => '"Great Vibes", cursive', 'import' => 'Great+Vibes'],
            'dancing_script' => ['label' => __('Dancing Script (Casual Monoline Script)'), 'css' => '"Dancing Script", cursive', 'import' => 'Dancing+Script:wght@400;700'],
            'sacramento' => ['label' => __('Sacramento (Kauffman Thin Script)'), 'css' => '"Sacramento", cursive', 'import' => 'Sacramento'],
            'alex_brush' => ['label' => __('Alex Brush (Fluid Brush Script)'), 'css' => '"Alex Brush", cursive', 'import' => 'Alex+Brush'],
            'satisfy' => ['label' => __('Satisfy (Brisk / Pepita Script)'), 'css' => '"Satisfy", cursive', 'import' => 'Satisfy'],
            'parisienne' => ['label' => __('Parisienne (Balmoral Vintage Script)'), 'css' => '"Parisienne", cursive', 'import' => 'Parisienne'],
            'allura' => ['label' => __('Allura (Kauffman Fine Calligraphy)'), 'css' => '"Allura", cursive', 'import' => 'Allura'],
            'monsieur' => ['label' => __('Ornate Balmoral (Monsieur La Doulaise)'), 'css' => '"Monsieur La Doulaise", cursive', 'import' => 'Monsieur+La+Doulaise'],
            'cookie' => ['label' => __('Cookie (Zapf Chancery Script)'), 'css' => '"Cookie", cursive', 'import' => 'Cookie'],
            'marck_script' => ['label' => __('Marck Script (Reporter Expressive Script)'), 'css' => '"Marck Script", cursive', 'import' => 'Marck+Script'],

            // Novelty / Decorative / Unique
            'unifraktur_maguntia' => ['label' => __('Unifraktur Maguntia (Old English Gothic)'), 'css' => '"UnifrakturMaguntia", serif', 'import' => 'UnifrakturMaguntia'],
            'courier_prime' => ['label' => __('Classic Typewriter (Courier Prime)'), 'css' => '"Courier Prime", monospace', 'import' => 'Courier+Prime:wght@400;700'],
            'special_elite' => ['label' => __('Grungy Typewriter (Special Elite)'), 'css' => '"Special Elite", display', 'import' => 'Special+Elite'],
            'caveat' => ['label' => __('Chalk Dust Handwriting (Caveat)'), 'css' => '"Caveat", cursive', 'import' => 'Caveat:wght@400;700'],
            'architects_daughter' => ['label' => __('Tekton Pencil (Architects)'), 'css' => '"Architects Daughter", cursive', 'import' => 'Architects+Daughter'],
            'fredericka' => ['label' => __('Sketchy Chalk Dust (Fredericka)'), 'css' => '"Fredericka the Great", display', 'import' => 'Fredericka+the+Great'],
            'bungee' => ['label' => __('Aachen Block Inline (Bungee Block)'), 'css' => '"Bungee", sans-serif', 'import' => 'Bungee'],
            'permanent_marker' => ['label' => __('Reporter Bold Brush (Marker)'), 'css' => '"Permanent Marker", cursive', 'import' => 'Permanent+Marker'],
            'creepster' => ['label' => __('Reporter Novelty Gothic (Creepster)'), 'css' => '"Creepster", display', 'import' => 'Creepster'],
            'rye' => ['label' => __('Rye (Wild West Decorative)'), 'css' => '"Rye", display', 'import' => 'Rye'],
        ];
    }

    /**
     * Helper to retrieve styled HTML options for the font dropdown.
     */
    protected function getFontDropdownOptions(): array
    {
        $fonts = $this->getFontCatalog();
        $options = [];
        foreach ($fonts as $key => $data) {
            // The CSS value contains double quotes (e.g. "Inter", sans-serif),
            // which would break the HTML style attribute. Encode them so the
            // option label actually renders in the chosen font.
            $css = htmlspecialchars($data['css'], ENT_QUOTES, 'UTF-8');
            $options[$key] = "<span style=\"font-family: {$css} !important; display: inline-block;\">{$data['label']}</span>";
        }

        return $options;
    }

    public function form(Form $form): Form
    {
        $fontsJson = json_encode($this->getFontCatalog());

        return $form
            ->schema([
                Tabs::make('SettingsCategories')
                    ->tabs([
                        Tab::make(__('Design & Branding'))
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Section::make(__('Theme & Typography'))
                                    ->schema([
                                        Select::make('branding_theme')
                                            ->label(__('Visual Theme Design'))
                                            ->options([
                                                'emerald_heritage' => __('MoPSE Emerald Heritage (Standard / Classic)'),
                                                'digital_cobalt' => __('Digital Cobalt (Sleek Modern Digital / Blue)'),
                                                'obsidian_gold' => __('Obsidian Charcoal (Premium Academic / Black)'),
                                                'crimson_academy' => __('Crimson Academy (Ivy Crimson & Slate)'),
                                                'ocean_breeze' => __('Ocean Breeze (Calming Teal & Cyan)'),
                                                'forest_pine' => __('Forest Pine (Pine Green & Soft Mint)'),
                                                'sunset_amber' => __('Sunset Amber (Deep Coral & Amber Orange)'),
                                                'royal_purple' => __('Royal Purple (Regal Purple & Lavender)'),
                                                'steel_slate' => __('Steel Slate (High-tech Steel & Cool Blue)'),
                                                'rosewood' => __('Rosewood Mahogany (Warm Rosewood & Peach)'),
                                                'dev_choice_1' => __('Developer\'s Choice 1 (Indigo + Cyan Blend)'),
                                                'dev_choice_2' => __('Developer\'s Choice 2 (Fuchsia + Violet Blend)'),
                                                'dev_choice_3' => __('Developer\'s Choice 3 (Cyan + Emerald Blend)'),
                                                'dev_choice_4' => __('Developer\'s Choice 4 (Midnight Navy + Flame Blend)'),
                                            ])->default('emerald_heritage'),
                                        Select::make('branding_font_family')
                                            ->label(__('System Typography Font'))
                                            ->options($this->getFontDropdownOptions())
                                            ->allowHtml()
                                            ->default('inter')
                                            ->live(),

                                        /* -----------------------------------------------------------------
                                         * TYPOGRAPHY PREVIEW BLOCKS - COMMENTED OUT
                                         * -----------------------------------------------------------------
                                        Placeholder::make('branding_font_preview')
                                            ->label(__('Typography Visual Preview'))
                                            ->content(function ($get) use ($fontsJson) {
                                                $fontFamily = $get('branding_font_family') ?? 'inter';
                                                $fonts = $this->getFontCatalog();
                                                $fontSelected = $fonts[$fontFamily] ?? $fonts['inter'];
                                                $css = $fontSelected['css'];
                                                $importUrl = 'https://fonts.googleapis.com/css2?family=' . $fontSelected['import'] . '&display=swap';

                                                return new HtmlString("
                                                    <link id='dynamic-preview-placeholder-sheet-{$fontFamily}' rel='stylesheet' href='{$importUrl}'>
                                                    <div class='mt-2 rounded-xl border border-gray-100 p-4 bg-gray-50/50 dark:border-gray-800 dark:bg-gray-950/20'>
                                                        <div style='font-family: {$css} !important;' class='space-y-2'>
                                                            <p class='text-2xl font-bold text-gray-900 dark:text-white leading-tight'>The quick brown fox jumps over the lazy dog.</p>
                                                            <p class='text-sm text-gray-500 dark:text-gray-400 mt-2'>0123456789 (A B C D E F G H I J K L M N O P Q R S T U V W X Y Z)</p>
                                                        </div>
                                                    </div>
                                                ");
                                            })
                                            ->columnSpanFull(),
                                         * ----------------------------------------------------------------- */
                                    ])->columns(2),

                                Section::make(__('School Logo'))
                                    ->schema([
                                        FileUpload::make('branding_logo_path')
                                            ->label(__('Logo Badge Upload'))
                                            ->directory('tenant/branding')
                                            ->image()
                                            ->avatar()
                                            ->imageEditor()
                                            ->imageEditorAspectRatios(['1:1']),
                                        FileUpload::make('branding_favicon_path')
                                            ->label(__('Browser Tab Favicon'))
                                            ->directory('tenant/branding')
                                            ->image()
                                            ->imageEditor()
                                            ->maxSize(1024)
                                            ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/svg+xml'])
                                            ->helperText(__('Small icon shown on the browser tab in Chrome and other browsers. Square PNG or ICO, 64×64px or larger recommended. Falls back to the platform favicon when empty.')),
                                        Select::make('branding_logo_height')
                                            ->label(__('Logo Badge Scale Height'))
                                            ->options([
                                                '24px' => __('Compact (24px)'),
                                                '32px' => __('Standard (32px)'),
                                                '48px' => __('Large (48px)'),
                                                '60px' => __('Maxi (60px)'),
                                            ])->default('32px'),
                                        Select::make('branding_logo_opacity')
                                            ->label(__('Logo Header Opacity'))
                                            ->options([
                                                '0.5' => __('Faded (50%)'),
                                                '0.8' => __('Soft (80%)'),
                                                '1.0' => __('Opaque (100%)'),
                                            ])->default('1.0'),
                                    ])->columns(3),

                                Section::make(__('Dashboard Watermark'))
                                    ->schema([
                                        FileUpload::make('branding_bg_path')
                                            ->label(__('Background Wallpaper'))
                                            ->directory('tenant/branding')
                                            ->image()
                                            ->imageEditor()
                                            ->imageEditorAspectRatios(['16:9', '4:3']),
                                        Select::make('branding_background_opacity')
                                            ->label(__('Wallpaper Opacity'))
                                            ->options([
                                                '0.02' => __('Subtle (2%)'),
                                                '0.08' => __('Soft (8%)'),
                                                '0.15' => __('Defined (15%)'),
                                                '0.25' => __('Bold (25%)'),
                                                '0.35' => __('Heavy (35%)'),
                                            ])->default('0.08'),
                                        Select::make('branding_background_scaling')
                                            ->label(__('Wallpaper Scale'))
                                            ->options([
                                                'cover' => __('Fill Canvas (Cover)'),
                                                'contain' => __('Keep Image Scale (Contain)'),
                                            ])->default('cover'),
                                    ])->columns(3),
                            ]),

                        // Tab 2: Dynamic System Module Switcher (Hides or shows sidebar entries dynamically) [1]
                        Tab::make(__('Manage Modules'))
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Placeholder::make('modules_intro')
                                    ->label(__('Modular System Architecture & Interface Customization'))
                                    ->content(new HtmlString(__('Configure your school interface by enabling or disabling entire modules and their specific sub-pages. Tailor the workspace to your institution (e.g. disable Boarding and Clinic for day schools, or Payroll for government schools where salaries are centrally managed). <em>Hover over any toggle or section for details on its function.</em>')))
                                    ->columnSpanFull(),

                                // One-click enable / disable every module at once.
                                Actions::make([
                                    Action::make('enableAllModules')
                                        ->label(__('Enable All Modules'))
                                        ->icon('heroicon-o-check-circle')
                                        ->color('success')
                                        ->size('sm')
                                        ->tooltip(__('Switch every module master and sub-page toggle on.'))
                                        ->action(fn () => $this->applyAllModuleToggles(true)),
                                    Action::make('disableAllModules')
                                        ->label(__('Disable All Modules'))
                                        ->icon('heroicon-o-x-circle')
                                        ->color('danger')
                                        ->size('sm')
                                        ->tooltip(__('Switch every module master and sub-page toggle off.'))
                                        ->action(fn () => $this->applyAllModuleToggles(false)),
                                ]),

                                // The module matrix is laid out as a responsive 2-column grid so each
                                // module card sits side-by-side with its neighbour. Each card spans
                                // one column; Filament's Grid::make(2) handles the 2-across layout.
                                Grid::make(2)->schema([
                                    // 1. Admissions & Online CRM
                                    Section::make(__('Admissions & Online CRM'))
                                        ->description(__('Manage prospective student applications and intake review pipeline.'))
                                        ->icon('heroicon-o-clipboard-document-list')
                                        ->extraAttributes(['title' => __('Admissions & Online CRM: controls the full admissions pipeline, online application forms, the Kanban intake board, and the admission settings page.')])
                                        ->collapsible()
                                        ->schema([
                                            Toggle::make('modules_admissions')
                                                ->label(__('Enable Admissions Module'))
                                                ->helperText(__('Master toggle: controls visibility of the admissions pipeline and online application forms.'))
                                                ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables the entire Admissions pipeline, online application forms, Kanban board, and settings.'))
                                                ->default(true)
                                                ->live(),
                                            Grid::make(1)->schema([
                                                Toggle::make('modules_admissions_applications')
                                                    ->label(__('Applications List'))
                                                    ->helperText(__('View, review, and process incoming student applications.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_admissions')),
                                                Toggle::make('modules_admissions_kanban')
                                                    ->label(__('Admissions Kanban Pipeline'))
                                                    ->helperText(__('Drag-and-drop Kanban board for tracking applicants through each intake stage.'))
                                                    ->hintIcon('heroicon-o-information-circle', __('Admissions Kanban Pipeline: visual board to move applicants from enquiry to enrolment.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_admissions')),
                                                Toggle::make('modules_admissions_settings')
                                                    ->label(__('Admission Settings'))
                                                    ->helperText(__('Configure intake windows, required documents, and custom questions.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_admissions')),
                                            ]),
                                        ]),

                                    // 2. Students & SIS
                                    Section::make(__('Students Directory'))
                                        ->description(__('Core student profiles, enrollments, and student administration.'))
                                        ->icon('heroicon-o-user-group')
                                        ->extraAttributes(['title' => __('Students Directory: master toggle gates the student body, ID cards, directory records, the promotion workflow, and the guardians/contacts hub.')])
                                        ->collapsible()
                                        ->schema([
                                            Toggle::make('modules_students')
                                                ->label(__('Enable Students Module'))
                                                ->helperText(__('Master toggle: controls visibility of the student body, ID cards, and directory.'))
                                                ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables the Students module, directory & ID cards, promotion workflow, and guardians/contacts hub.'))
                                                ->default(true)
                                                ->live(),
                                            Grid::make(1)->schema([
                                                Toggle::make('modules_students_directory')
                                                    ->label(__('Student Directory & Cards'))
                                                    ->helperText(__('Browse student records, print ID cards, and view individual profiles.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_students')),
                                                Toggle::make('modules_students_guardians')
                                                    ->label(__('Guardians & Contacts Hub'))
                                                    ->helperText(__('Manage parent/guardian contact books, communication preferences, and linked dependants.'))
                                                    ->hintIcon('heroicon-o-information-circle', __('Guardians & Contacts Hub: central contact book for parents and linked dependants.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_students')),
                                                Toggle::make('modules_students_promotion')
                                                    ->label(__('Student Promotion Workflow'))
                                                    ->helperText(__('End-of-year grade advancement and stream assignment wizard.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_students')),
                                            ]),
                                        ]),

                                    // 3. Academics & Curriculum
                                    Section::make(__('Academics & Curriculum'))
                                        ->description(__('Forms/grades, classes/streams, subjects, and academic operations.'))
                                        ->icon('heroicon-o-academic-cap')
                                        ->extraAttributes(['title' => __('Academics & Curriculum: gates courses/forms, classes/streams, the Academic Operations Center readiness board, and the curriculum dashboard.')])
                                        ->collapsible()
                                        ->schema([
                                            Toggle::make('modules_academics')
                                                ->label(__('Enable Academics Module'))
                                                ->helperText(__('Master toggle: controls curriculum structures, class streams, and academic setup.'))
                                                ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables the Academics module, courses/forms, classes/streams, operations center, and curriculum dashboard.'))
                                                ->default(true)
                                                ->live(),
                                            Grid::make(1)->schema([
                                                Toggle::make('modules_academics_courses')
                                                    ->label(__('Courses / Forms Management'))
                                                    ->helperText(__('Configure school forms, grades, and level structures.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_academics')),
                                                Toggle::make('modules_academics_streams')
                                                    ->label(__('Classes / Streams'))
                                                    ->helperText(__('Manage stream divisions (e.g. Form 1A, Form 1B).'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_academics')),
                                                Toggle::make('modules_academics_operations')
                                                    ->label(__('Academic Operations Center'))
                                                    ->helperText(__('Readiness checks, timetable building, and teacher assignments.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_academics')),
                                                Toggle::make('modules_academics_dashboard')
                                                    ->label(__('Curriculum Dashboard'))
                                                    ->helperText(__('High-level overview of form coverage, subject allocations, and timetable readiness.'))
                                                    ->hintIcon('heroicon-o-information-circle', __('Curriculum Dashboard: snapshot of form coverage and timetable readiness.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_academics')),
                                            ]),
                                        ]),

                                    // 4. Exams & Grading
                                    Section::make(__('Exams & Grading'))
                                        ->description(__('Assessments, grading scales, marks entry, and report cards.'))
                                        ->icon('heroicon-o-pencil-square')
                                        ->extraAttributes(['title' => __('Exams & Grading: controls continuous assessment, the mark ledger workspace, report card publishing, and the exams analytics dashboard.')])
                                        ->collapsible()
                                        ->schema([
                                            Toggle::make('modules_exams')
                                                ->label(__('Enable Exams & Grading'))
                                                ->helperText(__('Master toggle: controls continuous assessment, mark ledgers, and report card publishing.'))
                                                ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables the Exams module, assessment workspace, marks ledger, report cards, and analytics dashboard.'))
                                                ->default(true)
                                                ->live(),
                                            Grid::make(1)->schema([
                                                Toggle::make('modules_exams_workspace')
                                                    ->label(__('Assessment Workspace'))
                                                    ->helperText(__('Configure assessment components, weightings, and test plans.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_exams')),
                                                Toggle::make('modules_exams_marks')
                                                    ->label(__('Marks Entry Ledger'))
                                                    ->helperText(__('Record and verify student assessment scores per subject.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_exams')),
                                                Toggle::make('modules_exams_reports')
                                                    ->label(__('Academic Report Cards'))
                                                    ->helperText(__('Generate and sign official termly report cards with Unhu/Ubuntu competencies.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_exams')),
                                                Toggle::make('modules_exams_analytics')
                                                    ->label(__('Exams Analytics & Trends'))
                                                    ->helperText(__('Subject and cohort performance dashboards, pass-rate trends, and item analysis.'))
                                                    ->hintIcon('heroicon-o-information-circle', __('Exams Analytics & Trends: performance dashboards and pass-rate trend reporting.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_exams')),
                                            ]),
                                        ]),

                                    // 5. Attendance
                                    Section::make(__('Attendance Tracking'))
                                        ->description(__('Daily attendance tracking for staff and students.'))
                                        ->icon('heroicon-o-clipboard-document-check')
                                        ->extraAttributes(['title' => __('Attendance Tracking: gates daily staff/student attendance logging and the attendance reports & analytics panel.')])
                                        ->collapsible()
                                        ->schema([
                                            Toggle::make('modules_attendance')
                                                ->label(__('Enable Attendance Module'))
                                                ->helperText(__('Master toggle: controls attendance monitoring and reporting.'))
                                                ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables the Attendance module, staff attendance logging, and reports & analytics.'))
                                                ->default(true)
                                                ->live(),
                                            Grid::make(1)->schema([
                                                Toggle::make('modules_attendance_staff')
                                                    ->label(__('Staff Attendance'))
                                                    ->helperText(__('Log teacher and administrative staff daily attendance.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_attendance')),
                                                Toggle::make('modules_attendance_reports')
                                                    ->label(__('Attendance Reports & Analytics'))
                                                    ->helperText(__('Aggregate attendance statistics, late-arrival trends, and exportable registers.'))
                                                    ->hintIcon('heroicon-o-information-circle', __('Attendance Reports & Analytics: statistics and trend reporting for students and staff.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_attendance')),
                                            ]),
                                        ]),

                                    // 6. HR & Payroll
                                    Section::make(__('HR & Payroll'))
                                        ->description(__('Staff directory, salary grades, loans, and payroll processing.'))
                                        ->icon('heroicon-o-user-plus')
                                        ->extraAttributes(['title' => __('HR & Payroll: controls employee records, staff directory/contracts, payroll & salary grades, and leave/staff loans.')])
                                        ->collapsible()
                                        ->schema([
                                            Toggle::make('modules_hr')
                                                ->label(__('Enable HR & Payroll'))
                                                ->helperText(__('Master toggle: controls employee records, contracts, and salary management. Government schools can toggle off payroll if salaries are centralized.'))
                                                ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables the HR module, staff directory, payroll/salary grades, and leave/staff loans.'))
                                                ->default(true)
                                                ->live(),
                                            Grid::make(1)->schema([
                                                Toggle::make('modules_hr_employees')
                                                    ->label(__('Staff Directory & Contracts'))
                                                    ->helperText(__('Manage employee personal files, designations, and departments.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_hr')),
                                                Toggle::make('modules_hr_payroll')
                                                    ->label(__('Payroll & Salary Grades'))
                                                    ->helperText(__('Manage salary scales, allowances, deductions, and monthly payroll runs.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_hr')),
                                                Toggle::make('modules_hr_leave')
                                                    ->label(__('Leave Requests & Staff Loans'))
                                                    ->helperText(__('Process staff leave applications and salary advance loan requests.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_hr')),
                                                Toggle::make('modules_hr_recruitment')
                                                    ->label(__('Recruitment & Onboarding'))
                                                    ->helperText(__('Job postings, candidate tracking, and new-employee onboarding workflow.'))
                                                    ->hintIcon('heroicon-o-information-circle', __('Recruitment & Onboarding: job postings and candidate tracking pipeline.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_hr')),
                                            ]),
                                        ]),

                                    // 7. Boarding & Welfare
                                    Section::make(__('Boarding & Welfare'))
                                        ->description(__('Hostel dormitories, bed allocations, and outpass management.'))
                                        ->icon('heroicon-o-home-modern')
                                        ->extraAttributes(['title' => __('Boarding & Welfare: gates bed allocations and gate outpasses/visitor management for boarding schools.')])
                                        ->collapsible()
                                        ->schema([
                                            Toggle::make('modules_boarding')
                                                ->label(__('Enable Boarding Module'))
                                                ->helperText(__('Master toggle: ideal for boarding schools. Day schools can switch this off to keep their interface clean.'))
                                                ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables the Boarding module, bed allocations, outpasses, and hostel dashboard.'))
                                                ->default(true)
                                                ->live(),
                                            Grid::make(1)->schema([
                                                Toggle::make('modules_boarding_allocations')
                                                    ->label(__('Bed Allocations'))
                                                    ->helperText(__('Manage student hostel rooms, beds, and termly boarding check-ins.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_boarding')),
                                                Toggle::make('modules_boarding_outpasses')
                                                    ->label(__('Gate Outpasses & Visitors'))
                                                    ->helperText(__('Monitor exeats, exeat letters, visitor logs, and gate security passes.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_boarding')),
                                                Toggle::make('modules_boarding_hostel')
                                                    ->label(__('Hostel Overview Dashboard'))
                                                    ->helperText(__('Real-time occupancy, pending checkouts, and visitor arrival summaries.'))
                                                    ->hintIcon('heroicon-o-information-circle', __('Hostel Overview Dashboard: occupancy and visitor summaries.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_boarding')),
                                            ]),
                                        ]),

                                    // 8. Health & Safety (Clinic)
                                    Section::make(__('Health & Safety (Clinic)'))
                                        ->description(__('Sanitarium dispensary log and student medical history.'))
                                        ->icon('heroicon-o-heart')
                                        ->extraAttributes(['title' => __('Health & Safety (Clinic): gates clinic visit logs and the student medical records hub.')])
                                        ->collapsible()
                                        ->schema([
                                            Toggle::make('modules_clinic')
                                                ->label(__('Enable Clinic Module'))
                                                ->helperText(__('Master toggle: school health center log. Day schools without a resident nurse can switch this off.'))
                                                ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables the Clinic module, visit logs, and medical records hub.'))
                                                ->default(true)
                                                ->live(),
                                            Grid::make(1)->schema([
                                                Toggle::make('modules_clinic_visits')
                                                    ->label(__('Clinic Visit Logs'))
                                                    ->helperText(__('Record student complaints, treatments, administered medicines, and sickbay admissions.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_clinic')),
                                                Toggle::make('modules_clinic_medical_records')
                                                    ->label(__('Student Medical Records'))
                                                    ->helperText(__('Store immunisation histories, chronic conditions, and medical certificates.'))
                                                    ->hintIcon('heroicon-o-information-circle', __('Student Medical Records: immunisation histories and chronic conditions.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_clinic')),
                                            ]),
                                        ]),

                                    // 9. Library Circulation
                                    Section::make(__('Library Circulation'))
                                        ->description(__('Book catalog, barcode scanning, borrowing, and returns.'))
                                        ->icon('heroicon-o-book-open')
                                        ->extraAttributes(['title' => __('Library Circulation: controls book catalog & copies and the circulation/issues desk.')])
                                        ->collapsible()
                                        ->schema([
                                            Toggle::make('modules_library')
                                                ->label(__('Enable Library Module'))
                                                ->helperText(__('Master toggle: controls library book inventory, circulation desk, and overdue fines.'))
                                                ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables the Library module, book catalog, issues desk, and e-resources.'))
                                                ->default(true)
                                                ->live(),
                                            Grid::make(1)->schema([
                                                Toggle::make('modules_library_books')
                                                    ->label(__('Book Catalog & Copies'))
                                                    ->helperText(__('Manage library book titles, ISBNs, authors, and physical copy barcodes.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_library')),
                                                Toggle::make('modules_library_issues')
                                                    ->label(__('Circulation & Issues Desk'))
                                                    ->helperText(__('Issue books to students/staff, process returns, and track fines.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_library')),
                                                Toggle::make('modules_library_eresources')
                                                    ->label(__('E-Resources & Digital Library'))
                                                    ->helperText(__('Online journals, e-books, and digital media catalogue with access logs.'))
                                                    ->hintIcon('heroicon-o-information-circle', __('E-Resources & Digital Library: online journals and digital media catalogue.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_library')),
                                            ]),
                                        ]),

                                    // 10. Inventory & Procurement
                                    Section::make(__('Inventory & Procurement'))
                                        ->description(__('Stock inventory items, fixed assets, purchase orders, and GRNs.'))
                                        ->icon('heroicon-o-archive-box')
                                        ->extraAttributes(['title' => __('Inventory & Procurement: gates stock inventory, fixed assets/depreciation, and procurement/purchase orders.')])
                                        ->collapsible()
                                        ->schema([
                                            Toggle::make('modules_inventory')
                                                ->label(__('Enable Inventory Module'))
                                                ->helperText(__('Master toggle: controls school assets, stationery stock, and supplier procurement workflow.'))
                                                ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables the Inventory module, stock items, fixed assets, and procurement.'))
                                                ->default(true)
                                                ->live(),
                                            Grid::make(1)->schema([
                                                Toggle::make('modules_inventory_items')
                                                    ->label(__('Stock Inventory & Items'))
                                                    ->helperText(__('Manage consumable and returnable store stock items.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_inventory')),
                                                Toggle::make('modules_inventory_assets')
                                                    ->label(__('Fixed Assets & Depreciation'))
                                                    ->helperText(__('Track school equipment, furniture, vehicles, and maintenance logs.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_inventory')),
                                                Toggle::make('modules_inventory_procurement')
                                                    ->label(__('Procurement & Purchase Orders'))
                                                    ->helperText(__('Manage purchase requests, purchase orders, and goods received notes.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_inventory')),
                                                Toggle::make('modules_inventory_suppliers')
                                                    ->label(__('Suppliers & Vendors'))
                                                    ->helperText(__('Supplier master list, rating, and preferred-vendor management.'))
                                                    ->hintIcon('heroicon-o-information-circle', __('Suppliers & Vendors: supplier master list and rating management.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_inventory')),
                                            ]),
                                        ]),

                                    // 11. Finance & Accounting
                                    Section::make(__('Finance & Accounting'))
                                        ->description(__('Fee structures, invoicing, double-entry general ledger, and expenses.'))
                                        ->icon('heroicon-o-banknotes')
                                        ->extraAttributes(['title' => __('Finance & Accounting: controls fee invoicing, the double-entry ledger, expense management, and financial statements.')])
                                        ->collapsible()
                                        ->schema([
                                            Toggle::make('modules_finance')
                                                ->label(__('Enable Finance Module'))
                                                ->helperText(__('Master toggle: controls fee collection, bank accounts, accounting journal entries, and financial reports.'))
                                                ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables Finance, invoicing, ledger, expenses, and financial statements.'))
                                                ->default(true)
                                                ->live(),
                                            Grid::make(1)->schema([
                                                Toggle::make('modules_finance_invoices')
                                                    ->label(__('Invoicing & Fee Billing'))
                                                    ->helperText(__('Generate termly fee invoices, record payments, and issue receipts.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_finance')),
                                                Toggle::make('modules_finance_ledgers')
                                                    ->label(__('Double-Entry General Ledger'))
                                                    ->helperText(__('Manage Chart of Accounts and balanced journal entries.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_finance')),
                                                Toggle::make('modules_finance_expenses')
                                                    ->label(__('Expense Management'))
                                                    ->helperText(__('Track institutional expenditures and supplier bills.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_finance')),
                                                Toggle::make('modules_finance_reports')
                                                    ->label(__('Financial Statements & Reports'))
                                                    ->helperText(__('Trial balance, income statement, and cash-flow reports with export.'))
                                                    ->hintIcon('heroicon-o-information-circle', __('Financial Statements & Reports: trial balance and income statement reporting.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_finance')),
                                            ]),
                                        ]),

                                    // 12. Communication & Helpdesk
                                    Section::make(__('Communication Center'))
                                        ->description(__('Notice boards, internal messaging, and support helpdesks.'))
                                        ->icon('heroicon-o-chat-bubble-left-right')
                                        ->extraAttributes(['title' => __('Communication Center: gates announcements/notice board and the support helpdesk ticketing system.')])
                                        ->collapsible()
                                        ->schema([
                                            Toggle::make('modules_communication')
                                                ->label(__('Enable Communication Module'))
                                                ->helperText(__('Master toggle: controls broadcast announcements, notice boards, and helpdesk tickets.'))
                                                ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables Communication, announcements, notice board, and helpdesk tickets.'))
                                                ->default(true)
                                                ->live(),
                                            Grid::make(1)->schema([
                                                Toggle::make('modules_communication_notices')
                                                    ->label(__('Announcements & Notice Board'))
                                                    ->helperText(__('Publish school-wide announcements for teachers, parents, and students.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_communication')),
                                                Toggle::make('modules_communication_helpdesk')
                                                    ->label(__('Support Helpdesk Tickets'))
                                                    ->helperText(__('Internal helpdesk ticketing system for staff and IT support.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_communication')),
                                                Toggle::make('modules_communication_messaging')
                                                    ->label(__('Messaging & Broadcasts'))
                                                    ->helperText(__('Targeted SMS/email broadcasts and staff-to-staff instant messaging.'))
                                                    ->hintIcon('heroicon-o-information-circle', __('Messaging & Broadcasts: targeted SMS/email broadcasts and staff messaging.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_communication')),
                                            ]),
                                        ]),

                                    // 13. Visual Website & CMS
                                    Section::make(__('Visual Website & CMS'))
                                        ->description(__('Public-facing website, landing page builder, and SEO.'))
                                        ->icon('heroicon-o-globe-alt')
                                        ->extraAttributes(['title' => __('Visual Website & CMS: controls the public portal pages and the visual drag-and-drop CMS page builder.')])
                                        ->collapsible()
                                        ->schema([
                                            Toggle::make('modules_website')
                                                ->label(__('Enable Website & CMS Module'))
                                                ->helperText(__('Master toggle: controls public portal pages, news, and visual drag-and-drop website builder.'))
                                                ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables the Website & CMS module, page builder, news, and SEO.'))
                                                ->default(true)
                                                ->live(),
                                            Grid::make(1)->schema([
                                                Toggle::make('modules_website_builder')
                                                    ->label(__('Visual CMS Builder'))
                                                    ->helperText(__('Drag-and-drop page builder for home, about, admissions, and contact pages.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_website')),
                                                Toggle::make('modules_website_news')
                                                    ->label(__('News & Announcements Page'))
                                                    ->helperText(__('Manage the public news feed and curriculum announcements shown on the school portal.'))
                                                    ->hintIcon('heroicon-o-information-circle', __('News & Announcements Page: public news feed for the school portal.'))
                                                    ->default(true)
                                                    ->visible(fn ($get) => $get('modules_website')),
                                            ]),
                                            // 14. LMS
                                            Section::make(__('LMS'))
                                                ->description(__('Homework and online learning activities.'))
                                                ->icon('heroicon-o-play-circle')
                                                ->extraAttributes(['title' => __('LMS: gates homework & lessons, online learning activities, and submissions.')])
                                                ->collapsible()
                                                ->schema([
                                                    Toggle::make('modules_lms')
                                                        ->label(__('Enable LMS Module'))
                                                        ->helperText(__('Master toggle: controls homework & lessons and online learning activities.'))
                                                        ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables the LMS module, homework & lessons, and submissions.'))
                                                        ->default(true)
                                                        ->live(),
                                                ]),

                                            // 15. Knowledge Hub
                                            Section::make(__('Knowledge Hub'))
                                                ->description(__('Curated knowledge assets and galleries.'))
                                                ->icon('heroicon-o-light-bulb')
                                                ->extraAttributes(['title' => __('Knowledge Hub: gates the school repository, knowledge assets, and galleries.')])
                                                ->collapsible()
                                                ->schema([
                                                    Toggle::make('modules_knowledge')
                                                        ->label(__('Enable Knowledge Hub Module'))
                                                        ->helperText(__('Master toggle: controls the school repository, knowledge assets, and galleries.'))
                                                        ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables the Knowledge Hub module, school repository, assets, and galleries.'))
                                                        ->default(true)
                                                        ->live(),
                                                ]),

                                            // 16. Reports & Intelligence
                                            Section::make(__('Reports & Intelligence'))
                                                ->description(__('Report generator, templates, generated reports, and analytics.'))
                                                ->icon('heroicon-o-chart-bar')
                                                ->extraAttributes(['title' => __('Reports & Intelligence: gates the reporting dashboard, report generator, templates, generated reports, and analytics explorer.')])
                                                ->collapsible()
                                                ->schema([
                                                    Toggle::make('modules_reports')
                                                        ->label(__('Enable Reports & Intelligence Module'))
                                                        ->helperText(__('Master toggle: controls the reporting dashboard, report generator, templates, and analytics explorer.'))
                                                        ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables Reports & Intelligence, the dashboard, generator, templates, and analytics.'))
                                                        ->default(true)
                                                        ->live(),
                                                ]),

                                            // 17. System Administration
                                            Section::make(__('System Administration'))
                                                ->description(__('Settings, roles, departments, users, and audit trails.'))
                                                ->icon('heroicon-o-wrench-screwdriver')
                                                ->extraAttributes(['title' => __('System Administration: gates the admin overview, user accounts, roles, departments, and audit log. System Settings stays reachable by direct URL.')])
                                                ->collapsible()
                                                ->schema([
                                                    Toggle::make('modules_administration')
                                                        ->label(__('Enable System Administration Module'))
                                                        ->helperText(__('Master toggle: controls the admin overview, user accounts, roles, departments, and data export. System Settings remains reachable by URL.'))
                                                        ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables System Administration, user accounts, roles, departments, audit log, and data export.'))
                                                        ->default(true)
                                                        ->live(),
                                                ]),

                                            // 18. Subscription & Billing
                                            Section::make(__('Subscription & Billing'))
                                                ->description(__('Plan overview, invoices, receipts, and payments.'))
                                                ->icon('heroicon-o-credit-card')
                                                ->extraAttributes(['title' => __('Subscription & Billing: gates the billing overview and subscription management pages.')])
                                                ->collapsible()
                                                ->schema([
                                                    Toggle::make('modules_saas')
                                                        ->label(__('Enable Subscription & Billing Module'))
                                                        ->helperText(__('Master toggle: controls the billing overview and subscription management pages.'))
                                                        ->hintIcon('heroicon-o-information-circle', __('Master toggle: enables Subscription & Billing, the overview, invoices, and receipts.'))
                                                        ->default(true)
                                                        ->live(),
                                                ]),
                                        ]),
                                ]),
                            ]),

                        Tab::make(__('Institution Profile'))
                            ->icon('heroicon-o-home-modern')
                            ->schema([
                                TextInput::make('profile_school_name')->label(__('School Name')),
                                TextInput::make('profile_short_name')->label(__('Short Name')),
                                TextInput::make('profile_reg_number')->label(__('Ministry Registration Number')),
                                Select::make('profile_school_type')
                                    ->label(__('School Type'))
                                    ->options([
                                        'primary' => __('Primary School'),
                                        'secondary' => __('Secondary School'),
                                        'combined' => __('Combined / Comprehensive'),
                                        'college' => __('Tertiary College'),
                                    ]),
                                TextInput::make('profile_phone')->label(__('Phone Number')),
                                TextInput::make('profile_email')->label(__('Contact Email')),
                                TextInput::make('profile_address')->label(__('Physical Address')),
                                TextInput::make('profile_established_year')
                                    ->label(__('Established Year'))
                                    ->numeric()
                                    ->minValue(1800)
                                    ->maxValue((int) date('Y'))
                                    ->placeholder(__('e.g. 1985'))
                                    ->helperText(__('Shown on the branded sign-in page when set.')),
                                TextInput::make('profile_principal_name')
                                    ->label(__('Principal / Head Name'))
                                    ->maxLength(120)
                                    ->placeholder(__('e.g. Dr. S. Dube'))
                                    ->helperText(__('Shown on the branded sign-in page when set.')),
                                TextInput::make('profile_website')
                                    ->label(__('Website URL'))
                                    ->url()
                                    ->placeholder(__('e.g. https://www.your-school.org'))
                                    ->helperText(__('Leave empty to use the website automatically assigned to this school (e.g. http://'.(current_tenant()?->subdomain ?? 'school').'.schoolcore.com/).')),
                            ])->columns(2),

                        Tab::make(__('Footer & Branding'))
                            ->icon('heroicon-o-window')
                            ->schema([
                                Section::make(__('App Footer'))
                                    ->description(__('Controls the low-profile footer shown at the bottom of every page in the app panel.'))
                                    ->schema([
                                        TextInput::make('footer_powered_by_text')
                                            ->label(__('Powered-By Text'))
                                            ->placeholder(__('Powered by Tinway Technologies'))
                                            ->helperText(__('Shown as the clickable "powered by" label in the footer.')),
                                        TextInput::make('footer_powered_by_url')
                                            ->label(__('Powered-By Link (URL)'))
                                            ->url()
                                            ->placeholder(__('https://www.tinwaytechnologies.com'))
                                            ->helperText(__('Where the "powered by" label links to. Leave empty to show the text without a link.')),
                                    ])->columns(2),
                            ]),

                        Tab::make(__('Banking & Payments'))
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Section::make(__('Bank Accounts'))
                                    ->description(__('Bank accounts are created and maintained by the finance team under Finance → School Bank Accounts. Here you only choose which of those accounts should appear on printed student invoices.'))
                                    ->schema([
                                        Select::make('banking_invoice_default_bank_account_id')
                                            ->label(__('Bank Account Printed on Student Invoices'))
                                            ->helperText(__('Choose which registered bank account should print on student invoices by default. Leave empty to print all active accounts.'))
                                            ->options(function () {
                                                return SchoolBankAccount::query()
                                                    ->where('is_active', true)
                                                    ->orderByDesc('is_default')
                                                    ->orderBy('bank_name')
                                                    ->get()
                                                    ->mapWithKeys(fn ($account) => [
                                                        (string) $account->id => sprintf(
                                                            '%s — %s (%s)%s',
                                                            $account->bank_name,
                                                            $account->account_name,
                                                            $account->account_number,
                                                            $account->is_default ? __(' (Default)') : ''
                                                        ),
                                                    ])
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->placeholder(__('All active accounts')),
                                        Placeholder::make('banking_bank_accounts_link')
                                            ->label(__('Manage Bank Accounts'))
                                            ->content(fn () => new HtmlString(
                                                __('Create, edit or deactivate bank accounts under ')
                                                .'<a href="'.SchoolBankAccountResource::getUrl('index').'" class="font-semibold text-[color:var(--sc-brand)] underline">'
                                                .__('Finance → School Bank Accounts')
                                                .'</a>. '
                                                .__('Only active accounts appear in the dropdown above and on invoices, receipts and statements.')
                                            )),
                                    ]),

                                Section::make(__('Mobile Money / EcoCash'))
                                    ->schema([
                                        TextInput::make('banking_ecocash_merchant')
                                            ->label(__('EcoCash Merchant Pin Code'))
                                            ->placeholder(__('e.g. *153*1*55326*AMOUNT#'))
                                            ->helperText(__('Use the {AMOUNT} placeholder to let parents pay any amount.')),
                                    ]),
                            ]),

                        Tab::make(__('Authentication & Security'))
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                Select::make('security_password_length')
                                    ->label(__('Minimum Password Length'))
                                    ->options([
                                        '8' => __('8 Characters'),
                                        '10' => __('10 Characters'),
                                        '12' => __('12 Characters'),
                                    ])->default('8'),
                                Select::make('security_session_timeout')
                                    ->label(__('Auto-Session Timeout'))
                                    ->options([
                                        '15' => __('15 Minutes'),
                                        '30' => __('30 Minutes'),
                                        '60' => __('60 Minutes'),
                                        '120' => __('2 Hours'),
                                    ])->default('60'),
                                Toggle::make('security_require_mfa')->label(__('Enforce Multi-Factor Authentication (MFA)'))->default(false),
                                TextInput::make('security_ip_restrictions')
                                    ->label(__('Restrict System Login Access by IP Range'))
                                    ->placeholder(__('Leave empty to allow all IP requests')),
                            ])->columns(2),

                        Tab::make(__('System Preferences'))
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Select::make('preferences_default_language')
                                    ->label(__('School Operating & ERP Language'))
                                    ->options([
                                        'en' => __('English'),
                                        'sn' => __('Shona'),
                                        'sw' => __('Swahili'),
                                        'fr' => __('Français'),
                                        'pt' => __('Português'),
                                        'es' => __('Español'),
                                    ])->default('en')
                                    ->helperText(__('Used for system administration and all official printed documents/reports.')),
                                Select::make('preferences_website_language')
                                    ->label(__('Public Website & Portal Language'))
                                    ->options([
                                        'en' => __('English'),
                                        'sn' => __('Shona'),
                                        'sw' => __('Swahili'),
                                        'fr' => __('Français'),
                                        'pt' => __('Português'),
                                        'es' => __('Español'),
                                    ])->default('en')
                                    ->helperText(__('Used independently for public portal pages, landing pages, and the CMS builder.')),
                                Select::make('preferences_default_currency')
                                    ->label(__('Primary Ledger Currency'))
                                    ->options([
                                        'USD' => __('United States Dollar ($)'),
                                        'ZiG' => __('Zimbabwe Gold (ZiG)'),
                                        'ZAR' => __('South African Rand (R)'),
                                        'BWP' => __('Botswana Pula (P)'),
                                        'ZMW' => __('Zambian Kwacha (ZK)'),
                                        'MWK' => __('Malawian Kwacha (MK)'),
                                        'MZN' => __('Mozambican Metical (MT)'),
                                        'KES' => __('Kenyan Shilling (KSh)'),
                                        'GBP' => __('British Pound (£)'),
                                        'EUR' => __('Euro (€)'),
                                    ])->default('USD'),
                                TextInput::make('preferences_student_format')
                                    ->label(__('Student ID Prefix Pattern'))
                                    ->default('STU-{YEAR}-{SEQ}'),
                                TextInput::make('preferences_invoice_format')
                                    ->label(__('Invoice Number Pattern'))
                                    ->default('INV-{SEQ}'),
                            ])->columns(2),

                         Tab::make(__('Legal & Terms'))
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make(__('School Terms & Conditions'))
                                    ->description(__('Customize the Terms of Service and Conditions that users must review and agree to during registration to your school portal.'))
                                    ->schema([
                                        \Filament\Forms\Components\RichEditor::make('legal_terms_content')
                                            ->label(__('School Terms of Service Content'))
                                            ->default(default_school_terms())
                                            ->columnSpanFull()
                                            ->required(),
                                    ]),
                            ]),

                         Tab::make(__('Email Configuration'))
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Placeholder::make('email_config_intro')
                                    ->label(__('School Email Configuration'))
                                    ->content(new HtmlString(
                                        __('Configure how this school sends email for each category. '.
                                        'The sender address must be a school-specific address and can never reuse the platform '.
                                        'sending account (<strong>'.e(platform_email_address()).'</strong>). '.
                                        'Changes made here are shared with the dedicated <strong>Email Configuration</strong> page '.
                                        'under System Administration — there is only ever one underlying configuration per category.')
                                    )),
                                ...$this->emailConfigurationSections(),
                                Placeholder::make('email_branding_intro')
                                    ->label(__('Email Branding'))
                                    ->content(new HtmlString(__('These details appear in the header and footer of every email your school automatically sends — activation invites, registration alerts, admissions confirmations and more.')))
                                    ->columnSpanFull(),
                                FileUpload::make('email_logo_path')
                                    ->label(__('Email logo'))
                                    ->image()
                                    ->imageEditor()
                                    ->directory('school/email-branding')
                                    ->maxSize(2048)
                                    ->helperText(__('Shown at the top of outgoing emails. Falls back to your workspace logo when empty.')),
                                TextInput::make('email_company_name')
                                    ->label(__('Company / institution name'))
                                    ->maxLength(120),
                                TextInput::make('email_company_address')
                                    ->label(('Address'))
                                    ->maxLength(255),
                                TextInput::make('email_company_phone')
                                    ->label(__('Phone number'))
                                    ->tel()
                                    ->maxLength(40),
                                TextInput::make('email_company_email')
                                    ->label(__('Contact email address'))
                                    ->email()
                                    ->maxLength(120),
                            ]),

                        Tab::make(__('Notifications'))
                            ->icon('heroicon-o-bell-alert')
                            ->schema([
                                Placeholder::make('notifications_intro')
                                    ->label(__('Registration Notifications'))
                                    ->content(new HtmlString(
                                        __('Control how your school administrators are alerted when a new user registers on your workspace. '.
                                        'In-app notifications are always delivered to authorized approvers; the toggle below only governs '.
                                        'whether a notification <em>email</em> is also sent for each registration.')
                                    ))
                                    ->columnSpanFull(),
                                Toggle::make('notifications_email_on_user_registration')
                                    ->label(__('Email approvers when a new user registers'))
                                    ->default(true)
                                    ->helperText(__('When switched off, approvers are still notified inside the workspace, but no email is sent for every registration.')),
                            ]),

                        Tab::make(__('Feature Flags'))
                            ->icon('heroicon-o-flag')
                            ->schema([
                                Placeholder::make('feature_flags_intro')
                                    ->label(__('Tenant Feature Flags'))
                                    ->content(new HtmlString(
                                        __('Toggle individual platform features on or off for your school. '.
                                        'Disabled features are hidden from the navigation and inaccessible to all users. '.
                                        'Each flag inherits the platform default; overrides below apply only to your tenant.')
                                    ))
                                    ->columnSpanFull(),

                                Section::make(__('UI & Experience'))
                                    ->icon('heroicon-o-paint-brush')
                                    ->columns(3)
                                    ->schema([
                                        Toggle::make('features_dark_mode')
                                            ->label(__('Dark Mode'))
                                            ->default(false)
                                            ->helperText(__('Allow users to switch to dark theme')),
                                        Toggle::make('features_onboarding_wizard')
                                            ->label(__('Onboarding Wizard'))
                                            ->default(true)
                                            ->helperText(__('Show setup wizard for new administrators')),
                                        Toggle::make('features_id_card_designer')
                                            ->label(__('ID Card Designer'))
                                            ->default(true)
                                            ->helperText(__('Visual student & staff ID card builder')),
                                    ]),

                                Section::make(__('Core Modules'))
                                    ->icon('heroicon-o-cube')
                                    ->columns(3)
                                    ->schema([
                                        Toggle::make('features_attendance_tracking')
                                            ->label(__('Attendance Tracking'))
                                            ->default(true),
                                        Toggle::make('features_exam_management')
                                            ->label(__('Exam Management'))
                                            ->default(true),
                                        Toggle::make('features_fee_management')
                                            ->label(__('Fee Management'))
                                            ->default(true),
                                        Toggle::make('features_payroll_module')
                                            ->label(__('Payroll Module'))
                                            ->default(true),
                                        Toggle::make('features_library_module')
                                            ->label(__('Library Module'))
                                            ->default(true),
                                        Toggle::make('features_hostel_module')
                                            ->label(__('Hostel / Boarding'))
                                            ->default(true),
                                        Toggle::make('features_clinic_module')
                                            ->label(__('Clinic / Health'))
                                            ->default(true),
                                        Toggle::make('features_inventory_module')
                                            ->label(__('Inventory Module'))
                                            ->default(true),
                                        Toggle::make('features_lms_module')
                                            ->label(__('LMS (Homework & Lessons)'))
                                            ->default(true),
                                        Toggle::make('features_knowledge_base')
                                            ->label(__('Knowledge Base'))
                                            ->default(true),
                                        Toggle::make('features_report_generation')
                                            ->label(__('Report Generation'))
                                            ->default(true),
                                        Toggle::make('features_website_builder')
                                            ->label(__('Website / CMS Builder'))
                                            ->default(true),
                                    ]),

                                Section::make(__('Communication & Notifications'))
                                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                    ->columns(3)
                                    ->schema([
                                        Toggle::make('features_communication_center')
                                            ->label(__('Communication Center'))
                                            ->default(true),
                                        Toggle::make('features_email_notifications')
                                            ->label(__('Email Notifications'))
                                            ->default(true),
                                        Toggle::make('features_sms_notifications')
                                            ->label(__('SMS Notifications'))
                                            ->default(false),
                                        Toggle::make('features_whatsapp_notifications')
                                            ->label(__('WhatsApp Notifications'))
                                            ->default(false),
                                    ]),

                                Section::make(__('Portals & Access'))
                                    ->icon('heroicon-o-users')
                                    ->columns(3)
                                    ->schema([
                                        Toggle::make('features_student_portal')
                                            ->label(__('Student Portal'))
                                            ->default(true),
                                        Toggle::make('features_parent_portal')
                                            ->label(__('Parent Portal'))
                                            ->default(false),
                                        Toggle::make('features_staff_portal')
                                            ->label(__('Staff Portal'))
                                            ->default(true),
                                        Toggle::make('features_online_admissions')
                                            ->label(__('Online Admissions'))
                                            ->default(true),
                                        Toggle::make('features_two_factor_auth')
                                            ->label(__('Two-Factor Authentication'))
                                            ->default(false),
                                        Toggle::make('features_custom_roles')
                                            ->label(__('Custom Role Builder'))
                                            ->default(false),
                                    ]),

                                Section::make(__('Data & Integrations'))
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->columns(3)
                                    ->schema([
                                        Toggle::make('features_csv_export')
                                            ->label(__('CSV Export'))
                                            ->default(true),
                                        Toggle::make('features_pdf_export')
                                            ->label(__('PDF Export'))
                                            ->default(true),
                                        Toggle::make('features_data_import')
                                            ->label(__('Data Import'))
                                            ->default(true),
                                        Toggle::make('features_advanced_analytics')
                                            ->label(__('Advanced Analytics'))
                                            ->default(false),
                                        Toggle::make('features_api_access')
                                            ->label(__('External API Access'))
                                            ->default(false),
                                        Toggle::make('features_bulk_operations')
                                            ->label(__('Bulk Operations'))
                                            ->default(true),
                                        Toggle::make('features_print_reports')
                                            ->label(__('Print Reports'))
                                            ->default(true),
                                        Toggle::make('features_academic_calendar')
                                            ->label(__('Academic Calendar'))
                                            ->default(true),
                                        Toggle::make('features_timetable_builder')
                                            ->label(__('Visual Timetable Builder'))
                                            ->default(true),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function applyAllModuleToggles(bool $enabled): void
    {
        $state = $this->data ?? [];

        foreach (array_keys($state) as $key) {
            if (str_starts_with((string) $key, 'modules_')) {
                $this->data[$key] = $enabled;
            }
        }
    }

    public function save(): void
    {
        $state = $this->form->getState();
        /** @var User|null $user */
        $user = Auth::user();
        $schoolId = session('current_tenant')?->id ?? ($user ? $user->school_id : null);

        if (! $schoolId) {
            return;
        }

        $oldValues = [];
        $newValues = [];

        foreach ($state as $compoundKey => $value) {
            // Email configuration keys are namespaced under "emailcfg.*" and are
            // persisted to the email_configurations table, not system_settings.
            if (str_starts_with((string) $compoundKey, 'emailcfg.')) {
                continue;
            }

            $parts = explode('_', $compoundKey, 2);
            if (count($parts) < 2) {
                continue;
            }

            $group = $parts[0];
            $key = $parts[1];

            $settingModel = SystemSetting::where('school_id', $schoolId)
                ->where('group', $group)
                ->where('key', $key)
                ->first();

            // Normalise the module master/sub-page toggles. The System Settings
            // form submits them as PHP booleans which various PDO drivers persist
            // as "1"/"0"/"true"/"false"/"". Force a canonical "1"/"0" string so
            // ModuleVisibilityManager's filter_var check is always deterministic.
            if ($group === 'modules' && is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            // Normalise feature flag toggles the same way.
            if ($group === 'features' && is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            $serialized = is_array($value) ? json_encode($value) : $value;

            $oldValues[$compoundKey] = $settingModel ? $settingModel->value : null;
            $newValues[$compoundKey] = $serialized;

            SystemSetting::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'group' => $group,
                    'key' => $key,
                ],
                [
                    'value' => $serialized,
                ]
            );
        }

        AuditLogger::log('Update Configuration Settings', 'System Administration', $oldValues, $newValues);

        if (isset($state['preferences_default_language'])) {
            $tenant = current_tenant() ?: School::find($schoolId);
            if ($tenant) {
                $tenant->update(['locale' => $state['preferences_default_language']]);
                session(['locale' => $state['preferences_default_language']]);
                app()->setLocale($state['preferences_default_language']);
            }
        }

        // Persist the shared email configuration (same rows the dedicated
        // "Email Configuration" page writes to).
        $this->saveEmailConfiguration($state);

        // Resolve URLs for the live-reload event [1.2]
        $logoUrl = asset('images/logo-transparent.png');
        if (! empty($state['branding_logo_path'])) {
            $logoUrl = asset('storage/'.$state['branding_logo_path']);
        }
        $bgUrl = asset('images/School_repository_cover.jpeg');
        if (! empty($state['branding_bg_path'])) {
            $bgUrl = asset('storage/'.$state['branding_bg_path']);
        }

        $this->dispatch('theme-updated', [
            'theme' => $state['branding_theme'] ?? 'emerald_heritage',
            'font_family' => $state['branding_font_family'] ?? 'inter',
            'logo_height' => $state['branding_logo_height'] ?? '32px',
            'logo_opacity' => $state['branding_logo_opacity'] ?? '1.0',
            'background_opacity' => $state['branding_background_opacity'] ?? '0.08',
            'background_scaling' => $state['branding_background_scaling'] ?? 'cover',
            'logo_url' => $logoUrl,
            'bg_url' => $bgUrl,
            'school_name' => $state['profile_school_name'] ?? 'Kairo CORE',
        ]);

        $this->dispatch('reload-page-after-save');

        Notification::make()
            ->title(__('Branding preferences and styles updated'))
            ->success()
            ->send();
    }
}
