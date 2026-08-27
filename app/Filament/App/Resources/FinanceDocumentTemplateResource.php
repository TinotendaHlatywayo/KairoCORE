<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\FinanceDocumentTemplateResource\Pages;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Finance\Models\FinanceDocumentTemplate;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceItem;
use Modules\Finance\Models\Payment;
use Modules\Finance\Services\BillingDocumentSettingsService;
use Modules\Students\Models\Student;

class FinanceDocumentTemplateResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Finance');
    }

    protected static ?string $model = FinanceDocumentTemplate::class;

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Document Templates';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 99;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Billing Document Template')
                    ->description(__('Choose one of the 5 pre-designed templates, use it exactly as-is, or edit every section to build your own look.'))
                    ->schema([
                        Forms\Components\Select::make('document_type')
                            ->label(__('Document Type'))
                            ->options(FinanceDocumentTemplate::$documentTypes)
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('name')
                            ->label(__('Template Name'))
                            ->required()
                            ->placeholder(__('e.g. Standard Student Invoice Layout'))
                            ->live(),

                        Forms\Components\Select::make('design_theme')
                            ->label(__('Choose Pre-designed Theme'))
                            ->options(FinanceDocumentTemplate::$themes)
                            ->default('classic_line')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                self::applyThemeDefaults($set, $state);
                            }),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Set as Active Template'))
                            ->default(true)
                            ->helperText(__('Only one template can be active per document type. The active template is used when printing.')),
                    ])->columns(2),

                Forms\Components\Grid::make(3)
                    ->schema([
                        // ===== Left Column: Section-by-section customisation =====
                        Forms\Components\Group::make([
                            Forms\Components\Section::make('Document Typography')
                                ->description(__('Font used across the whole document.'))
                                ->schema([
                                    Forms\Components\Select::make('layout_config.font_family')
                                        ->label(__('Document Font'))
                                        ->options(
                                            collect(FinanceDocumentTemplate::$fonts)
                                                ->mapWithKeys(fn (string $label, string $value) => [
                                                    $value => new HtmlString('<span style="font-family: '.$value.';">'.$label.'</span>'),
                                                ])->all()
                                        )
                                        ->allowHtml()
                                        ->default('Helvetica, sans-serif')
                                        ->live(),
                                ]),

                            Forms\Components\Section::make('School Header')
                                ->description(__('School name, logo, motto and contact details at the top of the document.'))
                                ->schema([
                                    Forms\Components\Toggle::make('layout_config.header.show_logo')
                                        ->label(__('Insert School Logo'))
                                        ->default(true)
                                        ->live(),
                                    Forms\Components\FileUpload::make('layout_config.header.logo')
                                        ->label(__('Template Logo'))
                                        ->helperText(__('Optional. Upload your own logo for this template; otherwise the school branding logo is used. Remove it to fall back to branding.'))
                                        ->image()
                                        ->directory('tenant/branding/templates')
                                        ->disk('public')
                                        ->visibility('public')
                                        ->columns(1)
                                        ->live(),
                                    Forms\Components\Select::make('layout_config.header.logo_position')
                                        ->label(__('Logo Position'))
                                        ->options(FinanceDocumentTemplate::$logoPositions)
                                        ->default('center')
                                        ->live(),
                                    Forms\Components\Select::make('layout_config.header.logo_size')
                                        ->label(__('Logo Size'))
                                        ->options(array_combine(range(40, 140, 10), array_map(fn ($px) => "{$px}px", range(40, 140, 10))))
                                        ->default(78)
                                        ->live(),
                                    Forms\Components\Toggle::make('layout_config.header.show_school_name')
                                        ->label(__('Show School Name'))
                                        ->default(true)
                                        ->live(),
                                    Forms\Components\TextInput::make('layout_config.header.school_name_font_size')
                                        ->label(__('School Name Font Size'))
                                        ->numeric()->minValue(10)->maxValue(40)->default(22)
                                        ->live(debounce: 300),
                                    Forms\Components\ColorPicker::make('layout_config.header.school_name_color')
                                        ->label(__('School Name Colour'))
                                        ->live(),
                                    Forms\Components\Toggle::make('layout_config.header.school_name_bold')
                                        ->label(__('School Name Bold'))
                                        ->default(true)
                                        ->live(),
                                    Forms\Components\Toggle::make('layout_config.header.school_name_italic')
                                        ->label(__('School Name Italic'))
                                        ->default(false)
                                        ->live(),
                                    Forms\Components\Toggle::make('layout_config.header.show_motto')
                                        ->label(__('Show School Motto'))
                                        ->default(true)
                                        ->live(),
                                    Forms\Components\TextInput::make('layout_config.header.motto_font_size')
                                        ->label(__('Motto Font Size'))
                                        ->numeric()->minValue(8)->maxValue(20)->default(12)
                                        ->live(debounce: 300),
                                    Forms\Components\ColorPicker::make('layout_config.header.motto_color')
                                        ->label(__('Motto Colour'))
                                        ->live(),
                                    Forms\Components\Toggle::make('layout_config.header.motto_italic')
                                        ->label(__('Motto Italic'))
                                        ->default(true)
                                        ->live(),
                                    Forms\Components\Toggle::make('layout_config.header.show_contact')
                                        ->label(__('Show Address / Contact Details'))
                                        ->default(true)
                                        ->live(),
                                    Forms\Components\TextInput::make('layout_config.header.contact_font_size')
                                        ->label(__('Contact Font Size'))
                                        ->numeric()->minValue(6)->maxValue(16)->default(9)
                                        ->live(debounce: 300),
                                    Forms\Components\ColorPicker::make('layout_config.header.contact_color')
                                        ->label(__('Contact Colour'))
                                        ->live(),
                                ])->columns(2)->collapsible()->collapsed(),

                            Forms\Components\Section::make('Document Title')
                                ->description(__('The INVOICE / RECEIPT / STATEMENT heading and any extra text you want beneath it.'))
                                ->schema([
                                    Forms\Components\TextInput::make('layout_config.title.font_size')
                                        ->label(__('Title Font Size'))
                                        ->numeric()->minValue(10)->maxValue(36)->default(18)
                                        ->live(debounce: 300),
                                    Forms\Components\ColorPicker::make('layout_config.title.color')
                                        ->label(__('Title Colour'))
                                        ->live(),
                                    Forms\Components\Toggle::make('layout_config.title.bold')
                                        ->label(__('Bold'))
                                        ->default(true)
                                        ->live(),
                                    Forms\Components\Toggle::make('layout_config.title.italic')
                                        ->label(__('Italic'))
                                        ->default(false)
                                        ->live(),
                                    Forms\Components\Textarea::make('layout_config.title.extra_text')
                                        ->label(__('Additional Text Under Title'))
                                        ->placeholder(__('e.g. For the attention of the Parent / Guardian'))
                                        ->default('')
                                        ->rows(2)
                                        ->live(debounce: 300),
                                ])->columns(2)->collapsible()->collapsed(),

                            Forms\Components\Section::make('Student & Parent Information')
                                ->description(__('Style the student / parent details table.'))
                                ->schema([
                                    Forms\Components\TextInput::make('layout_config.metadata.font_size')
                                        ->label(__('Font Size'))
                                        ->numeric()->minValue(7)->maxValue(16)->default(10)
                                        ->live(debounce: 300),
                                    Forms\Components\ColorPicker::make('layout_config.metadata.color')
                                        ->label(__('Text Colour'))
                                        ->live(),
                                    Forms\Components\Toggle::make('layout_config.metadata.bold')
                                        ->label(__('Bold'))
                                        ->default(false)
                                        ->live(),
                                    Forms\Components\Toggle::make('layout_config.metadata.italic')
                                        ->label(__('Italic'))
                                        ->default(false)
                                        ->live(),
                                ])->columns(2)->collapsible()->collapsed(),

                            Forms\Components\Section::make('Items & Totals Table')
                                ->description(__('Fee lines, breakdown table and totals styling.'))
                                ->schema([
                                    Forms\Components\TextInput::make('layout_config.table.font_size')
                                        ->label(__('Table Font Size'))
                                        ->numeric()->minValue(7)->maxValue(16)->default(11)
                                        ->live(debounce: 300),
                                    Forms\Components\ColorPicker::make('layout_config.table.header_bg')
                                        ->label(__('Table Header Background'))
                                        ->live(),
                                    Forms\Components\ColorPicker::make('layout_config.table.header_color')
                                        ->label(__('Table Header Text Colour'))
                                        ->live(),
                                    Forms\Components\Toggle::make('layout_config.table.header_bold')
                                        ->label(__('Table Header Bold'))
                                        ->default(true)
                                        ->live(),
                                    Forms\Components\ColorPicker::make('layout_config.table.body_color')
                                        ->label(__('Body Text Colour'))
                                        ->live(),
                                ])->columns(2)->collapsible()->collapsed(),

                            Forms\Components\Section::make('Payment Instructions')
                                ->description(__('The bank / EcoCash payment channels box (invoices only).'))
                                ->visible(fn (Get $get) => $get('document_type') === 'invoice')
                                ->schema([
                                    Forms\Components\Toggle::make('layout_config.instructions.show')
                                        ->label(__('Show Payment Instructions'))
                                        ->default(true)
                                        ->live(),
                                    Forms\Components\TextInput::make('layout_config.instructions.font_size')
                                        ->label(__('Font Size'))
                                        ->numeric()->minValue(6)->maxValue(14)->default(10)
                                        ->live(debounce: 300),
                                    Forms\Components\ColorPicker::make('layout_config.instructions.color')
                                        ->label(__('Text Colour'))
                                        ->live(),
                                ])->columns(2)->collapsible()->collapsed(),

                            Forms\Components\Section::make('Footer, Signatures & QR')
                                ->description(__('Signature lines, verification QR code and any extra footer text.'))
                                ->schema([
                                    Forms\Components\Toggle::make('layout_config.footer.show_signatures')
                                        ->label(__('Show Signature Lines'))
                                        ->default(true)
                                        ->live(),
                                    Forms\Components\Toggle::make('layout_config.footer.show_qr')
                                        ->label(__('Show Verification QR Code'))
                                        ->default(true)
                                        ->live(),
                                    Forms\Components\Select::make('layout_config.footer.qr_position')
                                        ->label(__('QR Code Position'))
                                        ->options([
                                            'right' => __('Right'),
                                            'center' => __('Center'),
                                            'left' => __('Left'),
                                        ])
                                        ->default('right')
                                        ->live(),
                                    Forms\Components\TextInput::make('layout_config.footer.qr_size')
                                        ->label(__('QR Code Size'))
                                        ->numeric()->minValue(40)->maxValue(140)->default(70)
                                        ->live(debounce: 300),
                                    Forms\Components\TextInput::make('layout_config.footer.font_size')
                                        ->label(__('Footer Font Size'))
                                        ->numeric()->minValue(6)->maxValue(14)->default(10)
                                        ->live(debounce: 300),
                                    Forms\Components\ColorPicker::make('layout_config.footer.color')
                                        ->label(__('Footer Text Colour'))
                                        ->live(),
                                    Forms\Components\Textarea::make('layout_config.footer.extra_text')
                                        ->label(__('Additional Footer Text'))
                                        ->placeholder(__('e.g. This invoice was generated electronically and is valid without a signature.'))
                                        ->default('')
                                        ->rows(2)
                                        ->live(debounce: 300),
                                ])->columns(2)->collapsible()->collapsed(),
                        ])->columnSpan(2),

                        // ===== Right Column: Live Preview =====
                        Forms\Components\Group::make([
                            Forms\Components\Section::make('Live Preview')
                                ->description(__('Renders the document as it will look when printed, in real-time.'))
                                ->schema([
                                    Forms\Components\Placeholder::make('live_preview')
                                        ->content(fn (Get $get) => new HtmlString(self::generateFinancePreviewHtml($get))),
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
                Tables\Columns\TextColumn::make('document_type')
                    ->label(__('Document Type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => FinanceDocumentTemplate::$documentTypes[$state] ?? ucfirst($state))
                    ->color('info'),
                Tables\Columns\TextColumn::make('design_theme')
                    ->label(__('Themed Style'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => FinanceDocumentTemplate::$themes[$state] ?? $state)
                    ->color('gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')
                    ->options(FinanceDocumentTemplate::$documentTypes),
            ])
            ->defaultSort('document_type')
            ->actions([
                Tables\Actions\Action::make('setActive')
                    ->label(__('Set Active'))
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn ($record) => ! $record->is_active)
                    ->action(function ($record) {
                        $record->update(['is_active' => true]);

                        Notification::make()
                            ->title(__('Template activated'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinanceDocumentTemplates::route('/'),
            'create' => Pages\CreateFinanceDocumentTemplate::route('/create'),
            'edit' => Pages\EditFinanceDocumentTemplate::route('/{record}/edit'),
        ];
    }

    /**
     * Fill any section keys missing from the saved layout_config with the
     * theme-resolved section defaults. Filament materialises unset nested form
     * state as empty values on the edit page, which would otherwise override
     * the true defaults and hide e.g. QR codes and signature lines.
     */
    public static function fillLayoutConfigDefaults(array $data): array
    {
        $theme = $data['design_theme'] ?? 'classic_line';
        $colors = FinanceDocumentTemplate::$themeDefaults[$theme] ?? FinanceDocumentTemplate::$themeDefaults['classic_line'];
        $saved = $data['layout_config'] ?? [];

        $merged = [];
        foreach (FinanceDocumentTemplate::$sectionDefaults as $section => $defaults) {
            $merged[$section] = [];
            foreach ($defaults as $field => $value) {
                if (isset($saved[$section][$field]) && $saved[$section][$field] !== null && $saved[$section][$field] !== '') {
                    $merged[$section][$field] = $saved[$section][$field];
                } else {
                    $merged[$section][$field] = (is_string($value) && str_starts_with($value, '{') && str_ends_with($value, '}'))
                        ? ($colors[trim($value, '{}')] ?? $value)
                        : $value;
                }
            }
        }
        foreach ($saved as $key => $value) {
            if (! isset(FinanceDocumentTemplate::$sectionDefaults[$key])) {
                $merged[$key] = $value;
            }
        }
        $data['layout_config'] = $merged;

        return $data;
    }

    /**
     * Populate every per-section field with the selected theme's defaults.
     */
    protected static function applyThemeDefaults(Set $set, string $designTheme): void
    {
        $colors = FinanceDocumentTemplate::$themeDefaults[$designTheme] ?? FinanceDocumentTemplate::$themeDefaults['classic_line'];

        $set('layout_config.font_family', $colors['font_family']);

        foreach (FinanceDocumentTemplate::$sectionDefaults as $section => $fields) {
            foreach ($fields as $field => $value) {
                // The uploaded logo is user content — never overwrite it with defaults.
                if ($field === 'logo') {
                    continue;
                }
                if (is_string($value) && str_starts_with($value, '{') && str_ends_with($value, '}')) {
                    $value = $colors[trim($value, '{}')] ?? $value;
                }
                $set("layout_config.{$section}.{$field}", $value);
            }
        }
    }

    /**
     * Render a true, full-size A4 live preview of the configured document by
     * reusing the exact same Blade template that produces the printed PDF.
     */
    protected static function generateFinancePreviewHtml(Get $get): string
    {
        $school = null;
        try {
            $school = School::find(app('current_tenant')->id);
        } catch (\Exception $e) {
            // Tenant unavailable — the preview falls back to a placeholder.
        }

                if (! $school) {
            return '<div style="padding:16px;color:#94a3b8;font-size:13px;">Preview is unavailable outside a school workspace.</div>';
        }

        // Route URLs inside the preview (e.g. the QR verification link) need
        // the {tenant} default parameter for this render cycle.
        URL::defaults(['tenant' => $school->subdomain]);

        $documentType = $get('document_type') ?? 'invoice';

        $template = new FinanceDocumentTemplate;
        $template->design_theme = $get('design_theme') ?? 'classic_line';
        $layoutConfig = $get('layout_config') ?? [];

        // Before a form is saved, a freshly uploaded logo is a temporary file
        // (Livewire\TemporaryUploadedFile) that is not yet resolvable to a public
        // path. Convert it to a web-loadable preview URL so the live preview can
        // show the logo immediately. The FileUpload form state may be a plain
        // string, a list, or a UUID-keyed map (e.g. after a record is reopened),
        // so the first non-empty value is resolved in every case.
        if (isset($layoutConfig['header']['logo'])) {
            $layoutConfig['header']['logo'] = self::normalizeLogoValue($layoutConfig['header']['logo']);
        }

        $template->layout_config = $layoutConfig;

        try {
            $html = match ($documentType) {
                'receipt' => self::renderReceiptPreview($school, $template),
                'statement' => self::renderStatementPreview($school, $template),
                default => self::renderInvoicePreview($school, $template),
            };
        } catch (\Throwable $e) {
            return '<div style="padding:16px;color:#dc2626;font-size:13px;">Preview could not be rendered: '.e($e->getMessage()).'</div>';
        }

        // Image sources inside the PDF blades are absolute filesystem paths
        // (public_path()); rewrite them to web URLs so the browser can load them.
        $html = str_replace(public_path(), asset(''), $html);

        return '<div style="background:#eef2f7;padding:14px;border-radius:12px;overflow:auto;max-width:100%;">'
            .'<div style="width:334px;height:472px;overflow:hidden;margin:0 auto;position:relative;">'
            .'<div style="width:794px;height:1123px;background:#fff;box-shadow:0 8px 18px rgba(15,23,42,0.12);transform:scale(0.42);transform-origin:top left;">'
            .$html
            .'</div></div>'
            .'<div style="text-align:center;font-size:11px;color:#94a3b8;margin-top:8px;">A4 · live preview — '.strtoupper($documentType).'</div>'
            .'</div>';
    }

    /**
     * Render a preview document with an isolated view factory.
     *
     * Rendering the PDF blades through the shared application factory from
     * inside a Filament placeholder triggers Laravel's
     * `flushStateIfDoneRendering()` once the nested render returns, which wipes
     * the active Blade component stack and crashes the surrounding Livewire
     * render with "Undefined array key 0". An isolated factory keeps its own
     * render counter and component stack, so the flush can only ever touch the
     * preview's own (empty) state.
     */
    protected static function renderPreviewView(string $view, array $data): string
    {
        if (! ExtendBlade::isRenderingLivewireComponent()) {
            return view($view, $data)->render();
        }

        $factory = new Factory(
            app('view.engine.resolver'),
            new FileViewFinder(app('files'), config('view.paths')),
            app('events'),
        );
        $factory->share('__env', $factory);

        return $factory->make($view, $data)->render();
    }

    /**
     * Resolve a just-uploaded (unsaved) file to a URL the browser can load.
     *
     * Livewire stores unsaved uploads in its temporary disk and serves them via
     * the signed `livewire.preview-file` route, which the browser preview can
     * reference directly. Falls back to an empty string when the file type is
     * not previewable or a URL cannot be generated.
     */
    protected static function temporaryFileUrl(TemporaryUploadedFile $file): string
    {
        try {
            return $file->isPreviewable() ? (string) $file->temporaryUrl() : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Resolve a logo from the FileUpload form state to a single value the
     * preview blades can render (a web URL for unsaved uploads, a stored path
     * or a plain string otherwise).
     */
    protected static function normalizeLogoValue(mixed $logo): string
    {
        if ($logo instanceof TemporaryUploadedFile) {
            return self::temporaryFileUrl($logo);
        }

        if (is_array($logo)) {
            foreach (array_values($logo) as $candidate) {
                if ($candidate instanceof TemporaryUploadedFile) {
                    return self::temporaryFileUrl($candidate);
                }
            }

            $logo = array_values($logo)[0] ?? '';

            return self::normalizeLogoValue($logo);
        }

        return is_string($logo) ? $logo : '';
    }

    protected static function renderInvoicePreview($school, FinanceDocumentTemplate $template): string
    {
        $invoice = Invoice::with(['student', 'term.academicYear', 'items'])
            ->where('school_id', $school->id)
            ->whereHas('items')
            ->whereHas('student')
            ->orderBy('id', 'desc')
            ->first();

        // Fresh schools (or schools whose sample invoices lost their student
        // link) still deserve a live preview — synthesise a sample document.
        if (! $invoice) {
            $invoice = self::makeSampleInvoice($school);
        }

        return self::renderPreviewView('modules.finance.invoice-pdf', [
            'invoice' => $invoice,
            'school' => $school,
            'student' => $invoice->student,
            'config' => self::previewBillingConfig($school),
            'template' => $template,
        ]);
    }

    /**
     * A non-persisted sample invoice built from the school's newest enrolled
     * student, used when no real invoice can seed the template preview.
     */
    protected static function makeSampleInvoice($school): Invoice
    {
        $student = Student::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->orderBy('id')
            ->first();

        if (! $student) {
            throw new \RuntimeException('No sample invoice or student found for this school.');
        }

        // Term context for the "Term Billing Period" line — reuse the school's
        // active term when available, otherwise synthesise one.
        $term = \Modules\Academics\Models\Term::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->with('academicYear')
            ->orderBy('id')
            ->first();

        if (! $term) {
            $year = new \Modules\Academics\Models\AcademicYear([
                'school_id' => $school->id,
                'name' => now()->format('Y').' Academic Year',
            ]);
            $term = new \Modules\Academics\Models\Term([
                'school_id' => $school->id,
                'name' => 'Term 1',
            ]);
            $term->setRelation('academicYear', $year);
        }

        $invoice = new Invoice([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'term_id' => $term?->id,
            'invoice_number' => 'SAMPLE-'.date('y'),
            'currency' => config('app.currency', 'USD'),
            'subtotal_amount' => 120,
            'discount_amount' => 0,
            'total_amount' => 120,
            'paid_amount' => 40,
            'balance_amount' => 80,
            'status' => 'partial',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);
        $invoice->created_at = now();
        $invoice->setRelation('student', $student);
        $invoice->setRelation('term', $term);
        $invoice->setRelation('items', collect([
            new InvoiceItem(['name' => 'Tuition Fees (sample)', 'amount' => 120]),
        ]));

        return $invoice;
    }

    protected static function renderReceiptPreview($school, FinanceDocumentTemplate $template): string
    {
        $invoice = Invoice::with(['student', 'term.academicYear'])
            ->where('school_id', $school->id)
            ->whereHas('payments')
            ->orderBy('id', 'desc')
            ->first();

        if (! $invoice) {
            throw new \RuntimeException('No sample receipt found for this school.');
        }

        $payment = Payment::where('invoice_id', $invoice->id)->orderBy('id', 'desc')->first();

        return self::renderPreviewView('modules.finance.receipt-pdf', [
            'invoice' => $invoice,
            'payment' => $payment,
            'school' => $school,
            'config' => self::previewBillingConfig($school),
            'template' => $template,
        ]);
    }

    protected static function renderStatementPreview($school, FinanceDocumentTemplate $template): string
    {
        $invoice = Invoice::with('student')
            ->where('school_id', $school->id)
            ->orderBy('id', 'desc')
            ->first();

        if (! $invoice) {
            throw new \RuntimeException('No sample statement found for this school.');
        }

        $student = $invoice->student;
        $invoices = Invoice::where('student_id', $student->id)->orderBy('created_at', 'asc')->get();
        $payments = Payment::where('school_id', $school->id)
            ->whereIn('invoice_id', $invoices->pluck('id'))
            ->where('is_reversed', false)
            ->orderBy('created_at', 'asc')
            ->get();

        $ledger = [];
        $balance = 0;

        foreach ($invoices as $inv) {
            $balance += $inv->subtotal_amount;
            $ledger[] = ['date' => $inv->created_at, 'type' => "Gross Fees Billed ({$inv->invoice_number})", 'debit' => $inv->subtotal_amount, 'credit' => 0.00, 'running_balance' => $balance];

            if ($inv->discount_amount > 0) {
                $balance -= $inv->discount_amount;
                $ledger[] = ['date' => $inv->created_at, 'type' => 'Waiver Applied: '.($inv->waiver_details ?? 'Scholarship / Discount'), 'debit' => 0.00, 'credit' => $inv->discount_amount, 'running_balance' => $balance];
            }
        }

        foreach ($payments as $pay) {
            $balance -= $pay->amount;
            $ledger[] = ['date' => $pay->payment_date, 'type' => "Payment Received (Receipt: {$pay->receipt_number})", 'debit' => 0.00, 'credit' => $pay->amount, 'running_balance' => $balance];
        }

        usort($ledger, fn ($a, $b) => $a['date'] <=> $b['date']);

        return self::renderPreviewView('modules.finance.statement-pdf', [
            'student' => $student,
            'school' => $school,
            'ledger' => $ledger,
            'current_balance' => $balance,
            'config' => self::previewBillingConfig($school),
            'template' => $template,
            'verify_hash' => $invoices->last()?->integrity_hash,
        ]);
    }

    protected static function previewBillingConfig($school): array
    {
        $config = BillingDocumentSettingsService::get();

        $legacy = $school->settings['invoice_format'] ?? null;
        if (is_array($legacy)) {
            $config = array_merge($config, $legacy);
        }

        return $config;
    }
}
