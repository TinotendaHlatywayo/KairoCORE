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
use Modules\Academics\Services\GradingScaleResolver;
use Modules\Admin\Services\PermissionRegistry;

class ReportTemplateResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Exams & Grading');
    }

    protected static ?string $model = ReportTemplate::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isVisible('academics')) {
            return false;
        }

        try {
            if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
                $permissionRegistry = app(PermissionRegistry::class);
                if (method_exists($permissionRegistry, 'checkAcademicPermission')) {
                    return $permissionRegistry->checkAcademicPermission('academic_ops.manage_reports');
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Permission check failed in ReportTemplateResource: '.$e->getMessage());
        }

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
                                    // TAB 1: SCOPES & THEME
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

                                             Forms\Components\Fieldset::make(__('Logo Size Configuration'))
                                                 ->schema([
                                                     Forms\Components\TextInput::make('layout_config.logo_width')
                                                         ->label(__('School Logo Width (px)'))
                                                         ->numeric()
                                                         ->default(55)
                                                         ->live(),
                                                 ])->columns(1),
                                        ]),

                                    // TAB 3: LAYOUT, ORIENTATION & SPACING
                                    Forms\Components\Tabs\Tab::make(__('3. Layout & Density'))
                                        ->icon('heroicon-o-arrows-pointing-out')
                                        ->schema([
                                             Forms\Components\Select::make('layout_config.page_orientation')
                                                 ->label(__('Page Orientation'))
                                                 ->options([
                                                     'portrait' => __('Portrait'),
                                                     'landscape' => __('Landscape'),
                                                 ])
                                                 ->default('landscape')
                                                 ->required()
                                                 ->live()
                                                 ->helperText(__('Landscape is recommended when many assessment columns are selected.')),

                                            Forms\Components\TextInput::make('layout_config.line_spacing')
                                                ->label(__('Line Spacing / Row Height (multiplier)'))
                                                ->numeric()
                                                ->step(0.1)
                                                ->default(1.2)
                                                ->live()
                                                ->required(),

                                            Forms\Components\TextInput::make('layout_config.table_padding')
                                                ->label(__('Table Cell Padding (px)'))
                                                ->numeric()
                                                ->default(5)
                                                ->live()
                                                ->required(),

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

                                    // TAB 4: ACADEMIC COLUMNS & WEIGHTING
                                    Forms\Components\Tabs\Tab::make(__('4. Academic Columns'))
                                        ->icon('heroicon-o-table-cells')
                                        ->schema([
                                             Forms\Components\Fieldset::make(__('Select Assessments to Include as Columns'))
                                                 ->schema([
                                                     Forms\Components\CheckboxList::make('layout_config.included_assessments')
                                                         ->label(__('Select Specific Tests / Assessments (e.g. Test 1, Test 2, Test 3, Exercise 1, Exam)'))
                                                         ->options(fn (): array => self::numberedAssessmentOptions())
                                                         ->columns(2)
                                                         ->live()
                                                         ->default(function (): array {
                                                             return array_slice(
                                                                 array_keys(self::numberedAssessmentOptions()),
                                                                 0,
                                                                 3
                                                             );
                                                         }),
                                                 ])->columns(1),

Forms\Components\Fieldset::make(__('Select Table Summary & Metric Columns'))
                                                  ->schema([
                                                      Forms\Components\Toggle::make('layout_config.show_overall_subject_mark')
                                                          ->label(__('Show Overall Subject Mark Column'))
                                                          ->helperText(__('Weighted from each assessment\'s contribution (Weighted %).'))
                                                          ->default(true)
                                                          ->live(),

                                                     Forms\Components\Toggle::make('layout_config.show_grade')
                                                         ->label(__('Show Subject Letter Grade Column'))
                                                         ->default(true)
                                                         ->live(),

                                                     Forms\Components\Toggle::make('layout_config.show_class_average')
                                                         ->label(__('Show Class Average Column'))
                                                         ->default(true)
                                                         ->live(),

                                                     Forms\Components\Toggle::make('layout_config.show_stream_average')
                                                         ->label(__('Show Stream Average Column'))
                                                         ->default(true)
                                                         ->live(),

                                                      Forms\Components\Toggle::make('layout_config.show_subject_position')
                                                          ->label(__('Show Subject Position / Rank Column'))
                                                          ->default(true)
                                                          ->live(),
                                                 ])->columns(2),
                                        ]),

                                    // TAB 5: POSITIONS & FEATURES
                                    Forms\Components\Tabs\Tab::make(__('5. Positions & Features'))
                                        ->icon('heroicon-o-academic-cap')
                                        ->schema([
                                             Forms\Components\Fieldset::make(__('Subject Position Calculation Scope'))
                                                 ->schema([
                                                     Forms\Components\Select::make('layout_config.ranking_scope')
                                                         ->label(__('Subject Position Calculation Scope'))
                                                         ->options([
                                                             'class' => __('Per Class (Position: 2 of 25)'),
                                                             'stream' => __('Per Stream (Stream Position: 4 of 60)'),
                                                             'both' => __('Show Both (Class & Stream)'),
                                                         ])
                                                         ->default('class')
                                                         ->required()
                                                         ->live(),

                                                     Forms\Components\Toggle::make('layout_config.show_class_position')
                                                         ->label(__('Display Class Position (e.g. Position: 2 of 25)'))
                                                         ->default(true)
                                                         ->live(),

                                                     Forms\Components\Toggle::make('layout_config.show_stream_position')
                                                         ->label(__('Display Stream Position (e.g. Stream Position: 4 of 60)'))
                                                         ->default(true)
                                                         ->live(),
                                                 ])->columns(1),

Forms\Components\Fieldset::make(__('Information Modules & Features'))
                                                  ->schema([
                                                      Forms\Components\Toggle::make('layout_config.show_student_photo')->label(__('Display Student Photo'))->default(true)->live(),
                                                      Forms\Components\Toggle::make('layout_config.show_outstanding_achievements')->label(__('Display Outstanding Achievements Section'))->default(true)->live(),
                                                      Forms\Components\Toggle::make('layout_config.show_next_term_fees')->label(__('Display Next Term Fees / Schedule Box'))->default(true)->live(),
                                                      Forms\Components\Toggle::make('layout_config.show_ubuntu_competencies')->label(__('Display Unhu/Ubuntu Skills Table'))->default(true)->live(),
                                                      Forms\Components\Toggle::make('layout_config.show_ubuntu_percentage')->label(__('Display Unhu Grade as a Percentage'))->default(true)->live(),
                                                      Forms\Components\Toggle::make('layout_config.show_grading_keys')->label(__('Display Grading Scales Key in Footer'))->default(true)->live(),
                                                  ])->columns(3),

                                              Forms\Components\Fieldset::make(__('Report Sign-off & Validation'))
                                                  ->schema([
                                                      Forms\Components\Toggle::make('layout_config.show_qr_verification')
                                                          ->label(__('Display QR Verification Code'))
                                                          ->helperText(__('Scannable QR code that parents can use to verify the report is genuine.'))
                                                          ->default(true)
                                                          ->live(),
                                                      Forms\Components\Toggle::make('layout_config.show_class_teacher_remarks')
                                                          ->label(__('Display Class Teacher Remarks (per learner)'))
                                                          ->default(true)
                                                          ->live(),
                                                      Forms\Components\Toggle::make('layout_config.show_class_teacher_signature')
                                                          ->label(__('Display Class Teacher Signature Line'))
                                                          ->default(true)
                                                          ->live(),
                                                      Forms\Components\Toggle::make('layout_config.show_principal_remarks')
                                                          ->label(__('Display Principal\'s Remarks'))
                                                          ->default(true)
                                                          ->live(),
                                                      Forms\Components\Toggle::make('layout_config.show_headmaster_stamp')
                                                          ->label(__('Display Headmaster / Principal Stamp'))
                                                          ->default(true)
                                                          ->live(),
                                                      Forms\Components\Toggle::make('layout_config.show_subject_teacher_remarks')
                                                          ->label(__('Show Subject Teacher Remarks Column (per student)'))
                                                          ->default(false)
                                                          ->live(),
                                                  ])->columns(2),
                                        ]),

                                    // TAB 6: SCHEDULE & FEES SETTINGS
                                    Forms\Components\Tabs\Tab::make(__('6. Schedule & Fees'))
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

    protected static function generateLivePreviewHtml(Forms\Get $get): string
    {
        $school = null;
        try {
            $school = School::find(app('current_tenant')->id);
        } catch (\Exception $e) {
            try {
                $school = School::first();
            } catch (\Exception $e2) {
            }
        }

        $schoolName = $school && $school->name ? $school->name : 'School Name Not Set';
        $schoolMotto = $school && $school->motto ? $school->motto : 'Knowledge Lights the Way';
        $schoolPhone = $school && $school->phone ? $school->phone : '0750002345';
        $schoolEmail = $school && $school->email_address ? $school->email_address : 'info@school.com';
        $schoolAddress = $school && $school->physical_address ? $school->physical_address : 'P.O BOX 001 KLA';

        $logoBase64 = '';
        $schoolId = $school ? $school->id : null;
        if ($schoolId && file_exists(public_path($schoolId.'_logo.png'))) {
            $logoBase64 = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path($schoolId.'_logo.png')));
        } elseif ($school && !empty($school->logo_path) && file_exists(public_path($school->logo_path))) {
            $logoBase64 = 'data:image/'.pathinfo($school->logo_path, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents(public_path($school->logo_path)));
        } elseif (file_exists(public_path('images/school_logo.png'))) {
            $logoBase64 = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/school_logo.png')));
        } else {
            $defaultLogo = public_path('images/id-card-default-logo.png');
            if (file_exists($defaultLogo)) {
                $logoBase64 = 'data:image/png;base64,'.base64_encode(file_get_contents($defaultLogo));
            }
        }

        $theme = $get('design_theme') ?? 'classic_line';
        $orientation = $get('layout_config.page_orientation') ?? 'landscape';
        $fontFamily = $get('layout_config.font_family') ?? 'sans-serif';
        $bodyTextColor = $get('layout_config.body_text_color') ?? '#1e293b';
        $headerColor = $get('layout_config.header_color') ?? '#1e3a8a';
        $headerSize = $get('layout_config.header_font_size') ?? 20;
        $tableHeaderBg = $get('layout_config.table_header_bg') ?? '#f1f5f9';
        $lineSpacing = $get('layout_config.line_spacing') ?? 1.2;
        $logoWidth = $get('layout_config.logo_width') ?? 55;
        $previewLogoWidth = min(round($logoWidth * 0.6), 48);

        $accentColor = ($theme === 'minimal_compact') ? '#111827' : $headerColor;
        $successColor = ($theme === 'minimal_compact') ? '#111827' : '#16a34a';
        $dangerColor = ($theme === 'minimal_compact') ? '#111827' : '#b91c1c';

        $marginV = $get('layout_config.page_margin_v') ?? 12;
        $marginH = $get('layout_config.page_margin_h') ?? 15;
        $padding = $get('layout_config.table_padding') ?? 5;
        $borderW = $get('layout_config.page_border_width') ?? 0;
        $borderC = $get('layout_config.page_border_color') ?? '#fbbf24';

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

        $showClassPos = $get('layout_config.show_class_position') ?? true;
        $showStreamPos = $get('layout_config.show_stream_position') ?? true;
        $showSubjectRank = $get('layout_config.show_subject_position') ?? true;
        $showOutstandingAchievements = $get('layout_config.show_outstanding_achievements') ?? true;
        $showGradingKeys = $get('layout_config.show_grading_keys') ?? true;

        $showQrVerification = $get('layout_config.show_qr_verification') ?? true;
        $showClassRemarks = $get('layout_config.show_class_teacher_remarks') ?? true;
        $showClassSignature = $get('layout_config.show_class_teacher_signature') ?? true;
        $showPrincipalRemarks = $get('layout_config.show_principal_remarks') ?? true;
        $showHeadmasterStamp = $get('layout_config.show_headmaster_stamp') ?? true;
        $showSubjectRemarks = $get('layout_config.show_subject_teacher_remarks') ?? false;
        $showGradeColumn = $get('layout_config.show_grade') ?? true;

        $includedAssessments = $get('layout_config.included_assessments') ?? [];
        $showClassAverage = $get('layout_config.show_class_average') ?? true;
        $showStreamAverage = $get('layout_config.show_stream_average') ?? true;
        $showOverallMark = $get('layout_config.show_overall_subject_mark') ?? true;

        $nextTermBegins = $get('layout_config.next_term_begins') ? date('d-M-Y', strtotime($get('layout_config.next_term_begins'))) : date('d-M-Y', strtotime('+1 month'));
        $nextTermEnds = $get('layout_config.next_term_ends') ? date('d-M-Y', strtotime($get('layout_config.next_term_ends'))) : date('d-M-Y', strtotime('+4 months'));
        $nextTermFees = $get('layout_config.next_term_fees') ?? '$800.00 USD';
        $announcements = $get('layout_config.special_announcements') ?? '';

        $tableHeadersHtml = '<th style="width: 9%;">Code</th><th style="text-align: left; width: 26%;">Subject</th>';
        foreach ($includedAssessments as $assessmentId) {
            $assessmentType = AssessmentType::find($assessmentId);
            $testLabel = $assessmentType ? $assessmentType->name : 'Test';
            $tableHeadersHtml .= "<th style='width: 9%;'>{$testLabel}</th>";
        }
        if ($showOverallMark) {
            $tableHeadersHtml .= '<th style="width: 10%;">Overall</th>';
        }
        if ($showGradeColumn) {
            $tableHeadersHtml .= '<th style="width: 7%;">Grade</th>';
        }
        if ($showClassAverage) {
            $tableHeadersHtml .= '<th style="width: 10%;">Class Avg</th>';
        }
        if ($showStreamAverage) {
            $tableHeadersHtml .= '<th style="width: 10%;">Stream Avg</th>';
        }
        if ($showSubjectRank) {
            $tableHeadersHtml .= '<th style="width: 7%;">Rank</th>';
        }
        if ($showSubjectRemarks) {
            $tableHeadersHtml .= '<th style="width: 15%;">Subject Remark</th>';
        }

        $tableCellsHtml = "<td style='font-family: monospace; font-weight: bold;'>MATH</td><td style='text-align: left; font-weight: bold;'>Mathematics</td>";
        foreach ($includedAssessments as $assessmentId) {
            $tableCellsHtml .= "<td>82%</td>";
        }
        if ($showOverallMark) {
            $tableCellsHtml .= "<td style='font-weight: bold; color: {$accentColor};'>81%</td>";
        }
        if ($showGradeColumn) {
            $tableCellsHtml .= "<td style='font-weight: bold;'>A</td>";
        }
        if ($showClassAverage) {
            $tableCellsHtml .= "<td style='color: #64748b;'>72.4%</td>";
        }
        if ($showStreamAverage) {
            $tableCellsHtml .= "<td style='color: #64748b;'>75.0%</td>";
        }
        if ($showSubjectRank) {
            $tableCellsHtml .= "<td>1st</td>";
        }
        if ($showSubjectRemarks) {
            $tableCellsHtml .= "<td style='text-align: left; font-style: italic;'>Consistent effort and great progress.</td>";
        }

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

        $canvasWidth = ($orientation === 'landscape') ? '400px' : '320px';

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
                    width: {$canvasWidth};
                    min-height: 450px;
                    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                    box-sizing: border-box;
                    line-height: {$lineSpacing};
                }
                .ps-school-name { font-weight: bold; text-transform: uppercase; margin: 0; }
                .ps-school-motto { font-size: 5px; font-style: italic; margin-top: 1px; text-transform: uppercase; }

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
                    
                    <!-- Branded Header (Actual School Logo, No Emojis) -->
                    <div class='school-hdr'>
                        <table style='width: 100%; border: none; margin-bottom: 0;'>
                            <tr style='border: none;'>
                                " . ($showSchoolLogo && !empty($logoBase64) ? "<td style='width: 30px; text-align: left; border: none; padding: 0;'><img src='{$logoBase64}' style='width: {$previewLogoWidth}px; height: auto; object-fit: contain;' /></td>" : "") . "
                                <td style='text-align: center; border: none; padding: 0;'>
                                    <div class='ps-school-name' style='font-size: calc({$headerSize}px * 0.6); color: {$accentColor};'>{$schoolName}</div>
                                    " . ($showSchoolMotto ? "<div class='ps-school-motto'>\"{$schoolMotto}\"</div>" : "") . "
                                    <div style='font-size: 5px; color: #64748b; margin-top: 1px;'>
                                        " . ($showAddress ? "Address: {$schoolAddress}" : "") . "
                                        " . ($showPhone ? " | Tel: {$schoolPhone}" : "") . "
                                        " . ($showEmail ? " | Email: {$schoolEmail}" : "") . "
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Metadata Grid -->
                    <table class='ps-meta-table' style='margin-top: 6px;'>
                        <tr>
                            " . ($showStudentPhoto ? "<td rowspan='3' style='width: 35px; text-align: center;'><div style='width: 25px; height: 25px; background: #e2e8f0; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 4px; color: #94a3b8;'>Photo</div></td>" : "") . "
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
                            <td style='font-weight: bold; background: #f8fafc;'>Rankings & Standing:</td>
                            <td style='font-weight: bold; color: {$accentColor};' colspan='3'>
                                " . ($showClassPos ? "Position: 2 of 25" : "") . "
                                " . ($showStreamPos ? " | Stream Position: 4 of 60" : "") . "
                            </td>
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
                                {$tableCellsHtml}
                            </tr>
                        </tbody>
                    </table>

                    <!-- Outstanding Achievements Section -->
                    " . ($showOutstandingAchievements ? "
                    <div style='font-weight: bold; font-size: 6.5px; margin-bottom: 2px; text-transform: uppercase; color: {$accentColor};'>Outstanding Achievements</div>
                    <div class='ps-remarks-container' style='font-style: italic; font-size: 5.5px; line-height: 1.3; color: {$successColor};'>
                        ★ First Place in National Mathematics Olympiad (Senior Category)<br>
                        ★ Captain of the School Debating Society (Outstanding Leadership)
                    </div>" : "") . "

                    <!-- Competencies Mock -->
                    " . ($showUbuntuCompetencies ? "
                    <div style='font-weight: bold; font-size: 6px; margin-bottom: 2px; text-transform: uppercase; color: {$accentColor};'>Unhu / Ubuntu Competencies</div>
                    " . ($showUbuntuPercentage ? "<div style='font-weight: bold; font-size: 5px; margin-bottom: 2px; color: {$accentColor};'>Overall Ubuntu Rating: 86.4%</div>" : "") . "
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
                    </table>" : "") . "

                    <!-- Teacher Comments -->
                    " . ($showClassRemarks ? "
                    <div class='ps-remarks-container'>
                        <div class='ps-remarks-title'>Class Teacher's Remark:</div>
                        <div class='ps-manual-line'>\"A very hardworking and consistent student.\"</div>
                    </div>" : "") . "

                    " . ($showPrincipalRemarks ? "
                    <div class='ps-remarks-container'>
                        <div class='ps-remarks-title'>Principal's Remark:</div>
                        <div class='ps-manual-line'>\"Excellent results. Keep up the high standard.\"</div>
                    </div>" : "") . "

                    <!-- Next Term Fees Box -->
                    " . ($showNextTermFees ? "
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
                        " . (!empty($announcements) ? "
                        <tr>
                            <td colspan='2' style='line-height: 1.3; color: {$dangerColor};'>
                                <strong>Special Announcements:</strong><br>
                                {$announcements}
                            </td>
                        </tr>" : "") . "
                    </table>" : "") . "

                    <!-- Grading Scales Key in Footer -->
                    " . ($showGradingKeys ? self::gradingKeyFooterHtml($school, $theme) : "") . "

                    <!-- Signatures & QR Verification -->
                    <div style='margin-top: 8px;'>
                        <table style='width: 100%; border: none; margin-bottom: 0;'>
                            <tr style='border: none;'>
                                <td style='width: 62%; border: none; vertical-align: bottom;'>
                                    <div style='font-size: 5.5px; color: #64748b;'>
                                        " . ($showClassSignature ? "<div style='display: inline-block; width: 45%; margin-right: 5%; border-top: 0.5px solid #94a3b8; padding-top: 1px; text-align: center; box-sizing: border-box;'>Class Teacher Signature</div>" : "") . "
                                        " . ($showHeadmasterStamp ? "<div style='display: inline-block; width: 45%; border-top: 0.5px solid #94a3b8; padding-top: 1px; text-align: center; box-sizing: border-box;'>Headmaster / Principal Stamp</div>" : "") . "
                                    </div>
                                </td>
                                <td style='width: 38%; border: none; text-align: right; vertical-align: bottom;'>
                                    " . ($showQrVerification ? "
                                    <div style='display: inline-block; text-align: center;'>
                                        <div style='width: 22px; height: 22px; border: 0.5px solid #cbd5e1; background: #f8fafc; display: flex; align-items: center; justify-content: center;'>
                                            <span style='font-size: 4.5px; color: #94a3b8; letter-spacing: 1px;'>QR</span>
                                        </div>
                                        <div style='font-size: 4px; color: #94a3b8; margin-top: 1px;'>Scan to Verify</div>
                                    </div>" : "") . "
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Verification Warning -->
                    <div style='text-align: center; font-weight: bold; color: {$dangerColor}; font-size: 5px; margin-top: 4px;'>
                        ⚠️ This report card is invalid without a valid school seal or official stamp ⚠️
                    </div>

                </div>
            </div>
        ";
    }

    protected static function numberedAssessmentOptions(): array
    {
        $tenantId = app('current_tenant')->id ?? null;
        if (! $tenantId) {
            return [];
        }
        $types = AssessmentType::withoutGlobalScopes()
            ->where('school_id', $tenantId)
            ->orderBy('id')
            ->get();

        $options = [];
        $seen = [];
        foreach ($types as $type) {
            $name = trim($type->name);
            $key = mb_strtolower($name);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $options[$type->id] = $name;
        }

        if (empty($options)) {
            $options = [
                1 => 'Test 1',
                2 => 'Test 2',
                3 => 'Exercise 1',
                4 => 'Exercise 2',
                5 => 'End of Term Exam',
            ];
        }

        return $options;
    }

    protected static function gradingKeyFooterHtml($school, string $theme): string
    {
        $gradingScale = self::resolveDefaultGradingScale($school);
        if (empty($gradingScale)) {
            return '';
        }
        $isDark = in_array($theme, ['modern_dark', 'dark_minimal']);
        $bgColor = $isDark ? '#0f172a' : '#f8fafc';
        $textColor = $isDark ? '#f1f5f9' : '#0f172a';
        $accentColor = $isDark ? '#38bdf8' : '#2563eb';

        $items = '';
        foreach ($gradingScale as $grade => $range) {
            $items .= "<span style='display:inline-block; margin-right:12px; font-size:5px;'><strong style='color:{$accentColor}'>{$grade}</strong> {$range}</span>";
        }

        return "
            <div style='margin-top:6px; padding-top:4px; border-top:1px dashed {$accentColor}; font-size:5px; color:{$textColor}; background:{$bgColor};'>
                <strong style='color:{$accentColor}'>".__('Grading Scale Key').":</strong> {$items}
            </div>
        ";
    }

    protected static function resolveDefaultGradingScale($school): array
    {
        return GradingScaleResolver::key($school?->id ?? null);
    }
}
