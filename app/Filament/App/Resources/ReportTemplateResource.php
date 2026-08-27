<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ReportTemplateResource\Pages;
use App\Models\School;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\ReportTemplate;
use Modules\Academics\Models\Section;
use Modules\Admin\Services\PermissionRegistry;

class ReportTemplateResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Exams & Grading');
    }

    protected static ?string $model = ReportTemplate::class;

    protected static bool $shouldRegisterNavigation = true;

    public static function canAccess(): bool
    {
        // Check if the academics module is visible
        if (! ModuleVisibilityManager::isVisible('academics')) {
            return false;
        }

        // Try to check permissions, but fail gracefully if the method doesn't exist
        try {
            if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
                $permissionRegistry = app(PermissionRegistry::class);

                // Try to call the method if it exists
                if (method_exists($permissionRegistry, 'checkAcademicPermission')) {
                    return $permissionRegistry->checkAcademicPermission('academic_ops.manage_reports');
                }
            }
        } catch (\Exception $e) {
            // Log the error but allow access for now
            \Log::warning('Permission check failed in ReportTemplateResource: '.$e->getMessage());
        }

        // Default: allow access if module is visible
        return true;
    }

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationLabel = 'Report Templates';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // Left Column: Controls & Styling Fields (Span 2)
                        Forms\Components\Group::make([
                            Forms\Components\Tabs::make(__('Report Template Designer'))
                                ->tabs([
                                    // TAB 1: BRACKET MAPPING, THEMES & TARGET SCOPING
                                    Forms\Components\Tabs\Tab::make(__('1. Scopes & Theme'))
                                        ->icon('heroicon-o-tag')
                                        ->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->required()
                                                ->placeholder(__('e.g. Primary 1-7 Standard Layout'))
                                                ->live(),

                                            Forms\Components\Select::make('design_theme')
                                                ->label(__('Choose Pre-designed Theme'))
                                                ->options(ReportTemplate::$themes)
                                                ->default('classic_line')
                                                ->required()
                                                ->live(),

                                            Forms\Components\Select::make('scope_type')
                                                ->label(__('Template Assignment Scope'))
                                                ->options(ReportTemplate::$scopes)
                                                ->default('level')
                                                ->required()
                                                ->live(),

                                            Forms\Components\Select::make('target_level')
                                                ->label(__('Educational Bracket'))
                                                ->options(ReportTemplate::$brackets)
                                                ->visible(fn (Forms\Get $get) => $get('scope_type') === 'level')
                                                ->required(fn (Forms\Get $get) => $get('scope_type') === 'level')
                                                ->live(),

                                            Forms\Components\Select::make('course_id')
                                                ->label(__('Specific Grade / Form Level'))
                                                ->options(Course::all()->pluck('name', 'id'))
                                                ->visible(fn (Forms\Get $get) => $get('scope_type') === 'course')
                                                ->required(fn (Forms\Get $get) => $get('scope_type') === 'course')
                                                ->searchable()
                                                ->preload()
                                                ->live(),

                                            Forms\Components\Select::make('section_id')
                                                ->label(__('Specific Class Stream'))
                                                ->options(Section::with('course')->get()->pluck('full_name', 'id'))
                                                ->visible(fn (Forms\Get $get) => $get('scope_type') === 'section')
                                                ->required(fn (Forms\Get $get) => $get('scope_type') === 'section')
                                                ->searchable()
                                                ->preload()
                                                ->live(),

                                            Forms\Components\Toggle::make('is_active')
                                                ->label(__('Set as Active Layout Template'))
                                                ->default(false),
                                        ])->columns(2),

                                    // TAB 2: TYPOGRAPHY, BRANDING & COLORS
                                    Forms\Components\Tabs\Tab::make(__('2. Typography & Colors'))
                                        ->icon('heroicon-o-paint-brush')
                                        ->schema([
                                            Forms\Components\Fieldset::make(__('Font Styles'))
                                                ->schema([
                                                    Forms\Components\Select::make('layout_config.font_family')
                                                        ->label(__('Card Font Family'))
                                                        ->options([
                                                            'Helvetica, sans-serif' => __('Helvetica / Arial (Clean Modern)'),
                                                            'Georgia, serif' => __('Georgia (Elegant Editorial)'),
                                                            'Times New Roman, serif' => __('Times New Roman (Academic Classic)'),
                                                            'Courier, monospace' => __('Courier (System Monospace)'),
                                                        ])
                                                        ->default('Helvetica, sans-serif')
                                                        ->required()
                                                        ->live(),

                                                    Forms\Components\TextInput::make('layout_config.header_font_size')
                                                        ->label(__('Header Title Font Size (px)'))
                                                        ->numeric()
                                                        ->default(20)
                                                        ->live(),
                                                ])->columns(2),

                                            Forms\Components\Fieldset::make(__('Color Adjustments'))
                                                ->schema([
                                                    Forms\Components\ColorPicker::make('layout_config.header_color')
                                                        ->label(__('Header Title Color'))
                                                        ->default('#1e3a8a')
                                                        ->live(),

                                                    Forms\Components\ColorPicker::make('layout_config.body_text_color')
                                                        ->label(__('Body Text Color'))
                                                        ->default('#1e293b')
                                                        ->live(),

                                                    Forms\Components\ColorPicker::make('layout_config.table_header_bg')
                                                        ->label(__('Table Header Background'))
                                                        ->default('#f1f5f9')
                                                        ->live(),
                                                ])->columns(3),

                                            Forms\Components\Fieldset::make(__('Branding Visibilities'))
                                                ->schema([
                                                    Forms\Components\Toggle::make('layout_config.show_school_logo')->label(__('Display School Logo'))->default(true)->live(),
                                                    Forms\Components\Toggle::make('layout_config.show_school_motto')->label(__('Display School Motto'))->default(true)->live(),
                                                    Forms\Components\Toggle::make('layout_config.show_phone')->label(__('Display Contact Phone'))->default(true)->live(),
                                                    Forms\Components\Toggle::make('layout_config.show_email')->label(__('Display Contact Email'))->default(true)->live(),
                                                    Forms\Components\Toggle::make('layout_config.show_address')->label(__('Display Physical Address'))->default(true)->live(),
                                                ])->columns(5),
                                        ]),

                                    // TAB 3: SPACING & BOARD SPACES
                                    Forms\Components\Tabs\Tab::make(__('3. Margins & Padding'))
                                        ->icon('heroicon-o-arrows-pointing-out')
                                        ->schema([
                                            Forms\Components\TextInput::make('layout_config.page_margin_v')
                                                ->label(__('Page Vertical Margin (mm)'))
                                                ->numeric()
                                                ->default(12)
                                                ->live()
                                                ->required(),

                                            Forms\Components\TextInput::make('layout_config.page_margin_h')
                                                ->label(__('Page Horizontal Margin (mm)'))
                                                ->numeric()
                                                ->default(15)
                                                ->live()
                                                ->required(),

                                            Forms\Components\TextInput::make('layout_config.table_padding')
                                                ->label(__('Table Cell Padding (px)'))
                                                ->numeric()
                                                ->default(5)
                                                ->live()
                                                ->required(),

                                            Forms\Components\TextInput::make('layout_config.page_border_width')
                                                ->label(__('Page Border Thickness (px)'))
                                                ->numeric()
                                                ->default(0)
                                                ->live()
                                                ->required(),

                                            Forms\Components\ColorPicker::make('layout_config.page_border_color')
                                                ->label(__('Page Border Color'))
                                                ->default('#fbbf24')
                                                ->live()
                                                ->required(),
                                        ])->columns(3),

                                    // TAB 4: ACADEMIC TABLE COLUMNS CUSTOMIZER
                                    Forms\Components\Tabs\Tab::make(__('4. Academic Columns'))
                                        ->icon('heroicon-o-table-cells')
                                        ->schema([
                                            Forms\Components\Fieldset::make(__('Select Columns to Display'))
                                                ->schema([
                                                    Forms\Components\CheckboxList::make('layout_config.included_assessments')
                                                        ->label(__('Select Included Tests/Assessments'))
                                                        ->options(fn () => AssessmentType::where('school_id', app('current_tenant')->id)
                                                            ->pluck('name', 'id')
                                                            ->toArray()
                                                        )
                                                        ->columns(2)
                                                        ->live()
                                                        ->default(function () {
                                                            return AssessmentType::where('school_id', app('current_tenant')->id)
                                                                ->pluck('id')
                                                                ->toArray();
                                                        }),

                                                    Forms\Components\Toggle::make('layout_config.show_class_average')
                                                        ->label(__('Include Class Average Column'))
                                                        ->default(true)
                                                        ->live(),

                                                    Forms\Components\Toggle::make('layout_config.show_stream_average')
                                                        ->label(__('Include Stream / Level Average Column'))
                                                        ->default(true)
                                                        ->live(),
                                                ])->columns(1),
                                        ]),

                                    // TAB 5: VISIBILITY CONTROLS, RANKINGS & DYNAMIC SELECTIONS
                                    Forms\Components\Tabs\Tab::make(__('5. Section Visibilities'))
                                        ->icon('heroicon-o-eye')
                                        ->schema([
                                            Forms\Components\Fieldset::make(__('Information Modules & Rankings'))
                                                ->schema([
                                                    Forms\Components\Toggle::make('layout_config.show_student_photo')->label(__('Display Student Photo'))->default(true)->live(),
                                                    Forms\Components\Toggle::make('layout_config.show_class_position')->label(__('Display Class Position / Rank'))->default(true)->live(),
                                                    Forms\Components\Toggle::make('layout_config.show_stream_position')->label(__('Display Overall Stream / Level Rank'))->default(true)->live(),
                                                    Forms\Components\Toggle::make('layout_config.show_subject_position')->label(__('Display Subject Positions'))->default(true)->live(),
                                                    Forms\Components\Toggle::make('layout_config.show_outstanding_achievements')->label(__('Display Outstanding Achievements Section'))->default(true)->live(),
                                                    Forms\Components\Toggle::make('layout_config.show_next_term_fees')->label(__('Display Next Term Fees / Schedule Box'))->default(true)->live(),
                                                    Forms\Components\Toggle::make('layout_config.show_ubuntu_competencies')->label(__('Display Unhu/Ubuntu Skills Table'))->default(true)->live(),
                                                    Forms\Components\Toggle::make('layout_config.show_ubuntu_percentage')->label(__('Display Unhu Grade as a Percentage'))->default(true)->live(),
                                                    Forms\Components\Toggle::make('layout_config.show_grading_keys')->label(__('Display Grading Scales Key in Footer'))->default(true)->live(),
                                                ])->columns(3),

                                            Forms\Components\Fieldset::make(__('Select Unhu/Ubuntu Traits to Display'))
                                                ->visible(fn (Forms\Get $get) => $get('layout_config.show_ubuntu_competencies'))
                                                ->schema([
                                                    Forms\Components\CheckboxList::make('layout_config.displayed_ubuntu_traits')
                                                        ->label(__('Select Core Traits'))
                                                        ->options([
                                                            'respect' => __('Respect'),
                                                            'honesty' => __('Honesty'),
                                                            'responsibility' => __('Responsibility'),
                                                            'discipline' => __('Discipline'),
                                                            'patriotism' => __('Patriotism'),
                                                            'cooperation' => __('Cooperation'),
                                                            'leadership' => __('Leadership'),
                                                            'critical_thinking' => __('Critical Thinking'),
                                                            'creativity' => __('Creativity'),
                                                            'environment' => __('Environment'),
                                                            'communication' => __('Communication'),
                                                            'digital_literacy' => __('Digital Literacy'),
                                                            'entrepreneurship' => __('Entrepreneurship'),
                                                            'cultural_appreciation' => __('Cultural Appreciation'),
                                                            'community_service' => __('Community Service'),
                                                            'perseverance' => __('Perseverance'),
                                                            'compassion' => __('Compassion'),
                                                            'time_management' => __('Time Management'),
                                                            'self_confidence' => __('Self Confidence'),
                                                            'adaptability' => __('Adaptability'),
                                                        ])
                                                        ->default(['respect', 'honesty', 'responsibility', 'discipline', 'cooperation'])
                                                        ->columns(3)
                                                        ->live()
                                                        ->required(fn (Forms\Get $get) => $get('layout_config.show_ubuntu_competencies')),
                                                ])->columns(1),
                                        ]),

                                    // TAB 6: CUSTOM ANNOUNCEMENTS, FEES & SCHEDULES
                                    Forms\Components\Tabs\Tab::make(__('6. Schedule & Fees Settings'))
                                        ->icon('heroicon-o-banknotes')
                                        ->schema([
                                            Forms\Components\DatePicker::make('layout_config.next_term_begins')
                                                ->label(__('Next Term Begins On'))
                                                ->default(now()->addMonth())
                                                ->live(),

                                            Forms\Components\DatePicker::make('layout_config.next_term_ends')
                                                ->label(__('Next Term Ends On'))
                                                ->default(now()->addMonths(4))
                                                ->live(),

                                            Forms\Components\TextInput::make('layout_config.next_term_fees')
                                                ->label(__('Next Term Fees Due'))
                                                ->placeholder(__('e.g. $800.00 USD'))
                                                ->default('$800.00 USD')
                                                ->live(),

                                            Forms\Components\Textarea::make('layout_config.requirements')
                                                ->label(__('Next Term Requirements Guide'))
                                                ->placeholder(__('e.g. 1 Ream of Paper, 4 Rolls of Toilet Paper'))
                                                ->default('1 Ream of Paper, 4 Rolls of Toilet Paper')
                                                ->live()
                                                ->columnSpan(2),

                                            Forms\Components\Textarea::make('layout_config.special_announcements')
                                                ->label(__('Special Announcements / School Requirements'))
                                                ->placeholder(__('Write special board announcements here...'))
                                                ->live()
                                                ->columnSpanFull(),
                                        ])->columns(3),
                                ]),
                        ])->columnSpan(2),

                        // Right Column: Interactive WYSIWYG Realtime Preview Panel (Span 1)
                        Forms\Components\Group::make([
                            Forms\Components\Section::make(__('Interactive Live Simulator'))
                                ->description(__('Simulates layout changes in real-time before saving.'))
                                ->schema([
                                    Forms\Components\Placeholder::make('live_preview')
                                        ->content(fn (Forms\Get $get) => new HtmlString(self::generateLivePreviewHtml($get))),
                                ]),
                        ])->columnSpan(1)->extraAttributes(['class' => 'sticky top-6']),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('scope_type')
                    ->label(__('Assignment Scope'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => ReportTemplate::$scopes[$state] ?? $state)
                    ->color('info'),
                Tables\Columns\TextColumn::make('design_theme')
                    ->label(__('Themed style'))
                    ->formatStateUsing(fn ($state) => ReportTemplate::$themes[$state] ?? $state)
                    ->badge()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label(__('Active')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportTemplates::route('/'),
            'create' => Pages\CreateReportTemplate::route('/create'),
            'edit' => Pages\EditReportTemplate::route('/{record}/edit'),
        ];
    }

    /**
     * Helper to render highly precise live visual A4 report card previews within the Filament Form
     */
    protected static function generateLivePreviewHtml(Forms\Get $get): string
    {
        // Get the actual school details from the database
        $school = null;
        try {
            $school = School::find(app('current_tenant')->id);
        } catch (\Exception $e) {
            // Fallback if tenant is not available
            try {
                $school = School::first();
            } catch (\Exception $e2) {
                // If all else fails, use defaults
            }
        }

        $schoolName = $school && $school->name ? $school->name : 'School Name Not Set';
        $schoolMotto = $school && $school->motto ? $school->motto : 'Knowledge Lights the Way';
        $schoolPhone = $school && $school->phone ? $school->phone : '0750002345';
        $schoolEmail = $school && $school->email_address ? $school->email_address : 'info@school.com';
        $schoolAddress = $school && $school->physical_address ? $school->physical_address : 'P.O BOX 001 KLA';

        $theme = $get('design_theme') ?? 'classic_line';
        $fontFamily = $get('layout_config.font_family') ?? 'sans-serif';
        $bodyTextColor = $get('layout_config.body_text_color') ?? '#1e293b';
        $headerColor = $get('layout_config.header_color') ?? '#1e3a8a';
        $headerSize = $get('layout_config.header_font_size') ?? 20;
        $tableHeaderBg = $get('layout_config.table_header_bg') ?? '#f1f5f9';

        // Minimalist Compact is strictly black & white, so force all accents to grayscale
        $accentColor = ($theme === 'minimal_compact') ? '#111827' : $headerColor;
        $successColor = ($theme === 'minimal_compact') ? '#111827' : '#16a34a';
        $dangerColor = ($theme === 'minimal_compact') ? '#111827' : '#b91c1c';

        // Margins & Paddings
        $marginV = $get('layout_config.page_margin_v') ?? 12;
        $marginH = $get('layout_config.page_margin_h') ?? 15;
        $padding = $get('layout_config.table_padding') ?? 5;
        $borderW = $get('layout_config.page_border_width') ?? 0;
        $borderC = $get('layout_config.page_border_color') ?? '#fbbf24';

        // Visibilities & Custom settings
        $showSchoolLogo = $get('layout_config.show_school_logo') ?? true;
        $showSchoolMotto = $get('layout_config.show_school_motto') ?? true;
        $showPhone = $get('layout_config.show_phone') ?? true;
        $showEmail = $get('layout_config.show_email') ?? true;
        $showAddress = $get('layout_config.show_address') ?? true;
        $showStudentPhoto = $get('layout_config.show_student_photo') ?? true;
        $showNextTermFees = $get('layout_config.show_next_term_fees') ?? true;
        $showUbuntuCompetencies = $get('layout_config.show_ubuntu_competencies') ?? true;
        $showUbuntuPercentage = $get('layout_config.show_ubuntu_percentage') ?? true;
        $displayedTraits = $get('layout_config.displayed_ubuntu_traits') ?? ['respect', 'honesty', 'responsibility'];

        $showClassRank = $get('layout_config.show_class_position') ?? true;
        $showStreamRank = $get('layout_config.show_stream_position') ?? true;
        $showSubjectRank = $get('layout_config.show_subject_position') ?? true;
        $showOutstandingAchievements = $get('layout_config.show_outstanding_achievements') ?? true;

        $includedAssessments = $get('layout_config.included_assessments') ?? [];
        $showClassAverage = $get('layout_config.show_class_average') ?? true;
        $showStreamAverage = $get('layout_config.show_stream_average') ?? true;

        // Custom announcements & schedule text
        $nextTermBegins = $get('layout_config.next_term_begins') ? date('d-M-Y', strtotime($get('layout_config.next_term_begins'))) : date('d-M-Y', strtotime('+1 month'));
        $nextTermEnds = $get('layout_config.next_term_ends') ? date('d-M-Y', strtotime($get('layout_config.next_term_ends'))) : date('d-M-Y', strtotime('+4 months'));
        $nextTermFees = $get('layout_config.next_term_fees') ?? '$800.00 USD';
        $requirements = $get('layout_config.requirements') ?? '1 Ream of Paper, 4 Rolls of Toilet Paper';
        $announcements = $get('layout_config.special_announcements') ?? '';

        // Render dynamic table header columns based on column switches
        $tableHeadersHtml = '<th style="text-align: left; width: 30%;">Subject</th>';

        foreach ($includedAssessments as $assessmentId) {
            $assessmentType = AssessmentType::find($assessmentId);
            $testLabel = $assessmentType ? $assessmentType->name : 'Test';
            $tableHeadersHtml .= "<th style='width: 10%;'>{$testLabel}</th>";
        }

        if ($showClassAverage) {
            $tableHeadersHtml .= '<th style="width: 12%;">Class Avg</th>';
        }
        if ($showStreamAverage) {
            $tableHeadersHtml .= '<th style="width: 12%;">Strm Avg</th>';
        }
        if ($showSubjectRank) {
            $tableHeadersHtml .= '<th style="width: 10%;">Rank</th>';
        }
        $tableHeadersHtml .= '<th style="width: 10%;">Grade</th>';

        // Render dynamic Unhu rows
        $unhuRowsHtml = '';
        if ($showUbuntuCompetencies) {
            $traitsFormatted = array_map(fn ($t) => ucfirst(str_replace('_', ' ', $t)), $displayedTraits);
            foreach ($traitsFormatted as $traitName) {
                $unhuRowsHtml .= "
                    <tr>
                        <td style='text-align: left; font-weight: bold;'>{$traitName}</td>
                        <td style='font-weight: bold;'>Excellent</td>
                    </tr>
                ";
            }
        }

        return "
            <style>
                .preview-sheet-wrapper {
                    background-color: #f1f5f9;
                    padding: 16px;
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .preview-sheet-canvas {
                    position: relative;
                    background-color: #ffffff;
                    width: 320px;
                    min-height: 450px;
                    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                    box-sizing: border-box;
                    line-height: 1.2;
                }
                .ps-element { font-size: 7px; margin-bottom: 4px; }
                .ps-school-name { font-weight: bold; text-transform: uppercase; margin: 0; }
                .ps-school-motto { font-size: 5px; font-style: italic; margin-top: 1px; text-transform: uppercase; }

                /* THEME SIMULATORS */
                .pt-classic_line .school-hdr { border-bottom: 2px double {$headerColor}; padding-bottom: 4px; text-align: center; }
                .pt-classic_line th { background-color: {$tableHeaderBg}; color: {$headerColor}; border: 0.5px solid {$headerColor}; }
                .pt-classic_line td { border: 0.5px solid #cbd5e1; }

                .pt-modern_grid .school-hdr { background: {$headerColor}; color: #ffffff; padding: 6px; border-radius: 4px; text-align: center; }
                .pt-modern_grid .school-hdr .ps-school-name { color: #ffffff !important; }
                .pt-modern_grid .school-hdr .ps-school-motto { color: #ffffff !important; }
                .pt-modern_grid th { background-color: {$headerColor}; color: #ffffff; border: 0.5px solid #cbd5e1; }
                .pt-modern_grid td { border: 0.5px solid #cbd5e1; }

                .pt-elegant_editorial { font-family: 'Times New Roman', Georgia, serif !important; }
                .pt-elegant_editorial .school-hdr { border-bottom: 1.5px solid #7f1d1d; text-align: center; }
                .pt-elegant_editorial th { background-color: #7f1d1d; color: #ffffff; border: 0.5px solid #7f1d1d; }
                .pt-elegant_editorial td { border: 0.5px solid #f1f5f9; }

                .pt-minimal_compact .school-hdr { text-align: left; border-bottom: 1.5px solid #cbd5e1; padding-bottom: 3px; }
                .pt-minimal_compact th { background-color: {$tableHeaderBg}; color: #334155; border-bottom: 1.5px solid #cbd5e1; border-top: 0.5px solid #cbd5e1; }
                .pt-minimal_compact td { border-bottom: 0.5px solid #f1f5f9; }

                .pt-royal_crest { border: 1.5px solid #fbbf24; padding: 3px; }
                .pt-royal_crest .school-hdr { background: {$headerColor}; color: #fbbf24; padding: 6px; text-align: center; }
                .pt-royal_crest th { background-color: #1e3a8a; color: #ffffff; border: 0.5px solid #fbbf24; }
                .pt-royal_crest td { border: 0.5px solid #fef3c7; }

                .ps-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
                .ps-table th { font-size: 6px; font-weight: bold; padding: {$padding}px 3px; text-align: center; }
                .ps-table td { font-size: 5.5px; padding: {$padding}px 3px; vertical-align: middle; text-align: center; }

                .ps-remarks-container { border: 0.5px solid #cbd5e1; padding: 4px; margin-bottom: 4px; border-radius: 2px; }
                .ps-remarks-title { font-weight: bold; color: #334155; font-size: 6px; text-transform: uppercase; }
                .ps-manual-line { font-family: monospace; font-size: 6px; border-bottom: 0.5px dotted #94a3b8; padding-bottom: 1px; }

                .ps-meta-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
                .ps-meta-table td { padding: 3px; border: 0.5px solid #e2e8f0; font-size: 6px; }
            </style>

            <div class='preview-sheet-wrapper'>
                <div class='preview-sheet-canvas pt-{$theme}' 
                     style='font-family: {$fontFamily} !important; 
                            color: {$bodyTextColor}; 
                            padding: calc({$marginV}px * 0.7) calc({$marginH}px * 0.7) !important;
                            border: {$borderW}px solid {$borderC};'>
                    
                    <!-- Branded Header -->
                    <div class='school-hdr'>
                        <div style='display: flex; align-items: center; justify-content: center;'>
                            ".($showSchoolLogo ? "<div style='font-size: 14px; margin-right: 4px;'>🏫</div>" : '')."
                            <div class='ps-school-name' style='font-size: calc({$headerSize}px * 0.6); color: {$accentColor};'>{$schoolName}</div>
                        </div>
                        ".($showSchoolMotto ? "<div class='ps-school-motto'>\"{$schoolMotto}\"</div>" : '')."
                        <div style='font-size: 5px; color: #64748b; margin-top: 1px;'>
                            ".($showAddress ? "Address: {$schoolAddress}" : '').'
                            '.($showPhone ? " | Tel: {$schoolPhone}" : '').'
                            '.($showEmail ? " | Email: {$schoolEmail}" : '')."
                        </div>
                    </div>

                    <!-- Metadata Grid -->
                    <table class='ps-meta-table' style='margin-top: 6px;'>
                        <tr>
                            ".($showStudentPhoto ? "<td rowspan='3' style='width: 35px; text-align: center;'><div style='width: 25px; height: 25px; background: #e2e8f0; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 4px; color: #94a3b8;'>Photo</div></td>" : '')."
                            <td style='font-weight: bold; background: #f8fafc; width: 25%;'>Student Name:</td>
                            <td>Sophia Mercer</td>
                            <td style='font-weight: bold; background: #f8fafc; width: 25%;'>Admission No:</td>
                            <td>2607-0001-57</td>
                        </tr>
                        <tr>
                            <td style='font-weight: bold; background: #f8fafc;'>Class / Form:</td>
                            <td>Senior 5 Arts</td>
                            <td style='font-weight: bold; background: #f8fafc;'>Academic Period:</td>
                            <td>Term 1 (2025)</td>
                        </tr>
                        <tr>
                            <td style='font-weight: bold; background: #f8fafc;'>
                                ".($showClassRank ? 'Class Rank' : ($showStreamRank ? 'Stream Rank' : 'HBC Score'))."
                            </td>
                            <td style='font-weight: bold; color: {$accentColor};'>
                                ".($showClassRank ? '2nd of 25' : ($showStreamRank ? '4th of 120' : '9.20 / 10.00'))."
                            </td>
                            <td style='font-weight: bold; background: #f8fafc;'>Student ID:</td>
                            <td>VHS-1741271</td>
                        </tr>
                    </table>

                    <!-- Customizable Grades Table -->
                    <table class='ps-table'>
                        <thead>
                            <tr>
                                {$tableHeadersHtml}
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style='text-align: left; font-weight: bold;'>Mathematics</td>
                                ".(in_array('bot', $includedAssessments) ? '<td>85%</td>' : '').'
                                '.(in_array('mot', $includedAssessments) ? '<td>78%</td>' : '').'
                                '.(in_array('eot', $includedAssessments) ? '<td>82%</td>' : '').'
                                '.(in_array('c1', $includedAssessments) ? '<td>18/20</td>' : '').'
                                '.(in_array('c2', $includedAssessments) ? '<td>17/20</td>' : '').'
                                '.(in_array('c3', $includedAssessments) ? '<td>19/20</td>' : '').'
                                '.(in_array('exam', $includedAssessments) ? '<td>80%</td>' : '').'
                                '.($showClassAverage ? "<td style='color: #64748b;'>72.4%</td>" : '').'
                                '.($showStreamAverage ? "<td style='color: #64748b;'>70.1%</td>" : '').'
                                '.($showSubjectRank ? '<td>1st</td>' : '')."
                                <td style='font-weight: bold;'>A</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Outstanding Achievements Section -->
                    ".($showOutstandingAchievements ? "
                    <div style='font-weight: bold; font-size: 6.5px; margin-bottom: 2px; text-transform: uppercase; color: {$accentColor};'>Outstanding Achievements</div>
                    <div class='ps-remarks-container' style='font-style: italic; font-size: 5.5px; line-height: 1.3; color: {$successColor};'>
                        ★ First Place in National Mathematics Olympiad (Senior Category)<br>
                        ★ Captain of the School Debating Society (Outstanding Leadership)
                    </div>" : '').'

                    <!-- Competencies Mock -->
                    '.($showUbuntuCompetencies ? "
                    <div style='font-weight: bold; font-size: 6px; margin-bottom: 2px; text-transform: uppercase; color: {$accentColor};'>Unhu / Ubuntu Competencies</div>
                    ".($showUbuntuPercentage ? "<div style='font-weight: bold; font-size: 5px; margin-bottom: 2px; color: {$accentColor};'>Overall Ubuntu Rating: 86.4%</div>" : '')."
                    <table class='ps-table'>
                        <thead>
                            <tr>
                                <th style='text-align: left; width: 50%;'>Civic Competency</th>
                                <th style='width: 50%;'>Ubuntu Rating Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$unhuRowsHtml}
                        </tbody>
                    </table>" : '')."

                    <!-- Teacher Comments -->
                    <div class='ps-remarks-container'>
                        <div class='ps-remarks-title'>Class Teacher's Remark:</div>
                        <div class='ps-manual-line'>\"A very hardworking and consistent student.\"</div>
                    </div>

                    <!-- Next Term Fees Box -->
                    ".($showNextTermFees ? "
                    <table class='ps-meta-table'>
                        <tr>
                            <td style='width: 50%; line-height: 1.3;'>
                                <strong>Next Term Schedule:</strong><br>
                                Begins: {$nextTermBegins}<br>
                                Ends: {$nextTermEnds}
                            </td>
                            <td style='width: 50%; line-height: 1.3;'>
                                <strong>Next Term Fees Due:</strong><br>
                                Base Tuition: {$nextTermFees}
                            </td>
                        </tr>
                        ".(! empty($announcements) ? "
                        <tr>
                            <td colspan='2' style='line-height: 1.3; color: {$dangerColor};'>
                                <strong>Special Announcements:</strong><br>
                                {$announcements}
                            </td>
                        </tr>" : '').'
                    </table>' : '')."

                    <!-- Verification Warning -->
                    <div style='text-align: center; font-weight: bold; color: {$dangerColor}; font-size: 5px; margin-top: 4px;'>
                        ⚠️ This report card is invalid without a valid school seal or official stamp ⚠️
                    </div>

                </div>
            </div>
        ";
    }
}
