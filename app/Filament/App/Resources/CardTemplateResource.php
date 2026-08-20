<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\CardTemplateResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Modules\Students\Models\CardTemplate;
use Modules\Students\Models\Student;

class CardTemplateResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Students');
    }

    protected static ?string $model = CardTemplate::class;

    protected static ?string $navigationGroup = 'Students';

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationLabel = 'ID Card Designer';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static bool $shouldRegisterNavigation = true;

    public static function form(Form $form): Form
    {
        // High-fidelity distinct font selections
        $fontOptions = [
            'sans-serif' => 'Helvetica / Arial (Modern Clean)',
            'serif' => 'Georgia / Times New Roman (Formal Academic)',
            'monospace' => 'Courier New / Consolas (Retro Tech)',
            'Brush Script MT, cursive' => 'Brush Script (Fancy Cursive)',
            'Impact, Charcoal, sans-serif' => 'Impact (Bold Block)',
            'Trebuchet MS, sans-serif' => 'Trebuchet MS (Geometric Clean)',
            'Copperplate, serif' => 'Copperplate (Engraved Header)',
            'Palatino, serif' => 'Palatino (Elegant Literary)',
        ];

        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // Left Column: Controls & Styling Fields (Span 2)
                        Forms\Components\Group::make([
                            Forms\Components\Section::make('Template Details')
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->live()
                                        ->placeholder(__('e.g. Primary Portrait Badge')),

                                    Forms\Components\Select::make('layout_config.target_group')
                                        ->label(__('Active Target Cohort'))
                                        ->options([
                                            'all' => __('All Students (Global Default)'),
                                            'ecd' => __('ECD Students (ECD A & B)'),
                                            'primary' => __('Primary Students (Grades 1 to 7)'),
                                            'secondary' => __('Secondary Students (Forms 1 to 4)'),
                                            'alevel' => __('A-Level Students (Lower & Upper Six)'),
                                        ])
                                        ->default('all')
                                        ->live()
                                        ->required(),

                                    Forms\Components\Select::make('layout_config.design_theme')
                                        ->label(__('Visual Theme Style'))
                                        ->options([
                                            'classic' => __('Classic Academic (Double Border)'),
                                            'modern' => __('Modern Glassmorphic (Translucent Overlay)'),
                                            'corporate' => __('Corporate Minimal (Slate Black & Steel)'),
                                            'minimalist' => __('Minimalist Zen (Clean White Canvas)'),
                                            'premium' => __('Premium Royal Gold (Navy & Gold Accent)'),
                                            'government' => __('State Institutional (Emerald Shield)'),
                                            'playful' => __('Playful Kids (ECD Pink & Orange Bubble)'),
                                            'collegiate' => __('Collegiate Varsity (Crimson Panel & Text Shadow)'),
                                            'tech' => __('Cyber Tech Grid (Dark Mode Neon Cyan)'),
                                            'vintage' => __('Vintage Retro Academy (Aged Parchment & Sepia)'),
                                        ])
                                        ->default('classic')
                                        ->live()
                                        ->required(),

                                    Forms\Components\Select::make('orientation')
                                        ->options([
                                            'portrait' => 'Portrait (Vertical)',
                                            'landscape' => 'Landscape (Horizontal)',
                                        ])
                                        ->default('portrait')
                                        ->live()
                                        ->required(),

                                    Forms\Components\Select::make('barcode_format')
                                        ->options([
                                            'Code128' => 'Code 128 (Standard)',
                                            'Code39' => 'Code 39',
                                            'EAN13' => 'EAN-13',
                                        ])
                                        ->default('Code128')
                                        ->live()
                                        ->required(),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label(__('Set as Active Group Template'))
                                        ->helperText(__('Setting this active deactivates all other templates assigned to the same cohort.'))
                                        ->default(false),
                                ])->columns(2),

                            Forms\Components\Section::make('Included Information on ID Card')
                                ->description(__('Select elements to render.'))
                                ->schema([
                                    Forms\Components\Toggle::make('layout_config.show_school_header')->label(__('Show School Header'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_school_motto')->label(__('Show School Motto'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_photo')->label(__('Show Photo Frame'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_name')->label(__('Show Student Name'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_class')->label(__('Show Class Level'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_student_id')->label(__('Show Student ID'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_admission_no')->label(__('Show Admission No'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_expiry')->label(__('Show Expiry Date'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_qr')->label(__('Show QR Code'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_barcode')->label(__('Show Barcode'))->default(true)->live(),
                                ])->columns(3),

                            Forms\Components\Section::make('Layout Canvas Spacing & Border Settings')
                                ->schema([
                                    Forms\Components\TextInput::make('layout_config.card_padding')
                                        ->label(__('Inner Padding (px)'))
                                        ->numeric()
                                        ->default(10)
                                        ->live()
                                        ->required(),

                                    Forms\Components\TextInput::make('layout_config.card_margin_v')
                                        ->label(__('Outer Spacing V / Vertical Margin (px)'))
                                        ->numeric()
                                        ->default(0)
                                        ->live()
                                        ->required(),

                                    Forms\Components\TextInput::make('layout_config.card_margin_h')
                                        ->label(__('Outer Spacing H / Horizontal Margin (px)'))
                                        ->numeric()
                                        ->default(0)
                                        ->live()
                                        ->required(),

                                    Forms\Components\TextInput::make('layout_config.card_border_width')
                                        ->label(__('Border Width (px)'))
                                        ->numeric()
                                        ->default(3)
                                        ->live()
                                        ->required(),

                                    Forms\Components\ColorPicker::make('layout_config.card_border_color')
                                        ->label(__('Border Color'))
                                        ->default('#1e3a8a')
                                        ->live()
                                        ->required(),

                                    Forms\Components\ColorPicker::make('layout_config.canvas_bg_color')
                                        ->label(__('Card Canvas BG Color'))
                                        ->default('#ffffff')
                                        ->live()
                                        ->required(),

                                    Forms\Components\ColorPicker::make('layout_config.header_bg_color')
                                        ->label(__('Header Area BG Color'))
                                        ->default('#1e3a8a')
                                        ->live()
                                        ->required(),

                                    Forms\Components\ColorPicker::make('layout_config.header_text_color')
                                        ->label(__('Header Text Color'))
                                        ->default('#ffffff')
                                        ->live()
                                        ->required(),
                                ])->columns(3),

                            Forms\Components\Section::make('Element Position, Color & Font Controls')
                                ->schema([
                                    Forms\Components\Tabs::make('Elements Layout Configuration')
                                        ->tabs([
                                            Forms\Components\Tabs\Tab::make('Name Text')
                                                ->schema([
                                                    Forms\Components\Select::make('layout_config.name_font_family')
                                                        ->label(__('Font Style'))
                                                        ->options($fontOptions)->default('sans-serif')->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.name_x')->label(__('X Offset (%)'))->numeric()->default(10)->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.name_y')->label(__('Y Offset (%)'))->numeric()->default(45)->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.name_font_size')->label(__('Font Size (px)'))->numeric()->default(14)->live()->required(),
                                                    Forms\Components\ColorPicker::make('layout_config.name_color')->label(__('Font Color'))->default('#1e3a8a')->live()->required(),
                                                    Forms\Components\Toggle::make('layout_config.name_is_bold')->label(__('Bold Text'))->default(true)->live(),
                                                    Forms\Components\Toggle::make('layout_config.name_is_italic')->label(__('Italic Text'))->default(false)->live(),
                                                ])->columns(2),

                                            Forms\Components\Tabs\Tab::make('Class Text')
                                                ->schema([
                                                    Forms\Components\Select::make('layout_config.class_font_family')
                                                        ->label(__('Font Style'))
                                                        ->options($fontOptions)->default('sans-serif')->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.class_x')->label(__('X Offset (%)'))->numeric()->default(10)->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.class_y')->label(__('Y Offset (%)'))->numeric()->default(52)->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.class_font_size')->label(__('Font Size (px)'))->numeric()->default(9)->live()->required(),
                                                    Forms\Components\ColorPicker::make('layout_config.class_color')->label(__('Font Color'))->default('#64748b')->live()->required(),
                                                    Forms\Components\Toggle::make('layout_config.class_is_bold')->label(__('Bold Text'))->default(false)->live(),
                                                    Forms\Components\Toggle::make('layout_config.class_is_italic')->label(__('Italic Text'))->default(false)->live(),
                                                ])->columns(2),

                                            Forms\Components\Tabs\Tab::make('School Motto')
                                                ->schema([
                                                    Forms\Components\Select::make('layout_config.motto_font_family')
                                                        ->label(__('Font Style'))
                                                        ->options($fontOptions)->default('sans-serif')->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.motto_x')->label(__('X Offset (%)'))->numeric()->default(10)->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.motto_y')->label(__('Y Offset (%)'))->numeric()->default(8)->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.motto_font_size')->label(__('Font Size (px)'))->numeric()->default(7)->live()->required(),
                                                    Forms\Components\ColorPicker::make('layout_config.motto_color')->label(__('Font Color'))->default('#cbd5e1')->live()->required(),
                                                    Forms\Components\Toggle::make('layout_config.motto_is_bold')->label(__('Bold Text'))->default(false)->live(),
                                                    Forms\Components\Toggle::make('layout_config.motto_is_italic')->label(__('Italic Text'))->default(true)->live(),
                                                ])->columns(2),

                                            Forms\Components\Tabs\Tab::make('Photo Frame')
                                                ->schema([
                                                    Forms\Components\TextInput::make('layout_config.photo_x')->label(__('X Offset (%)'))->numeric()->default(35)->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.photo_y')->label(__('Y Offset (%)'))->numeric()->default(15)->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.photo_width')->label(__('Width (%)'))->numeric()->default(30)->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.photo_height')->label(__('Height (%)'))->numeric()->default(25)->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.photo_rounded_corners')->label(__('Corner Radius (px)'))->numeric()->default(8)->live()->required(),
                                                ])->columns(3),

                                            Forms\Components\Tabs\Tab::make('QR & Barcode Layout')
                                                ->schema([
                                                    Forms\Components\Fieldset::make('QR Security Verification')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('layout_config.qr_x')->label(__('X Offset (%)'))->numeric()->default(10)->live()->required(),
                                                            Forms\Components\TextInput::make('layout_config.qr_y')->label(__('Y Offset (%)'))->numeric()->default(70)->live()->required(),
                                                            Forms\Components\TextInput::make('layout_config.qr_size')->label(__('QR Dimension (px)'))->numeric()->default(50)->live()->required(),
                                                        ])->columns(3),

                                                    Forms\Components\Fieldset::make('Barcode Coordinates')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('layout_config.barcode_x')->label(__('X Offset (%)'))->numeric()->default(10)->live()->required(),
                                                            Forms\Components\TextInput::make('layout_config.barcode_y')->label(__('Y Offset (%)'))->numeric()->default(82)->live()->required(),
                                                            Forms\Components\TextInput::make('layout_config.barcode_width')->label(__('Width (%)'))->numeric()->default(80)->live()->required(),
                                                            Forms\Components\TextInput::make('layout_config.barcode_height')->label(__('Height (px)'))->numeric()->default(30)->live()->required(),
                                                            Forms\Components\ColorPicker::make('layout_config.barcode_text_color')->label(__('Code Text Color'))->default('#000000')->live()->required(),
                                                        ])->columns(3),
                                                ]),

                                            Forms\Components\Tabs\Tab::make('Metadata Text block')
                                                ->schema([
                                                    Forms\Components\Select::make('layout_config.meta_font_family')
                                                        ->label(__('Font Style'))
                                                        ->options($fontOptions)->default('sans-serif')->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.meta_x')->label(__('X Offset (%)'))->numeric()->default(10)->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.meta_y')->label(__('Y Offset (%)'))->numeric()->default(58)->live()->required(),
                                                    Forms\Components\TextInput::make('layout_config.meta_font_size')->label(__('Font Size (px)'))->numeric()->default(8)->live()->required(),
                                                    Forms\Components\ColorPicker::make('layout_config.meta_color')->label(__('Font Color'))->default('#334155')->live()->required(),
                                                    Forms\Components\Toggle::make('layout_config.meta_is_bold')->label(__('Bold Text'))->default(false)->live(),
                                                    Forms\Components\Toggle::make('layout_config.meta_is_italic')->label(__('Italic Text'))->default(false)->live(),
                                                ])->columns(2),

                                            Forms\Components\Tabs\Tab::make('Custom Text Lines')
                                                ->schema([
                                                    Forms\Components\Repeater::make('layout_config.custom_texts')
                                                        ->label(__('Add Custom Labels / Mottos / Text Elements'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('text')->label(__('Label Text'))->required()->live(),
                                                            Forms\Components\TextInput::make('x')->label(__('X Offset (%)'))->numeric()->default(10)->live()->required(),
                                                            Forms\Components\TextInput::make('y')->label(__('Y Offset (%)'))->numeric()->default(65)->live()->required(),
                                                            Forms\Components\TextInput::make('font_size')->label(__('Size (px)'))->numeric()->default(10)->live()->required(),
                                                            Forms\Components\ColorPicker::make('color')->label(__('Color'))->default('#000000')->live()->required(),
                                                            Forms\Components\Select::make('font_family')
                                                                ->options($fontOptions)->default('sans-serif')->live()->required(),
                                                            Forms\Components\Toggle::make('is_bold')->label(__('Bold'))->live(),
                                                            Forms\Components\Toggle::make('is_italic')->label(__('Italic'))->live(),
                                                        ])
                                                        ->columns(4)
                                                        ->live()
                                                        ->columnSpanFull(),
                                                ]),
                                        ]),
                                ]),
                        ])->columnSpan(2),

                        // Right Column: Interactive WYSIWYG Realtime Preview Panel (Span 1)
                        Forms\Components\Group::make([
                            Forms\Components\Section::make('Interactive Live Simulator')
                                ->description(__('Simulates theme styling on edit changes.'))
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
                Tables\Columns\TextColumn::make('orientation')->badge()->color('info'),
                Tables\Columns\TextColumn::make('barcode_format')->label(__('Barcode Standard')),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label(__('Active')),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label(__('Preview'))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => route('students.print-cards', [
                        'scope' => 'selected',
                        'ids' => Student::where('school_id', $record->school_id)->first()?->id ?? 0,
                        'layout' => 'pvc',
                        'template_id' => $record->id,
                    ]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('activate')
                    ->label(__('Set Active'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => ! $record->is_active)
                    ->action(function ($record) {
                        CardTemplate::where('school_id', $record->school_id)
                            ->update(['is_active' => false]);

                        $record->update(['is_active' => true]);

                        Notification::make()
                            ->title(__('Active Template Swapped'))
                            ->body("{$record->name} is now set as the active printing template.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            // FIXED: Replaced double colons with backslashes
            'index' => Pages\ListCardTemplates::route('/'),
            'create' => Pages\CreateCardTemplate::route('/create'),
            'edit' => Pages\EditCardTemplate::route('/{record}/edit'),
        ];
    }

    protected static function generateLivePreviewHtml(Forms\Get $get): string
    {
        $orientation = $get('orientation') ?? 'portrait';
        $theme = $get('layout_config.design_theme') ?? 'classic';

        $photoX = $get('layout_config.photo_x') ?? 35;
        $photoY = $get('layout_config.photo_y') ?? 15;
        $photoW = $get('layout_config.photo_width') ?? 30;
        $photoH = $get('layout_config.photo_height') ?? 25;
        $photoRadius = $get('layout_config.photo_rounded_corners') ?? 8;

        $nameFontFamily = $get('layout_config.name_font_family') ?? 'sans-serif';
        $nameX = $get('layout_config.name_x') ?? 10;
        $nameY = $get('layout_config.name_y') ?? 45;
        $nameFont = $get('layout_config.name_font_size') ?? 14;
        $nameColor = $get('layout_config.name_color') ?? '#1e3a8a';
        $nameWeight = ($get('layout_config.name_is_bold') ?? true) ? 'bold' : 'normal';
        $nameStyle = ($get('layout_config.name_is_italic') ?? false) ? 'italic' : 'normal';

        $classFontFamily = $get('layout_config.class_font_family') ?? 'sans-serif';
        $classX = $get('layout_config.class_x') ?? 10;
        $classY = $get('layout_config.class_y') ?? 52;
        $classFont = $get('layout_config.class_font_size') ?? 9;
        $classColor = $get('layout_config.class_color') ?? '#64748b';
        $classWeight = ($get('layout_config.class_is_bold') ?? false) ? 'bold' : 'normal';
        $classStyle = ($get('layout_config.class_is_italic') ?? false) ? 'italic' : 'normal';

        $mottoFontFamily = $get('layout_config.motto_font_family') ?? 'sans-serif';
        $mottoX = $get('layout_config.motto_x') ?? 10;
        $mottoY = $get('layout_config.motto_y') ?? 8;
        $mottoFont = $get('layout_config.motto_font_size') ?? 7;
        $mottoColor = $get('layout_config.motto_color') ?? '#cbd5e1';
        $mottoWeight = ($get('layout_config.motto_is_bold') ?? false) ? 'bold' : 'normal';
        $mottoStyle = ($get('layout_config.motto_is_italic') ?? true) ? 'italic' : 'normal';

        $qrX = $get('layout_config.qr_x') ?? 10;
        $qrY = $get('layout_config.qr_y') ?? 70;
        $qrSize = $get('layout_config.qr_size') ?? 50;

        $barX = $get('layout_config.barcode_x') ?? 10;
        $barY = $get('layout_config.barcode_y') ?? 82;
        $barW = $get('layout_config.barcode_width') ?? 80;
        $barH = $get('layout_config.barcode_height') ?? 30;
        $barTextColor = $get('layout_config.barcode_text_color') ?? '#000000';

        $metaFontFamily = $get('layout_config.meta_font_family') ?? 'sans-serif';
        $metaX = $get('layout_config.meta_x') ?? 10;
        $metaY = $get('layout_config.meta_y') ?? 58;
        $metaFont = $get('layout_config.meta_font_size') ?? 8;
        $metaColor = $get('layout_config.meta_color') ?? '#334155';
        $metaWeight = ($get('layout_config.meta_is_bold') ?? false) ? 'bold' : 'normal';
        $metaStyle = ($get('layout_config.meta_is_italic') ?? false) ? 'italic' : 'normal';

        $padding = $get('layout_config.card_padding') ?? 10;
        $marginV = $get('layout_config.card_margin_v') ?? 0;
        $marginH = $get('layout_config.card_margin_h') ?? 0;
        $borderW = $get('layout_config.card_border_width') ?? 3;
        $borderC = $get('layout_config.card_border_color') ?? '#1e3a8a';
        $canvasBgColor = $get('layout_config.canvas_bg_color') ?? '#ffffff';
        $headerBgColor = $get('layout_config.header_bg_color') ?? '#1e3a8a';
        $headerTextColor = $get('layout_config.header_text_color') ?? '#ffffff';

        // Information visibility switches
        $showSchoolHeader = $get('layout_config.show_school_header') ?? true;
        $showSchoolMotto = $get('layout_config.show_school_motto') ?? true;
        $showPhoto = $get('layout_config.show_photo') ?? true;
        $showName = $get('layout_config.show_name') ?? true;
        $showClass = $get('layout_config.show_class') ?? true;
        $showStudentID = $get('layout_config.show_student_id') ?? true;
        $showAdmissionNo = $get('layout_config.show_admission_no') ?? true;
        $showExpiry = $get('layout_config.show_expiry') ?? true;
        $showQR = $get('layout_config.show_qr') ?? true;
        $showBarcode = $get('layout_config.show_barcode') ?? true;

        $dimensions = ($orientation === 'landscape')
            ? 'width: 320px; height: 200px;'
            : 'width: 200px; height: 320px;';

        $qrDataPayload = urlencode("Name: Sophia Mercer\nID: R2607-0184-74\nAdm No: 2607-0001-57\nSchool: School Core Academy\nExpiry: 31-Dec-2029");
        $qrImageSource = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&format=jpg&data={$qrDataPayload}";

        // Compile custom text components dynamically inside the Live Simulator
        $customTexts = $get('layout_config.custom_texts') ?? [];
        $customTextsHtml = '';
        foreach ($customTexts as $ct) {
            $txt = e($ct['text'] ?? '');
            $tx = $ct['x'] ?? 10;
            $ty = $ct['y'] ?? 65;
            $tSize = $ct['font_size'] ?? 10;
            $tColor = $ct['color'] ?? '#000000';
            $tFont = $ct['font_family'] ?? 'sans-serif';
            $tWeight = ($ct['is_bold'] ?? false) ? 'bold' : 'normal';
            $tStyle = ($ct['is_italic'] ?? false) ? 'italic' : 'normal';

            $customTextsHtml .= "
                <div class='preview-element' style='left: {$tx}%; top: {$ty}%; font-size: calc({$tSize}px * 0.7); color: {$tColor}; font-family: {$tFont} !important; font-weight: {$tWeight}; font-style: {$tStyle}; white-space: nowrap;'>
                    {$txt}
                </div>
            ";
        }

        return "
            <style>
                .preview-card {
                    position: relative;
                    border-radius: 12px;
                    overflow: hidden;
                    box-sizing: border-box;
                    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                    transition: all 0.2s ease-in-out;
                    margin: 0 auto;
                }
                .preview-element { position: absolute; }
                .preview-header { padding: 4px; text-align: center; font-weight: bold; }
                
                .pt-classic { border: {$borderW}px double {$borderC} !important; background-color: {$canvasBgColor}; }
                .pt-classic .p-head { background-color: {$headerBgColor}; color: {$headerTextColor}; }

                .pt-modern { 
                    border: {$borderW}px solid rgba(255,255,255,0.8) !important; 
                    background: linear-gradient(135deg, {$canvasBgColor} 0%, rgba(224, 231, 255, 0.9) 60%, rgba(245, 243, 255, 0.8) 100%) !important; 
                }
                .pt-modern .p-head { background: rgba(99, 102, 241, 0.15); color: #4f46e5; border-bottom: 1px solid rgba(255,255,255,0.5); font-weight: 800; }

                .pt-corporate { border: {$borderW}px solid {$borderC} !important; background-color: {$canvasBgColor}; }
                .pt-corporate .p-head { background: #0f172a; color: #f1f5f9; border-bottom: 2px solid {$borderC}; }

                .pt-minimalist { border: {$borderW}px dashed {$borderC} !important; background-color: {$canvasBgColor}; }
                .pt-minimalist .p-head { background: transparent; color: {$borderC}; border-bottom: 1px solid #f1f5f9; }

                .pt-premium { border: {$borderW}px solid {$borderC} !important; background-color: {$canvasBgColor}; }
                .pt-premium .p-head { background: #0f172a; color: #fbbf24; border-bottom: 2px solid #fbbf24; }

                .pt-government { border: {$borderW}px solid {$borderC} !important; background-color: {$canvasBgColor}; }
                .pt-government .p-head { background: #065f46; color: #ffffff; }

                .pt-playful { border: {$borderW}px solid {$borderC} !important; background: linear-gradient(135deg, {$canvasBgColor} 0%, #fff7ed 100%); }
                .pt-playful .p-head { background-color: #f43f5e; color: #ffffff; }

                .pt-collegiate { border: {$borderW}px solid {$borderC} !important; background-color: {$canvasBgColor}; }
                .pt-collegiate .p-head { background-color: #7f1d1d; color: #ffffff; }

                .pt-tech { border: {$borderW}px solid {$borderC} !important; background-color: {$canvasBgColor}; color: {$borderC}; }
                .pt-tech .p-head { background-color: #161b22; color: {$borderC}; border-bottom: 2px solid {$borderC}; }

                .pt-vintage { border: {$borderW}px solid {$borderC} !important; background-color: {$canvasBgColor}; }
                .pt-vintage .p-head { background-color: #451a03; color: #fef3c7; border-bottom: 2px double {$borderC}; }
            </style>

            <div style='background-color: #f8fafc; padding: 24px; border-radius: 12px; display: flex; align-items: center; justify-content: center;'>
                <div style='box-sizing: border-box; padding: {$marginV}px {$marginH}px; background-color: #f8fafc; display: flex;'>
                    <div class='preview-card pt-{$theme} {$orientation}' style='{$dimensions} padding: 0 !important; margin: 0;'>
                        
                        ".($showSchoolHeader ? "
                        <div class='preview-header p-head' style='font-size: 8px;'>
                            SCHOOL CORE ACADEMY
                        </div>" : '').'

                        '.($showPhoto ? "
                        <div class='preview-element' style='left: {$photoX}%; top: {$photoY}%; width: {$photoW}%; height: {$photoH}%; border-radius: {$photoRadius}px; background-color: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 6px; color: #94a3b8; border: 1px solid #cbd5e1;'>
                            Photo
                        </div>" : '').'

                        '.($showName ? "
                        <div class='preview-element' style='left: {$nameX}%; top: {$nameY}%; width: 80%; text-align: center; font-weight: {$nameWeight}; font-style: {$nameStyle}; font-size: calc({$nameFont}px * 0.7); color: {$nameColor}; font-family: {$nameFontFamily} !important;'>
                            Sophia Mercer
                        </div>" : '').'

                        '.($showClass ? "
                        <div class='preview-element' style='left: {$classX}%; top: {$classY}%; width: 80%; text-align: center; font-weight: {$classWeight}; font-style: {$classStyle}; font-size: calc({$classFont}px * 0.7); color: {$classColor}; font-family: {$classFontFamily} !important;'>
                            Class: Form 3 Blue
                        </div>" : '')."

                        <div class='preview-element' style='left: {$metaX}%; top: {$metaY}%; width: 80%; font-weight: {$metaWeight}; font-style: {$metaStyle}; font-size: calc({$metaFont}px * 0.7); line-height: 1.3; color: {$metaColor}; font-family: {$metaFontFamily} !important;'>
                            ".($showStudentID ? 'ID: R2607-0184-74<br>' : '').'
                            '.($showAdmissionNo ? 'Adm No: 2607-0001-57<br>' : '').'
                            '.($showExpiry ? 'Expiry: 31-Dec-2029' : '').'
                        </div>

                        '.($showQR ? "
                        <div class='preview-element' style='left: {$qrX}%; top: {$qrY}%; width: {$qrSize}px; height: {$qrSize}px; display: flex; align-items: center; justify-content: center;'>
                            <img src='{$qrImageSource}' style='width: 100%; height: 100%; border: 1px solid #cbd5e1; padding: 1px; background: white;'>
                        </div>" : '').'

                        '.($showBarcode ? "
                        <div class='preview-element' style='left: {$barX}%; top: {$barY}%; width: {$barW}%; height: {$barH}px; border: 1px solid #cbd5e1; background-color: #fafafa; display: flex; flex-direction: column; align-items: center; justify-content: center;'>
                            <div style='letter-spacing: 1px; font-size: 4px; font-family: monospace; font-weight: bold; color: {$barTextColor};'>||||| | ||| || ||</div>
                            <div style='font-size: 3px; font-family: monospace; color: {$barTextColor};'>R2607-0184-74</div>
                        </div>" : '')."

                        <!-- Render dynamic custom text components safely -->
                        {$customTextsHtml}

                        <!-- ADDED: Absolutely positioned school motto simulator -->
                        ".($showSchoolMotto ? "
                        <div class='preview-element' style='left: {$mottoX}%; top: {$mottoY}%; font-size: calc({$mottoFont}px * 0.7); color: {$mottoColor}; font-family: {$mottoFontFamily} !important; font-weight: {$mottoWeight}; font-style: {$mottoStyle}; white-space: nowrap;'>
                            \"Excellence in Education\"
                        </div>" : '').'

                    </div>
                </div>
            </div>
        ';
    }
}
