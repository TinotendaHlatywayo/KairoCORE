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
            'sans-serif' => __('Helvetica / Arial (Modern Clean)'),
            'serif' => __('Georgia / Times New Roman (Formal Academic)'),
            'monospace' => __('Courier New / Consolas (Retro Tech)'),
            'Brush Script MT, cursive' => __('Brush Script (Fancy Cursive)'),
            'Impact, Charcoal, sans-serif' => __('Impact (Bold Block)'),
            'Trebuchet MS, sans-serif' => __('Trebuchet MS (Geometric Clean)'),
            'Copperplate, serif' => __('Copperplate (Engraved Header)'),
            'Palatino, serif' => __('Palatino (Elegant Literary)'),
        ];

        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // Left Column: Controls & Styling Fields (Span 2)
                        Forms\Components\Group::make([
                            Forms\Components\Section::make(__('Template Details'))
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
                                              'professional' => __('Professional School ID (Diagonal Header, Grid Layout)'),
                                          ])
                                          ->default('professional')
                                          ->live()
                                          ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                              $theme = $state ?? 'professional';
                                              $defaults = static::getThemeDefaults($theme);
                                              // Set each layout_config leaf individually so the
                                              // dot-path bound fields all pick up the new values.
                                              foreach ($defaults as $key => $value) {
                                                  if (! str_starts_with($key, 'layout_config.')) {
                                                      continue;
                                                  }
                                                  $set($key, $value);
                                              }
                                              // Ensure the selected theme is always preserved.
                                              $set('layout_config.design_theme', $theme);
                                          })
                                          ->required(),

                                     Forms\Components\Select::make('orientation')
                                         ->options([
                                             'portrait' => __('Portrait (Vertical)'),
                                             'landscape' => __('Landscape (Horizontal)'),
                                         ])
                                         ->default('landscape')
                                         ->native(false)
                                         ->live()
                                         ->required(),

                                     Forms\Components\Select::make('barcode_format')
                                         ->options([
                                             'Code128' => __('Code 128 (Standard)'),
                                             'Code39' => __('Code 39'),
                                             'EAN13' => __('EAN-13'),
                                         ])
                                         ->default('Code128')
                                         ->native(false)
                                         ->live()
                                         ->required(),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label(__('Set as Active Group Template'))
                                        ->helperText(__('Setting this active deactivates all other templates assigned to the same cohort.'))
                                        ->default(false),
                                ])->columns(2),

                            Forms\Components\Section::make(__('Included Information on ID Card'))
                                ->description(__('Select elements to render.'))
                                ->schema([
                                    Forms\Components\Toggle::make('layout_config.show_school_header')->label(__('Show School Header'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_school_motto')->label(__('Show School Motto'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_school_logo')->label(__('Show School Logo'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_contact_details')->label(__('Show Contact Details'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_photo')->label(__('Show Photo Frame'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_name')->label(__('Show Student Name'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_class')->label(__('Show Class Level'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_student_id')->label(__('Show School ID Number'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_admission_no')->label(__('Show Admission No'))->helperText(__('Off by default — the School ID Number is the primary identifier.'))->default(false)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_expiry')->label(__('Show Expiry Date'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_qr')->label(__('Show QR Code'))->default(true)->live(),
                                    Forms\Components\Toggle::make('layout_config.show_barcode')->label(__('Show Barcode'))->default(false)->live(),
                                ])->columns(3),

                             Forms\Components\Section::make(__('Layout Canvas Spacing & Border Settings'))
                                 ->schema([
                                     Forms\Components\TextInput::make('layout_config.custom_school_motto')
                                         ->label(__('Custom School Motto'))
                                         ->placeholder(__('e.g. Excellence In Education'))
                                         ->maxLength(100)
                                         ->live()
                                         ->columnSpan(2),

                                      Forms\Components\FileUpload::make('background_path')
                                          ->label(__('Card Background Image (Watermark)'))
                                          ->helperText(__('Used when Background Mode is set to "Background Image / Watermark" (Max 2MB)'))
                                          ->image()
                                          ->maxSize(2048)
                                          ->disk('public')
                                          ->directory('id-card-backgrounds')
                                          ->visibility('public')
                                          ->columnSpan(1)
                                          ->live(),

                                     Forms\Components\Select::make('layout_config.bg_mode')
                                         ->label(__('Background Mode'))
                                         ->options([
                                             'solid' => __('Solid Color'),
                                             'gradient' => __('Gradient Color'),
                                             'image' => __('Background Image / Watermark'),
                                             'logo' => __('School Logo Background'),
                                         ])
                                         ->default('solid')
                                         ->live()
                                         ->required()
                                         ->columnSpan(1),

                                     Forms\Components\ColorPicker::make('layout_config.canvas_bg_color')
                                         ->label(__('Card Canvas BG Color'))
                                         ->default('#ffffff')
                                         ->live()
                                         ->required(),

                                     Forms\Components\ColorPicker::make('layout_config.canvas_gradient_end_color')
                                         ->label(__('Gradient End Color'))
                                         ->default('#e0e7ff')
                                         ->live(),

                                     Forms\Components\TextInput::make('layout_config.canvas_bg_watermark_opacity')
                                         ->label(__('Watermark / Gradient Opacity (%)'))
                                         ->numeric()
                                         ->minValue(0)
                                         ->maxValue(100)
                                         ->default(100)
                                         ->helperText(__('0 = fully transparent, 100 = fully opaque'))
                                         ->live(),

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

                            // Theme Configuration — visible for all themes
                            Forms\Components\Section::make(__('Theme Settings'))
                                ->description(__('Configure brand colors, typography, and element visibility for the selected theme.'))
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            // School & Contact Overrides
                                            Forms\Components\TextInput::make('layout_config.custom_school_name')
                                                ->label(__('Custom School Name'))
                                                ->placeholder(__('Default: school name in Settings'))
                                                ->live()
                                                ->columnSpan(1),
                                            Forms\Components\TextInput::make('layout_config.custom_school_motto')
                                                ->label(__('Custom School Motto'))
                                                ->placeholder(__('Default: school motto in Settings'))
                                                ->live()
                                                ->columnSpan(1),

                                            // Brand Colors
                                            Forms\Components\ColorPicker::make('layout_config.primary_color')
                                                ->label(__('Primary Color'))
                                                ->default('#1e3a8a')
                                                ->live()
                                                ->required()
                                                ->helperText(__('Main brand color for header, borders')),
                                            Forms\Components\ColorPicker::make('layout_config.primary_dark')
                                                ->label(__('Primary Dark'))
                                                ->default('#0f172a')
                                                ->live()
                                                ->required()
                                                ->helperText(__('Darker shade for header background')),
                                            Forms\Components\ColorPicker::make('layout_config.accent_color')
                                                ->label(__('Accent Color'))
                                                ->default('#fbbf24')
                                                ->live()
                                                ->required()
                                                ->helperText(__('Gold/yellow accent for year strip, borders')),

                                            // Text Colors
                                            Forms\Components\ColorPicker::make('layout_config.text_primary')
                                                ->label(__('Text Primary'))
                                                ->default('#0f172a')
                                                ->live()
                                                ->required(),
                                            Forms\Components\ColorPicker::make('layout_config.text_secondary')
                                                ->label(__('Text Secondary'))
                                                ->default('#334155')
                                                ->live()
                                                ->required(),
                                            Forms\Components\ColorPicker::make('layout_config.text_muted')
                                                ->label(__('Text Muted'))
                                                ->default('#64748b')
                                                ->live()
                                                ->required(),

                                            // Footer Colors
                                            Forms\Components\ColorPicker::make('layout_config.footer_bg')
                                                ->label(__('Footer Background'))
                                                ->default('#0f172a')
                                                ->live()
                                                ->required(),
                                            Forms\Components\ColorPicker::make('layout_config.footer_text')
                                                ->label(__('Footer Text Color'))
                                                ->default('#fbbf24')
                                                ->live()
                                                ->required(),

                                            // School Name Typography
                                            Forms\Components\Select::make('layout_config.school_name_font_family')
                                                ->label(__('School Name Font'))
                                                ->options($fontOptions)
                                                ->default('sans-serif')
                                                ->native(false)
                                                ->live()
                                                ->required(),
                                            Forms\Components\TextInput::make('layout_config.school_name_font_size')
                                                ->label(__('School Name Size (px)'))
                                                ->numeric()
                                                ->default(18)
                                                ->live()
                                                ->required(),
                                            Forms\Components\ColorPicker::make('layout_config.school_name_color')
                                                ->label(__('School Name Color'))
                                                ->default('#fbbf24')
                                                ->live()
                                                ->required(),

                                            // Motto Typography
                                            Forms\Components\Select::make('layout_config.motto_font_family')
                                                ->label(__('Motto Font'))
                                                ->options($fontOptions)
                                                ->default('sans-serif')
                                                ->native(false)
                                                ->live()
                                                ->required(),
                                            Forms\Components\TextInput::make('layout_config.motto_font_size')
                                                ->label(__('Motto Size (px)'))
                                                ->numeric()
                                                ->default(10)
                                                ->live()
                                                ->required(),
                                            Forms\Components\ColorPicker::make('layout_config.motto_color')
                                                ->label(__('Motto Color'))
                                                ->default('#cbd5e1')
                                                ->live()
                                                ->required(),

                                            // Name Typography
                                            Forms\Components\Select::make('layout_config.name_font_family')
                                                ->label(__('Student Name Font'))
                                                ->options($fontOptions)
                                                ->default('sans-serif')
                                                ->native(false)
                                                ->live()
                                                ->required(),
                                            Forms\Components\TextInput::make('layout_config.name_font_size')
                                                ->label(__('Name Size (px)'))
                                                ->numeric()
                                                ->default(22)
                                                ->live()
                                                ->required(),
                                            Forms\Components\ColorPicker::make('layout_config.name_color')
                                                ->label(__('Name Color'))
                                                ->default('#0f172a')
                                                ->live()
                                                ->required(),

                                            // Label/Value Typography
                                            Forms\Components\Select::make('layout_config.label_font_family')
                                                ->label(__('Label Font'))
                                                ->options($fontOptions)
                                                ->default('sans-serif')
                                                ->native(false)
                                                ->live()
                                                ->required(),
                                            Forms\Components\TextInput::make('layout_config.label_font_size')
                                                ->label(__('Label Size (px)'))
                                                ->numeric()
                                                ->default(11)
                                                ->live()
                                                ->required(),
                                            Forms\Components\ColorPicker::make('layout_config.label_color')
                                                ->label(__('Label Color'))
                                                ->default('#64748b')
                                                ->live()
                                                ->required(),

                                            Forms\Components\Select::make('layout_config.value_font_family')
                                                ->label(__('Value Font'))
                                                ->options($fontOptions)
                                                ->default('sans-serif')
                                                ->native(false)
                                                ->live()
                                                ->required(),
                                            Forms\Components\TextInput::make('layout_config.value_font_size')
                                                ->label(__('Value Size (px)'))
                                                ->numeric()
                                                ->default(12)
                                                ->live()
                                                ->required(),
                                            Forms\Components\ColorPicker::make('layout_config.value_color')
                                                ->label(__('Value Color'))
                                                ->default('#0f172a')
                                                ->live()
                                                ->required(),
                                            Forms\Components\ColorPicker::make('layout_config.value_color_accent')
                                                ->label(__('Value Accent Color (ID)'))
                                                ->default('#1e3a8a')
                                                ->live()
                                                ->required(),

                                            // Photo Settings
                                            Forms\Components\ColorPicker::make('layout_config.photo_border_color')
                                                ->label(__('Photo Border Color'))
                                                ->default('#fbbf24')
                                                ->live()
                                                ->required(),
                                            Forms\Components\TextInput::make('layout_config.photo_rounded_corners')
                                                ->label(__('Photo Corner Radius (px)'))
                                                ->numeric()
                                                ->default(8)
                                                ->live()
                                                ->required(),
                                            Forms\Components\TextInput::make('layout_config.photo_border_width')
                                                ->label(__('Photo Border Width (px)'))
                                                ->numeric()
                                                ->default(2)
                                                ->live()
                                                ->required(),

                                            // QR Code
                                            Forms\Components\TextInput::make('layout_config.qr_size')
                                                ->label(__('QR Size (px)'))
                                                ->numeric()
                                                ->default(48)
                                                ->live()
                                                ->required(),

                                            // Year Strip
                                            Forms\Components\TextInput::make('layout_config.strip_font_size')
                                                ->label(__('Year Strip Font Size (px)'))
                                                ->numeric()
                                                ->default(9)
                                                ->live()
                                                ->required(),

                                            // Element Visibility Toggles
                                            Forms\Components\Toggle::make('layout_config.show_dob')
                                                ->label(__('Show Date of Birth'))
                                                ->default(true)
                                                ->live(),
                                            Forms\Components\Toggle::make('layout_config.show_address')
                                                ->label(__('Show Address'))
                                                ->default(true)
                                                ->live(),
                                            Forms\Components\Toggle::make('layout_config.show_student_phone')
                                                ->label(__('Show Student Phone'))
                                                ->default(true)
                                                ->live(),
                                            Forms\Components\Toggle::make('layout_config.show_national_id')
                                                ->label(__('Show National ID'))
                                                ->default(true)
                                                ->live(),
                                            Forms\Components\Toggle::make('layout_config.show_photo_caption')
                                                ->label(__('Show Photo Caption'))
                                                ->default(true)
                                                ->live(),
                                            Forms\Components\TextInput::make('layout_config.photo_caption_text')
                                                ->label(__('Photo Caption Text'))
                                                ->default('STUDENT')
                                                ->live()
                                                ->maxLength(20),
                                        ]),
                                ])->columns(1)->collapsed(),

                            Forms\Components\Section::make(__('Element Position, Color & Font Controls'))
                                ->schema([
                                    Forms\Components\Tabs::make(__('Elements Layout Configuration'))
                                        ->tabs([
                                             Forms\Components\Tabs\Tab::make(__('Name Text'))
                                                 ->schema([
                                                     Forms\Components\Select::make('layout_config.name_font_family')
                                                         ->label(__('Font Style'))
                                                         ->options($fontOptions)->default('sans-serif')->native(false)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.name_x')->label(__('X Offset (%)'))->numeric()->default(35)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.name_y')->label(__('Y Offset (%)'))->numeric()->default(30)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.name_font_size')->label(__('Font Size (px)'))->numeric()->default(16)->live()->required(),
                                                     Forms\Components\ColorPicker::make('layout_config.name_color')->label(__('Font Color'))->default('#1e3a8a')->live()->required(),
                                                     Forms\Components\Toggle::make('layout_config.name_is_bold')->label(__('Bold Text'))->default(true)->live(),
                                                     Forms\Components\Toggle::make('layout_config.name_is_italic')->label(__('Italic Text'))->default(false)->live(),
                                                 ])->columns(2),

                                              Forms\Components\Tabs\Tab::make(__('Class Text'))
                                                  ->schema([
                                                      Forms\Components\Select::make('layout_config.class_font_family')
                                                          ->label(__('Font Style'))
                                                          ->options($fontOptions)->default('sans-serif')->native(false)->live()->required(),
                                                      Forms\Components\TextInput::make('layout_config.class_x')->label(__('X Offset (%)'))->numeric()->default(35)->live()->required(),
                                                      Forms\Components\TextInput::make('layout_config.class_y')->label(__('Y Offset (%)'))->numeric()->default(41)->live()->required(),
                                                      Forms\Components\TextInput::make('layout_config.class_font_size')->label(__('Font Size (px)'))->numeric()->default(13)->live()->required(),
                                                      Forms\Components\ColorPicker::make('layout_config.class_color')->label(__('Font Color'))->default('#64748b')->live()->required(),
                                                      Forms\Components\Toggle::make('layout_config.class_is_bold')->label(__('Bold Text'))->default(false)->live(),
                                                      Forms\Components\Toggle::make('layout_config.class_is_italic')->label(__('Italic Text'))->default(false)->live(),
                                                  ])->columns(2),

                                              Forms\Components\Tabs\Tab::make(__('School Motto'))
                                                  ->schema([
                                                      Forms\Components\Select::make('layout_config.motto_font_family')
                                                          ->label(__('Font Style'))
                                                          ->options($fontOptions)->default('sans-serif')->native(false)->live()->required(),
                                                      Forms\Components\TextInput::make('layout_config.motto_x')->label(__('X Offset (%)'))->numeric()->default(18)->live()->required(),
                                                      Forms\Components\TextInput::make('layout_config.motto_y')->label(__('Y Offset (%)'))->numeric()->default(14)->live()->required(),
                                                      Forms\Components\TextInput::make('layout_config.motto_font_size')->label(__('Font Size (px)'))->numeric()->default(10)->live()->required(),
                                                      Forms\Components\ColorPicker::make('layout_config.motto_color')->label(__('Font Color'))->default('#cbd5e1')->live()->required(),
                                                      Forms\Components\Toggle::make('layout_config.motto_is_bold')->label(__('Bold Text'))->default(false)->live(),
                                                      Forms\Components\Toggle::make('layout_config.motto_is_italic')->label(__('Italic Text'))->default(true)->live(),
                                                  ])->columns(2),

                                             Forms\Components\Tabs\Tab::make(__('Photo Frame'))
                                                 ->schema([
                                                     Forms\Components\TextInput::make('layout_config.photo_x')->label(__('X Offset (%)'))->numeric()->default(5)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.photo_y')->label(__('Y Offset (%)'))->numeric()->default(27)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.photo_width')->label(__('Width (%)'))->numeric()->default(25)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.photo_height')->label(__('Height (%)'))->numeric()->default(41)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.photo_rounded_corners')->label(__('Corner Radius (px)'))->numeric()->default(8)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.photo_border_width')->label(__('Border Width (px)'))->numeric()->default(2)->live()->required(),
                                                     Forms\Components\ColorPicker::make('layout_config.photo_border_color')->label(__('Border Color'))->default('#fbbf24')->live()->required(),
                                                 ])->columns(3),

                                             Forms\Components\Tabs\Tab::make(__('School Logo'))
                                                 ->schema([
                                                     Forms\Components\FileUpload::make('layout_config.logo_path')
                                                         ->label(__('Logo Override'))
                                                         ->helperText(__('Optional. Uses the logo configured in Settings when left empty.'))
                                                         ->image()
                                                         ->disk('public')
                                                         ->directory('id-card-logos')
                                                         ->visibility('public')
                                                         ->live(),
Forms\Components\Select::make('layout_config.logo_bg_mode')
                                                          ->label(__('Logo Background Mode'))
                                                          ->options([
                                                              'none' => __('No Background'),
                                                              'solid' => __('Solid Color'),
                                                              'gradient' => __('Gradient Color'),
                                                          ])
                                                          ->default('none')
                                                          ->native(false)
                                                          ->live()
                                                          ->required(),
                                                     Forms\Components\ColorPicker::make('layout_config.logo_bg_color')
                                                         ->label(__('Logo Background Color'))
                                                         ->default('rgba(255,255,255,0.2)')
                                                         ->live(),
                                                     Forms\Components\ColorPicker::make('layout_config.logo_bg_gradient_end')
                                                         ->label(__('Logo Background Gradient End'))
                                                         ->default('#e0e7ff')
                                                         ->live(),
                                                     Forms\Components\TextInput::make('layout_config.logo_bg_transparency')
                                                         ->label(__('Logo Background Transparency (%)'))
                                                         ->numeric()
                                                         ->minValue(0)
                                                         ->maxValue(100)
                                                         ->default(50)
                                                         ->helperText(__('0 = fully transparent, 100 = fully opaque'))
                                                         ->live(),
                                                     Forms\Components\TextInput::make('layout_config.logo_padding')
                                                         ->label(__('Logo Padding (px)'))
                                                         ->numeric()
                                                         ->default(2)
                                                         ->live(),
                                                     Forms\Components\TextInput::make('layout_config.logo_x')->label(__('X Offset (%)'))->numeric()->default(87)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.logo_y')->label(__('Y Offset (%)'))->numeric()->default(4)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.logo_width')->label(__('Width (px)'))->numeric()->default(40)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.logo_height')->label(__('Height (px)'))->numeric()->default(40)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.logo_rounded_corners')->label(__('Corner Radius (px)'))->numeric()->default(6)->live()->required(),
                                                     Forms\Components\Select::make('layout_config.logo_fit')
                                                         ->label(__('Logo Image Fit'))
                                                         ->options(['cover' => __('Crop to Fit'), 'contain' => __('Fit Entire Logo')])
                                                         ->default('cover')
                                                         ->native(false)
                                                         ->live()
                                                         ->required(),
                                                 ])->columns(3),

                                             Forms\Components\Tabs\Tab::make(__('Contact Details'))
                                                 ->schema([
                                                     Forms\Components\Fieldset::make(__('Which Contact Details to Include'))
                                                         ->schema([
                                                             Forms\Components\Toggle::make('layout_config.show_contact_address')->label(__('Show School Address'))->default(true)->live(),
                                                             Forms\Components\Toggle::make('layout_config.show_contact_phone')->label(__('Show School Phone'))->default(true)->live(),
                                                             Forms\Components\Toggle::make('layout_config.show_contact_email')->label(__('Show School Email'))->default(true)->live(),
                                                             Forms\Components\Toggle::make('layout_config.show_contact_website')->label(__('Show School Website'))->default(true)->live(),
                                                         ])->columns(4),
                                                     Forms\Components\Select::make('layout_config.contact_line_mode')
                                                         ->label(__('Contact Layout'))
                                                         ->options([
                                                             'single' => __('Single Line'),
                                                             'stacked' => __('Each Detail on Its Own Line'),
                                                         ])
                                                         ->default('single')
                                                         ->helperText(__('Single line suits the card footer; own-line suits contact placed beside the school logo.'))
                                                         ->native(false)
                                                         ->live()
                                                         ->required(),
                                                     Forms\Components\TextInput::make('layout_config.contact_address')->label(__('Contact Address Override'))->placeholder(__('Default: system address'))->live(),
                                                     Forms\Components\TextInput::make('layout_config.contact_email')->label(__('Contact Email Override'))->placeholder(__('Default: system email'))->live(),
                                                     Forms\Components\TextInput::make('layout_config.contact_phone')->label(__('Contact Phone Override'))->placeholder(__('Default: system phone'))->live(),
                                                     Forms\Components\TextInput::make('layout_config.contact_website')->label(__('Contact Website Override'))->placeholder(__('Default: system website'))->live(),
                                                     Forms\Components\TextInput::make('layout_config.contact_x')->label(__('X Offset (%)'))->numeric()->default(5)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.contact_y')->label(__('Y Offset (%)'))->numeric()->default(88)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.contact_font_size')->label(__('Font Size (px)'))->numeric()->default(9)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.contact_width')->label(__('Width (%)'))->numeric()->default(70)->live()->required(),
                                                     Forms\Components\ColorPicker::make('layout_config.contact_color')->label(__('Font Color'))->default('#475569')->live()->required(),
                                                 ])->columns(3),

                                             Forms\Components\Tabs\Tab::make(__('School Name'))
                                                 ->schema([
                                                     Forms\Components\TextInput::make('layout_config.custom_school_name')
                                                         ->label(__('School Name Override'))
                                                         ->placeholder(__('Default: school name in Settings'))
                                                         ->live()
                                                         ->columnSpanFull(),
                                                     Forms\Components\Select::make('layout_config.school_name_font_family')
                                                         ->label(__('Font Style'))
                                                         ->options($fontOptions)->default('sans-serif')->native(false)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.school_name_x')->label(__('X Offset (%)'))->numeric()->default(14)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.school_name_y')->label(__('Y Offset (%)'))->numeric()->default(5)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.school_name_font_size')->label(__('Font Size (px)'))->numeric()->default(18)->live()->required(),
                                                     Forms\Components\ColorPicker::make('layout_config.school_name_color')->label(__('Font Color'))->default('#fbbf24')->live()->required(),
                                                     Forms\Components\Toggle::make('layout_config.school_name_is_bold')->label(__('Bold Text'))->default(true)->live(),
                                                     Forms\Components\Toggle::make('layout_config.school_name_is_italic')->label(__('Italic Text'))->default(false)->live(),
                                                 ])->columns(3),

                                            Forms\Components\Tabs\Tab::make(__('QR & Barcode Layout'))
                                                ->schema([
                                                    Forms\Components\Fieldset::make(__('QR Security Verification'))
                                                        ->schema([
                                                            Forms\Components\TextInput::make('layout_config.qr_x')->label(__('X Offset (%)'))->numeric()->default(75)->live()->required(),
                                                            Forms\Components\TextInput::make('layout_config.qr_y')->label(__('Y Offset (%)'))->numeric()->default(42)->live()->required(),
                                                            Forms\Components\TextInput::make('layout_config.qr_size')->label(__('QR Dimension (px)'))->numeric()->default(58)->live()->required(),
                                                        ])->columns(3),

                                                     Forms\Components\Fieldset::make(__('Barcode Coordinates'))
                                                         ->schema([
                                                             Forms\Components\TextInput::make('layout_config.barcode_x')->label(__('X Offset (%)'))->numeric()->default(10)->live(),
                                                             Forms\Components\TextInput::make('layout_config.barcode_y')->label(__('Y Offset (%)'))->numeric()->default(82)->live(),
                                                             Forms\Components\TextInput::make('layout_config.barcode_width')->label(__('Width (%)'))->numeric()->default(80)->live(),
                                                             Forms\Components\TextInput::make('layout_config.barcode_height')->label(__('Height (px)'))->numeric()->default(30)->live(),
                                                             Forms\Components\ColorPicker::make('layout_config.barcode_text_color')->label(__('Code Text Color'))->default('#000000')->live(),
                                                         ])->columns(3),
                                                ]),

                                             Forms\Components\Tabs\Tab::make(__('Metadata Text block'))
                                                 ->schema([
                                                     Forms\Components\TextInput::make('layout_config.custom_metadata_text')
                                                         ->label(__('Custom Metadata Text (Your Own Line)'))
                                                         ->placeholder(__('e.g. Bus Route A / Prefect / House Captain'))
                                                         ->maxLength(100)
                                                         ->helperText(__('Rendered as an extra line inside the metadata block.'))
                                                         ->live()
                                                         ->columnSpanFull(),
                                                     Forms\Components\Select::make('layout_config.meta_font_family')
                                                         ->label(__('Font Style'))
                                                         ->options($fontOptions)->default('sans-serif')->native(false)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.meta_x')->label(__('X Offset (%)'))->numeric()->default(35)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.meta_y')->label(__('Y Offset (%)'))->numeric()->default(50)->live()->required(),
                                                     Forms\Components\TextInput::make('layout_config.meta_font_size')->label(__('Font Size (px)'))->numeric()->default(10)->live()->required(),
                                                     Forms\Components\ColorPicker::make('layout_config.meta_color')->label(__('Font Color'))->default('#334155')->live()->required(),
                                                     Forms\Components\Toggle::make('layout_config.meta_is_bold')->label(__('Bold Text'))->default(false)->live(),
                                                     Forms\Components\Toggle::make('layout_config.meta_is_italic')->label(__('Italic Text'))->default(false)->live(),
                                                 ])->columns(2),

                                            Forms\Components\Tabs\Tab::make(__('Custom Text Lines'))
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
                                                                 ->options($fontOptions)->default('sans-serif')->native(false)->live()->required(),
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
                            Forms\Components\Section::make(__('Interactive Live Simulator'))
                                ->description(__('Simulates theme styling on edit changes.'))
                                ->schema([
                                    Forms\Components\Placeholder::make('live_preview')
                                        ->reactive()
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

                Tables\Actions\Action::make('duplicate')
                    ->label(__('Duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->action(function ($record) {
                        $newRecord = $record->replicate();
                        $newRecord->name = $record->name . ' (Copy)';
                        $newRecord->is_active = false;
                        $newRecord->save();

                        Notification::make()
                            ->title(__('Template Duplicated'))
                            ->body("Successfully duplicated {$record->name}.")
                            ->success()
                            ->send();
                    }),

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
        // Merge live form values over the built-in defaults so a freshly
        // created template previews exactly like the printed card.
        $defaults = \App\Http\Controllers\StudentCardPrintController::defaultTemplate()->layout_config;
        $formConfig = array_replace($defaults, (array) ($get('layout_config') ?? []));
        $orientation = $get('orientation') ?? 'landscape';

        // In-memory sample student so the preview pipeline is byte-for-byte the
        // same one used by the printed PDF and the PNG export.
        $section = new \Modules\Academics\Models\Section();
        $section->name = 'North';
        $course = new \Modules\Academics\Models\Course();
        $course->name = 'Grade 1';
        $section->setRelation('course', $course);

        $enrollment = new \Modules\Students\Models\Enrollment();
        $enrollment->setRelation('section', $section);

        $sample = new \Modules\Students\Models\Student();
        $sample->first_name = 'Sophia';
        $sample->last_name = 'Mercer';
        $sample->gender = 'female';
        $sample->student_id_number = 'R263685829X';
        $sample->admission_number = '2607-0001-57';
        $sample->national_id = '65 2546896 F 88';
        $sample->date_of_birth = \Carbon\Carbon::parse('2015-03-21');
        $sample->boarding_status = 'day_scholar';
        $sample->physical_address = '14 Links Lane, Borrowdale, Harare';
        $sample->phone = '+263 77 123 4567';
        $sample->photo_path = null;
        $sample->card_expiry_date = \Carbon\Carbon::parse('2029-12-31');
        $sample->setRelation('currentEnrollment', $enrollment);

        $template = (object) [
            'orientation' => $orientation,
            'background_path' => $get('background_path'),
            'layout_config' => $formConfig,
        ];

        $styles = view('components.id-card-styles')->render();
        $card = view('components.id-card-render', [
            'student' => $sample,
            'template' => $template,
            'school' => current_tenant(),
            'cropMarks' => false,
        ])->render();

        // This is the print canvas, scaled only for the narrow designer panel.
        // No alternate preview markup is used, so all coordinates are identical.
        $scale = $orientation === 'portrait' ? 0.48 : 0.68;
        $previewHeight = $orientation === 'portrait' ? 252 : 226;
        $previewWidth = $orientation === 'portrait' ? 300 : 480;

        return $styles.'<div class="id-card-preview" style="background:#eef2f7; border-radius:10px;'
            .' padding:14px; height:'.(int) $previewHeight.'px; display:flex; align-items:flex-start;'
            .' justify-content:center; overflow:hidden; max-width:100%;">'
            .'<div style="width:'.$previewWidth.'px; transform:scale('.$scale.'); transform-origin:top center;'
            .' box-shadow:0 14px 30px rgba(15,23,42,.18); border-radius:12px; background:#ffffff; line-height:normal;">'.$card.'</div></div>';
    }

    public static function getThemeDefaults(string $theme): array
    {
        $base = [
            'layout_config.canvas_bg_color'              => '#ffffff',
            'layout_config.canvas_gradient_end_color'    => '#e0e7ff',
            'layout_config.bg_mode'                      => 'solid',
            'layout_config.card_border_width'             => 3,
            'layout_config.card_border_color'             => '#1e3a8a',
            'layout_config.header_bg_color'              => '#1e3a8a',
            'layout_config.header_text_color'            => '#ffffff',
            'layout_config.card_padding'                 => 10,
            'layout_config.card_margin_v'                => 0,
            'layout_config.card_margin_h'                => 0,
            'layout_config.canvas_bg_watermark_opacity'  => 100,

            'layout_config.primary_color'       => '#1e3a8a',
            'layout_config.primary_dark'        => '#0f172a',
            'layout_config.accent_color'        => '#fbbf24',
            'layout_config.text_primary'        => '#0f172a',
            'layout_config.text_secondary'      => '#334155',
            'layout_config.text_muted'          => '#64748b',
            'layout_config.footer_bg'           => '#0f172a',
            'layout_config.footer_text'         => '#fbbf24',

            'layout_config.name_font_family'           => 'sans-serif',
            'layout_config.name_font_size'             => 16,
            'layout_config.name_color'                 => '#1e3a8a',
            'layout_config.name_x'                     => 35,
            'layout_config.name_y'                     => 30,
            'layout_config.name_is_bold'               => true,
            'layout_config.name_is_italic'             => false,

            'layout_config.class_font_family'          => 'sans-serif',
            'layout_config.class_font_size'            => 13,
            'layout_config.class_color'                => '#64748b',
            'layout_config.class_x'                    => 35,
            'layout_config.class_y'                    => 41,
            'layout_config.class_is_bold'              => false,
            'layout_config.class_is_italic'            => false,

            'layout_config.motto_font_family'          => 'sans-serif',
            'layout_config.motto_font_size'            => 10,
            'layout_config.motto_color'                => '#cbd5e1',
            'layout_config.motto_x'                    => 18,
            'layout_config.motto_y'                    => 14,
            'layout_config.motto_is_bold'              => false,
            'layout_config.motto_is_italic'            => true,

            'layout_config.school_name_font_family'    => 'sans-serif',
            'layout_config.school_name_font_size'      => 18,
            'layout_config.school_name_color'          => '#fbbf24',
            'layout_config.school_name_x'              => 14,
            'layout_config.school_name_y'              => 5,
            'layout_config.school_name_is_bold'        => true,
            'layout_config.school_name_is_italic'      => false,

            'layout_config.label_font_family'          => 'sans-serif',
            'layout_config.label_font_size'            => 11,
            'layout_config.label_color'                => '#64748b',
            'layout_config.value_font_family'          => 'sans-serif',
            'layout_config.value_font_size'            => 12,
            'layout_config.value_color'                => '#0f172a',
            'layout_config.value_color_accent'         => '#1e3a8a',

            'layout_config.meta_font_family'           => 'sans-serif',
            'layout_config.meta_font_size'             => 10,
            'layout_config.meta_color'                 => '#334155',
            'layout_config.meta_x'                     => 35,
            'layout_config.meta_y'                     => 50,
            'layout_config.meta_is_bold'               => false,
            'layout_config.meta_is_italic'             => false,

            'layout_config.photo_x'                    => 5,
            'layout_config.photo_y'                    => 27,
            'layout_config.photo_width'                => 25,
            'layout_config.photo_height'               => 41,
            'layout_config.photo_rounded_corners'      => 8,
            'layout_config.photo_border_width'         => 2,
            'layout_config.photo_border_color'         => '#fbbf24',

            'layout_config.logo_x'                     => 87,
            'layout_config.logo_y'                     => 4,
            'layout_config.logo_width'                 => 40,
            'layout_config.logo_height'                => 40,
            'layout_config.logo_rounded_corners'       => 6,
            'layout_config.logo_bg_mode'               => 'none',
            'layout_config.logo_bg_color'              => 'rgba(255,255,255,0.2)',
            'layout_config.logo_bg_transparency'       => 50,
            'layout_config.logo_padding'               => 2,
            'layout_config.logo_fit'                   => 'cover',

            'layout_config.contact_x'                  => 5,
            'layout_config.contact_y'                  => 88,
            'layout_config.contact_font_size'          => 9,
            'layout_config.contact_width'              => 70,
            'layout_config.contact_color'              => '#475569',
            'layout_config.contact_line_mode'          => 'single',

            'layout_config.qr_x'                       => 75,
            'layout_config.qr_y'                       => 42,
            'layout_config.qr_size'                    => 58,

            'layout_config.strip_font_size'            => 9,

            // Contact visibility toggles
            'layout_config.show_contact_address'       => true,
            'layout_config.show_contact_phone'         => true,
            'layout_config.show_contact_email'         => true,
            'layout_config.show_contact_website'       => true,

            // Included Information toggles (default ON)
            'layout_config.show_school_header'         => true,
            'layout_config.show_school_motto'          => true,
            'layout_config.show_school_logo'           => true,
            'layout_config.show_contact_details'       => true,
            'layout_config.show_photo'                 => true,
            'layout_config.show_name'                  => true,
            'layout_config.show_class'                 => true,
            'layout_config.show_student_id'            => true,
            'layout_config.show_admission_no'          => false,
            'layout_config.show_expiry'                => true,
            'layout_config.show_qr'                    => true,
            'layout_config.show_barcode'               => false,
            'layout_config.show_dob'                   => true,
            'layout_config.show_address'               => true,
            'layout_config.show_student_phone'         => true,
            'layout_config.show_national_id'           => true,
            'layout_config.show_photo_caption'         => true,
        ];

        $themes = [
            'professional' => array_replace($base, [
                'layout_config.canvas_bg_color'       => '#ffffff',
                'layout_config.card_border_width'      => 3,
                'layout_config.card_border_color'      => '#1e3a8a',
                'layout_config.header_bg_color'        => '#1e3a8a',
                'layout_config.header_text_color'      => '#ffffff',
                'layout_config.primary_color'          => '#1e3a8a',
                'layout_config.primary_dark'           => '#0f172a',
                'layout_config.accent_color'           => '#fbbf24',
                'layout_config.footer_bg'              => '#0f172a',
                'layout_config.footer_text'            => '#fbbf24',
                'layout_config.name_color'             => '#0f172a',
                'layout_config.name_x'                 => 35,
                'layout_config.name_y'                 => 30,
                'layout_config.class_color'            => '#64748b',
                'layout_config.class_x'                => 35,
                'layout_config.class_y'                => 41,
                'layout_config.photo_x'                => 5,
                'layout_config.photo_y'                => 27,
                'layout_config.photo_width'            => 25,
                'layout_config.photo_height'           => 41,
                'layout_config.photo_border_color'     => '#fbbf24',
                'layout_config.school_name_color'      => '#fbbf24',
                'layout_config.motto_color'            => '#cbd5e1',
                'layout_config.qr_x'                   => 75,
                'layout_config.qr_y'                   => 42,
                'layout_config.contact_color'          => '#fbbf24',
                'layout_config.contact_font_size'      => 8,
            ]),

            'premium' => array_replace($base, [
                'layout_config.canvas_bg_color'       => '#0f172a',
                'layout_config.card_border_width'      => 4,
                'layout_config.card_border_color'      => '#fbbf24',
                'layout_config.header_bg_color'        => '#0f172a',
                'layout_config.header_text_color'      => '#fbbf24',
                'layout_config.primary_color'          => '#1e3a5a',
                'layout_config.primary_dark'           => '#020617',
                'layout_config.accent_color'           => '#fbbf24',
                'layout_config.footer_bg'              => '#020617',
                'layout_config.footer_text'            => '#fbbf24',
                'layout_config.name_color'             => '#0f172a',
                'layout_config.name_x'                 => 30,
                'layout_config.name_y'                 => 28,
                'layout_config.name_font_size'         => 18,
                'layout_config.name_is_bold'           => true,
                'layout_config.class_color'            => '#475569',
                'layout_config.class_x'                => 30,
                'layout_config.class_y'                => 38,
                'layout_config.photo_x'                => 4,
                'layout_config.photo_y'                => 24,
                'layout_config.photo_width'            => 22,
                'layout_config.photo_height'           => 44,
                'layout_config.photo_border_color'     => '#fbbf24',
                'layout_config.photo_border_width'     => 3,
                'layout_config.photo_rounded_corners'  => 6,
                'layout_config.school_name_color'      => '#fbbf24',
                'layout_config.motto_color'            => '#94a3b8',
                'layout_config.motto_is_italic'        => true,
                'layout_config.qr_x'                   => 76,
                'layout_config.qr_y'                   => 40,
                'layout_config.contact_color'          => '#fbbf24',
                'layout_config.contact_font_size'      => 8,
                'layout_config.label_color'            => '#64748b',
                'layout_config.value_color'            => '#0f172a',
            ]),

            'corporate' => array_replace($base, [
                'layout_config.canvas_bg_color'       => '#f8fafc',
                'layout_config.card_border_width'      => 2,
                'layout_config.card_border_color'      => '#1e293b',
                'layout_config.header_bg_color'        => '#1e293b',
                'layout_config.header_text_color'      => '#f8fafc',
                'layout_config.primary_color'          => '#1e293b',
                'layout_config.primary_dark'           => '#0f172a',
                'layout_config.accent_color'           => '#64748b',
                'layout_config.footer_bg'              => '#1e293b',
                'layout_config.footer_text'            => '#f8fafc',
                'layout_config.name_color'             => '#0f172a',
                'layout_config.name_x'                 => 32,
                'layout_config.name_y'                 => 30,
                'layout_config.name_font_size'         => 17,
                'layout_config.name_is_bold'           => true,
                'layout_config.class_color'            => '#475569',
                'layout_config.class_x'                => 32,
                'layout_config.class_y'                => 40,
                'layout_config.photo_x'                => 5,
                'layout_config.photo_y'                => 26,
                'layout_config.photo_width'            => 22,
                'layout_config.photo_height'           => 42,
                'layout_config.photo_border_color'     => '#1e293b',
                'layout_config.photo_border_width'     => 2,
                'layout_config.photo_rounded_corners'  => 4,
                'layout_config.school_name_color'      => '#f8fafc',
                'layout_config.motto_color'            => '#94a3b8',
                'layout_config.motto_is_italic'        => false,
                'layout_config.qr_x'                   => 76,
                'layout_config.qr_y'                   => 42,
                'layout_config.contact_color'          => '#f8fafc',
                'layout_config.contact_font_size'      => 8,
                'layout_config.label_color'            => '#64748b',
                'layout_config.value_color'            => '#0f172a',
            ]),

            'modern' => array_replace($base, [
                'layout_config.canvas_bg_color'       => '#f0f4ff',
                'layout_config.canvas_gradient_end_color' => '#e0e7ff',
                'layout_config.bg_mode'               => 'gradient',
                'layout_config.card_border_width'      => 1,
                'layout_config.card_border_color'      => '#c7d2fe',
                'layout_config.header_bg_color'        => '#4f46e5',
                'layout_config.header_text_color'      => '#ffffff',
                'layout_config.primary_color'          => '#4f46e5',
                'layout_config.primary_dark'           => '#312e81',
                'layout_config.accent_color'           => '#06b6d4',
                'layout_config.footer_bg'              => '#312e81',
                'layout_config.footer_text'            => '#e0e7ff',
                'layout_config.name_color'             => '#1e1b4b',
                'layout_config.name_x'                 => 34,
                'layout_config.name_y'                 => 28,
                'layout_config.name_font_size'         => 18,
                'layout_config.name_is_bold'           => true,
                'layout_config.class_color'            => '#6366f1',
                'layout_config.class_x'                => 34,
                'layout_config.class_y'                => 38,
                'layout_config.photo_x'                => 5,
                'layout_config.photo_y'                => 24,
                'layout_config.photo_width'            => 24,
                'layout_config.photo_height'           => 44,
                'layout_config.photo_border_color'     => '#06b6d4',
                'layout_config.photo_border_width'     => 2,
                'layout_config.photo_rounded_corners'  => 12,
                'layout_config.school_name_color'      => '#e0e7ff',
                'layout_config.motto_color'            => '#a5b4fc',
                'layout_config.motto_is_italic'        => true,
                'layout_config.qr_x'                   => 74,
                'layout_config.qr_y'                   => 40,
                'layout_config.contact_color'          => '#e0e7ff',
                'layout_config.contact_font_size'      => 8,
                'layout_config.label_color'            => '#6366f1',
                'layout_config.value_color'            => '#1e1b4b',
            ]),

            'classic' => array_replace($base, [
                'layout_config.canvas_bg_color'       => '#fffbeb',
                'layout_config.canvas_gradient_end_color' => '#fef3c7',
                'layout_config.bg_mode'               => 'solid',
                'layout_config.card_border_width'      => 5,
                'layout_config.card_border_color'      => '#92400e',
                'layout_config.header_bg_color'        => '#92400e',
                'layout_config.header_text_color'      => '#fffbeb',
                'layout_config.primary_color'          => '#92400e',
                'layout_config.primary_dark'           => '#78350f',
                'layout_config.accent_color'           => '#b45309',
                'layout_config.footer_bg'              => '#78350f',
                'layout_config.footer_text'            => '#fef3c7',
                'layout_config.name_color'             => '#78350f',
                'layout_config.name_x'                 => 30,
                'layout_config.name_y'                 => 30,
                'layout_config.name_font_family'       => 'serif',
                'layout_config.name_font_size'         => 18,
                'layout_config.name_is_bold'           => true,
                'layout_config.class_color'            => '#92400e',
                'layout_config.class_x'                => 30,
                'layout_config.class_y'                => 40,
                'layout_config.class_font_family'      => 'serif',
                'layout_config.photo_x'                => 4,
                'layout_config.photo_y'                => 26,
                'layout_config.photo_width'            => 22,
                'layout_config.photo_height'           => 42,
                'layout_config.photo_border_color'     => '#92400e',
                'layout_config.photo_border_width'     => 3,
                'layout_config.photo_rounded_corners'  => 4,
                'layout_config.school_name_font_family' => 'serif',
                'layout_config.school_name_color'      => '#fef3c7',
                'layout_config.motto_font_family'      => 'serif',
                'layout_config.motto_color'            => '#d6d3d1',
                'layout_config.motto_is_italic'        => true,
                'layout_config.motto_is_bold'          => false,
                'layout_config.qr_x'                   => 76,
                'layout_config.qr_y'                   => 42,
                'layout_config.contact_color'          => '#fef3c7',
                'layout_config.contact_font_size'      => 8,
                'layout_config.label_color'            => '#92400e',
                'layout_config.value_color'            => '#78350f',
                'layout_config.label_font_family'      => 'serif',
                'layout_config.value_font_family'      => 'serif',
            ]),

            'minimalist' => array_replace($base, [
                'layout_config.canvas_bg_color'       => '#ffffff',
                'layout_config.card_border_width'      => 1,
                'layout_config.card_border_color'      => '#e2e8f0',
                'layout_config.header_bg_color'        => '#ffffff',
                'layout_config.header_text_color'      => '#0f172a',
                'layout_config.primary_color'          => '#0f172a',
                'layout_config.primary_dark'           => '#0f172a',
                'layout_config.accent_color'           => '#94a3b8',
                'layout_config.footer_bg'              => '#f8fafc',
                'layout_config.footer_text'            => '#64748b',
                'layout_config.name_color'             => '#0f172a',
                'layout_config.name_x'                 => 35,
                'layout_config.name_y'                 => 30,
                'layout_config.name_font_size'         => 18,
                'layout_config.name_is_bold'           => true,
                'layout_config.class_color'            => '#94a3b8',
                'layout_config.class_x'                => 35,
                'layout_config.class_y'                => 40,
                'layout_config.photo_x'                => 5,
                'layout_config.photo_y'                => 26,
                'layout_config.photo_width'            => 22,
                'layout_config.photo_height'           => 42,
                'layout_config.photo_border_color'     => '#e2e8f0',
                'layout_config.photo_border_width'     => 1,
                'layout_config.photo_rounded_corners'  => 50,
                'layout_config.school_name_color'      => '#0f172a',
                'layout_config.motto_color'            => '#94a3b8',
                'layout_config.motto_is_italic'        => true,
                'layout_config.qr_x'                   => 76,
                'layout_config.qr_y'                   => 42,
                'layout_config.contact_color'          => '#94a3b8',
                'layout_config.contact_font_size'      => 8,
                'layout_config.label_color'            => '#94a3b8',
                'layout_config.value_color'            => '#0f172a',
            ]),

            'government' => array_replace($base, [
                'layout_config.canvas_bg_color'       => '#f0fdf4',
                'layout_config.canvas_gradient_end_color' => '#dcfce7',
                'layout_config.card_border_width'      => 4,
                'layout_config.card_border_color'      => '#166534',
                'layout_config.header_bg_color'        => '#166534',
                'layout_config.header_text_color'      => '#f0fdf4',
                'layout_config.primary_color'          => '#166534',
                'layout_config.primary_dark'           => '#14532d',
                'layout_config.accent_color'           => '#22c55e',
                'layout_config.footer_bg'              => '#14532d',
                'layout_config.footer_text'            => '#dcfce7',
                'layout_config.name_color'             => '#14532d',
                'layout_config.name_x'                 => 32,
                'layout_config.name_y'                 => 30,
                'layout_config.name_font_size'         => 17,
                'layout_config.name_is_bold'           => true,
                'layout_config.class_color'            => '#166534',
                'layout_config.class_x'                => 32,
                'layout_config.class_y'                => 40,
                'layout_config.photo_x'                => 5,
                'layout_config.photo_y'                => 26,
                'layout_config.photo_width'            => 22,
                'layout_config.photo_height'           => 42,
                'layout_config.photo_border_color'     => '#166534',
                'layout_config.photo_border_width'     => 3,
                'layout_config.photo_rounded_corners'  => 4,
                'layout_config.school_name_color'      => '#dcfce7',
                'layout_config.motto_color'            => '#86efac',
                'layout_config.motto_is_italic'        => false,
                'layout_config.qr_x'                   => 76,
                'layout_config.qr_y'                   => 42,
                'layout_config.contact_color'          => '#dcfce7',
                'layout_config.contact_font_size'      => 8,
                'layout_config.label_color'            => '#166534',
                'layout_config.value_color'            => '#14532d',
            ]),

            'playful' => array_replace($base, [
                'layout_config.canvas_bg_color'       => '#fdf2f8',
                'layout_config.canvas_gradient_end_color' => '#fce7f3',
                'layout_config.bg_mode'               => 'gradient',
                'layout_config.card_border_width'      => 3,
                'layout_config.card_border_color'      => '#ec4899',
                'layout_config.header_bg_color'        => '#ec4899',
                'layout_config.header_text_color'      => '#ffffff',
                'layout_config.primary_color'          => '#ec4899',
                'layout_config.primary_dark'           => '#be185d',
                'layout_config.accent_color'           => '#f97316',
                'layout_config.footer_bg'              => '#be185d',
                'layout_config.footer_text'            => '#fce7f3',
                'layout_config.name_color'             => '#be185d',
                'layout_config.name_x'                 => 34,
                'layout_config.name_y'                 => 28,
                'layout_config.name_font_size'         => 18,
                'layout_config.name_is_bold'           => true,
                'layout_config.class_color'            => '#f97316',
                'layout_config.class_x'                => 34,
                'layout_config.class_y'                => 38,
                'layout_config.photo_x'                => 5,
                'layout_config.photo_y'                => 24,
                'layout_config.photo_width'            => 24,
                'layout_config.photo_height'           => 44,
                'layout_config.photo_border_color'     => '#f97316',
                'layout_config.photo_border_width'     => 3,
                'layout_config.photo_rounded_corners'  => 14,
                'layout_config.school_name_color'      => '#fce7f3',
                'layout_config.motto_color'            => '#fbcfe8',
                'layout_config.motto_is_italic'        => true,
                'layout_config.qr_x'                   => 74,
                'layout_config.qr_y'                   => 40,
                'layout_config.contact_color'          => '#fce7f3',
                'layout_config.contact_font_size'      => 8,
                'layout_config.label_color'            => '#ec4899',
                'layout_config.value_color'            => '#be185d',
            ]),

            'collegiate' => array_replace($base, [
                'layout_config.canvas_bg_color'       => '#fef2f2',
                'layout_config.canvas_gradient_end_color' => '#fee2e2',
                'layout_config.card_border_width'      => 4,
                'layout_config.card_border_color'      => '#991b1b',
                'layout_config.header_bg_color'        => '#991b1b',
                'layout_config.header_text_color'      => '#fef2f2',
                'layout_config.primary_color'          => '#991b1b',
                'layout_config.primary_dark'           => '#7f1d1d',
                'layout_config.accent_color'           => '#dc2626',
                'layout_config.footer_bg'              => '#7f1d1d',
                'layout_config.footer_text'            => '#fecaca',
                'layout_config.name_color'             => '#7f1d1d',
                'layout_config.name_x'                 => 30,
                'layout_config.name_y'                 => 28,
                'layout_config.name_font_size'         => 20,
                'layout_config.name_is_bold'           => true,
                'layout_config.class_color'            => '#991b1b',
                'layout_config.class_x'                => 30,
                'layout_config.class_y'                => 38,
                'layout_config.photo_x'                => 4,
                'layout_config.photo_y'                => 24,
                'layout_config.photo_width'            => 24,
                'layout_config.photo_height'           => 44,
                'layout_config.photo_border_color'     => '#991b1b',
                'layout_config.photo_border_width'     => 3,
                'layout_config.photo_rounded_corners'  => 6,
                'layout_config.school_name_color'      => '#fecaca',
                'layout_config.motto_color'            => '#fca5a5',
                'layout_config.motto_is_italic'        => false,
                'layout_config.motto_is_bold'          => false,
                'layout_config.qr_x'                   => 76,
                'layout_config.qr_y'                   => 40,
                'layout_config.contact_color'          => '#fecaca',
                'layout_config.contact_font_size'      => 8,
                'layout_config.label_color'            => '#991b1b',
                'layout_config.value_color'            => '#7f1d1d',
            ]),

            'tech' => array_replace($base, [
                'layout_config.canvas_bg_color'       => '#020617',
                'layout_config.canvas_gradient_end_color' => '#0f172a',
                'layout_config.bg_mode'               => 'solid',
                'layout_config.card_border_width'      => 2,
                'layout_config.card_border_color'      => '#06b6d4',
                'layout_config.header_bg_color'        => '#0f172a',
                'layout_config.header_text_color'      => '#06b6d4',
                'layout_config.primary_color'          => '#06b6d4',
                'layout_config.primary_dark'           => '#020617',
                'layout_config.accent_color'           => '#22d3ee',
                'layout_config.footer_bg'              => '#06b6d4',
                'layout_config.footer_text'            => '#020617',
                'layout_config.name_color'             => '#e2e8f0',
                'layout_config.name_x'                 => 32,
                'layout_config.name_y'                 => 28,
                'layout_config.name_font_family'       => 'monospace',
                'layout_config.name_font_size'         => 17,
                'layout_config.name_is_bold'           => true,
                'layout_config.class_color'            => '#22d3ee',
                'layout_config.class_x'                => 32,
                'layout_config.class_y'                => 38,
                'layout_config.class_font_family'      => 'monospace',
                'layout_config.photo_x'                => 5,
                'layout_config.photo_y'                => 24,
                'layout_config.photo_width'            => 22,
                'layout_config.photo_height'           => 44,
                'layout_config.photo_border_color'     => '#06b6d4',
                'layout_config.photo_border_width'     => 2,
                'layout_config.photo_rounded_corners'  => 8,
                'layout_config.school_name_color'      => '#22d3ee',
                'layout_config.motto_color'            => '#67e8f9',
                'layout_config.motto_is_italic'        => false,
                'layout_config.qr_x'                   => 76,
                'layout_config.qr_y'                   => 40,
                'layout_config.contact_color'          => '#22d3ee',
                'layout_config.contact_font_size'      => 8,
                'layout_config.label_color'            => '#06b6d4',
                'layout_config.value_color'            => '#e2e8f0',
                'layout_config.label_font_family'      => 'monospace',
                'layout_config.value_font_family'      => 'monospace',
                'layout_config.meta_font_family'       => 'monospace',
            ]),

            'vintage' => array_replace($base, [
                'layout_config.canvas_bg_color'       => '#faf8f5',
                'layout_config.canvas_gradient_end_color' => '#f5f0e8',
                'layout_config.bg_mode'               => 'solid',
                'layout_config.card_border_width'      => 4,
                'layout_config.card_border_color'      => '#78350f',
                'layout_config.header_bg_color'        => '#78350f',
                'layout_config.header_text_color'      => '#faf8f5',
                'layout_config.primary_color'          => '#78350f',
                'layout_config.primary_dark'           => '#451a03',
                'layout_config.accent_color'           => '#a16207',
                'layout_config.footer_bg'              => '#451a03',
                'layout_config.footer_text'            => '#fef3c7',
                'layout_config.name_color'             => '#451a03',
                'layout_config.name_x'                 => 30,
                'layout_config.name_y'                 => 30,
                'layout_config.name_font_family'       => 'serif',
                'layout_config.name_font_size'         => 18,
                'layout_config.name_is_bold'           => true,
                'layout_config.class_color'            => '#78350f',
                'layout_config.class_x'                => 30,
                'layout_config.class_y'                => 40,
                'layout_config.class_font_family'      => 'serif',
                'layout_config.photo_x'                => 4,
                'layout_config.photo_y'                => 26,
                'layout_config.photo_width'            => 22,
                'layout_config.photo_height'           => 42,
                'layout_config.photo_border_color'     => '#78350f',
                'layout_config.photo_border_width'     => 3,
                'layout_config.photo_rounded_corners'  => 2,
                'layout_config.school_name_font_family' => 'serif',
                'layout_config.school_name_color'      => '#fef3c7',
                'layout_config.motto_font_family'      => 'serif',
                'layout_config.motto_color'            => '#a8a29e',
                'layout_config.motto_is_italic'        => true,
                'layout_config.qr_x'                   => 76,
                'layout_config.qr_y'                   => 42,
                'layout_config.contact_color'          => '#fef3c7',
                'layout_config.contact_font_size'      => 8,
                'layout_config.label_color'            => '#78350f',
                'layout_config.value_color'            => '#451a03',
                'layout_config.label_font_family'      => 'serif',
                'layout_config.value_font_family'      => 'serif',
                'layout_config.meta_font_family'       => 'serif',
            ]),
        ];

        return $themes[$theme] ?? $themes['professional'];
    }
}
