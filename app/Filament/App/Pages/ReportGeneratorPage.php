<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\GeneratedReportResource;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Modules\Reports\Models\EnterpriseReportTemplate;
use Modules\Reports\Models\ReportSchedule;
use Modules\Reports\Services\DatasetRegistry;
use Modules\Reports\Services\ReportExecutionService;

class ReportGeneratorPage extends Page
{
    use ModuleAwareActiveNavigation;
    use ModulePermissionAccess;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Reports & Intelligence';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    protected static ?string $navigationLabel = 'Generate Report';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?string $title = 'Enterprise Report Designer & Generator';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.app.pages.report-generator-page';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'layout_settings' => [
                'primary_color' => '#15803d',
                'show_logo' => true,
                'show_signature_block' => true,
                'header_text' => 'OFFICIAL INSTITUTIONAL RECORD',
                'footer_text' => 'Confidential - Generated via SchoolCore ERP Reporting System.',
            ],
            'orientation' => 'portrait',
            'output_format' => 'pdf',
            'sharing_scope' => 'private',
            'schedule_enabled' => false,
        ]);
    }

    public function form(Form $form): Form
    {
        $registry = app(DatasetRegistry::class);

        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Sources & Joins')
                        ->description(__('Choose data sources and their relationships'))
                        ->schema([
                            CheckboxList::make('datasets')
                                ->label(__('Reporting Data Sources'))
                                ->helperText(__('Select one or more sources. The first selection becomes the primary source.'))
                                ->options($registry->groupedForPicker() ? $this->datasetOptions($registry) : [])
                                ->columns(2)
                                ->reactive()
                                ->required()
                                ->afterStateUpdated(function ($set, $get, $state) {
                                    // Rebuild the join graph options and prune fields
                                    // whose source dataset was deselected.
                                    $set('joins', []);

                                    $kept = [];
                                    foreach (($get('selected_fields') ?? []) as $field) {
                                        foreach ((array) $state as $dataset) {
                                            if (str_starts_with($field, $dataset.'.')) {
                                                $kept[] = $field;
                                                break;
                                            }
                                        }
                                    }
                                    $set('selected_fields', $kept);
                                }),

                            CheckboxList::make('joins')
                                ->label(__('Cross-Module Relationships (Join Graph)'))
                                ->helperText(__('Enable the connection edges that link your selected sources together.'))
                                ->options(fn (callable $get) => $this->joinOptions($registry, $get('datasets')))
                                ->columns(2)
                                ->visible(fn (callable $get) => count($get('datasets')) > 1),
                        ]),

                    Wizard\Step::make('Configure Fields')
                        ->description(__('Select the output columns'))
                        ->schema([
                            CheckboxList::make('selected_fields')
                                ->label(__('Output Columns'))
                                ->helperText(__('Fields are labelled with their source dataset.'))
                                ->options(fn (callable $get) => $this->fieldOptions($registry, $get('datasets')))
                                ->columns(2)
                                ->reactive()
                                ->required(),
                        ]),

                    Wizard\Step::make('Filters')
                        ->description(__('Restrict which rows appear'))
                        ->schema([
                            Repeater::make('filters')
                                ->label(__('Filter Conditions'))
                                ->schema([
                                    Select::make('dataset')
                                        ->label(__('Source'))
                                        ->options(fn (callable $get) => $this->datasetOptions($registry))
                                        ->reactive()
                                        ->required(),
                                    Select::make('key')
                                        ->label(__('Field'))
                                        ->options(function (callable $get) use ($registry) {
                                            return $this->fieldOptions($registry, [$get('dataset')]);
                                        })
                                        ->reactive()
                                        ->required(),
                                    Select::make('op')
                                        ->label(__('Operator'))
                                        ->options([
                                            'eq' => __('Equals (=)'),
                                            'neq' => __('Not equal (≠)'),
                                            'gt' => __('Greater than (>)'),
                                            'gte' => __('Greater or equal (≥)'),
                                            'lt' => __('Less than (<)'),
                                            'lte' => __('Less or equal (≤)'),
                                            'contains' => __('Contains'),
                                            'starts' => __('Starts with'),
                                            'ends' => __('Ends with'),
                                            'is_null' => __('Is empty (NULL)'),
                                            'is_not_null' => __('Is not empty'),
                                        ])
                                        ->default('eq')
                                        ->reactive()
                                        ->required(),
                                    TextInput::make('value')
                                        ->label(__('Value'))
                                        ->visible(fn (callable $get) => ! in_array($get('op'), ['is_null', 'is_not_null'])),
                                    Select::make('boolean')
                                        ->label(__('Logic'))
                                        ->options(['and' => __('AND'), 'or' => __('OR')])
                                        ->default('and'),
                                ])
                                ->defaultItems(0)
                                ->collapsible()
                                ->grid(3),
                        ]),

                    Wizard\Step::make('Grouping & Totals')
                        ->description(__('Aggregate rows and add totals'))
                        ->schema([
                            CheckboxList::make('grouping')
                                ->label(__('Group Rows By'))
                                ->options(fn (callable $get) => $this->fieldOptions($registry, $get('datasets')))
                                ->columns(2),

                            Repeater::make('calculations')
                                ->label(__('Aggregate Calculations'))
                                ->schema([
                                    Select::make('type')
                                        ->label(__('Aggregate'))
                                        ->options(['sum' => 'Sum', 'avg' => 'Average', 'min' => 'Minimum', 'max' => 'Maximum', 'count' => 'Count'])
                                        ->default('sum')
                                        ->required(),
                                    Select::make('dataset')
                                        ->label(__('Source'))
                                        ->options($this->datasetOptions($registry))
                                        ->reactive()
                                        ->required(),
                                    Select::make('field')
                                        ->label(__('Field'))
                                        ->options(function (callable $get) use ($registry) {
                                            return $this->fieldOptions($registry, [$get('dataset')], true);
                                        })
                                        ->reactive()
                                        ->required(),
                                    TextInput::make('alias')
                                        ->label(__('Total Label (alias)'))
                                        ->placeholder(__('e.g. total_outstanding')),
                                ])
                                ->defaultItems(0)
                                ->collapsible()
                                ->grid(3),
                        ]),

                    Wizard\Step::make('Sorting')
                        ->description(__('Order the output rows'))
                        ->schema([
                            Repeater::make('sorting')
                                ->label(__('Sort Rules'))
                                ->schema([
                                    Select::make('dataset')
                                        ->label(__('Source'))
                                        ->options($this->datasetOptions($registry))
                                        ->reactive()
                                        ->required(),
                                    Select::make('field')
                                        ->label(__('Field'))
                                        ->options(function (callable $get) use ($registry) {
                                            return $this->fieldOptions($registry, [$get('dataset')]);
                                        })
                                        ->reactive()
                                        ->required(),
                                    Select::make('direction')
                                        ->label(__('Direction'))
                                        ->options(['asc' => 'Ascending', 'desc' => 'Descending'])
                                        ->default('asc'),
                                ])
                                ->defaultItems(1)
                                ->collapsible()
                                ->grid(3),
                        ]),

                    Wizard\Step::make('Visualizations')
                        ->description(__('Add charts for dashboards and print previews'))
                        ->schema([
                            Repeater::make('visualizations')
                                ->label(__('Chart Definitions'))
                                ->schema([
                                    Select::make('type')
                                        ->label(__('Chart Type'))
                                        ->options([
                                            'bar' => 'Bar Chart',
                                            'line' => 'Line Chart',
                                            'pie' => 'Pie Chart',
                                            'doughnut' => 'Doughnut',
                                            'polarArea' => 'Polar Area',
                                            'radar' => 'Radar',
                                        ])
                                        ->default('bar')
                                        ->required(),
                                    TextInput::make('title')
                                        ->label(__('Chart Title'))
                                        ->required(),
                                    Select::make('label')
                                        ->label(__('Category Axis (label field)'))
                                        ->options(fn (callable $get) => $this->fieldOptions($registry, $get('../../datasets')))
                                        ->reactive(),
                                    Repeater::make('series')
                                        ->label(__('Data Series'))
                                        ->schema([
                                            Select::make('field')
                                                ->label(__('Value Field'))
                                                ->options(fn (callable $get) => $this->fieldOptions($registry, $get('../../../../datasets')))
                                                ->reactive()
                                                ->required(),
                                            TextInput::make('label')
                                                ->label(__('Series Label')),
                                            ColorPicker::make('color')
                                                ->label(__('Color')),
                                        ])
                                        ->defaultItems(1)
                                        ->grid(2),
                                ])
                                ->defaultItems(0)
                                ->collapsible(),
                        ]),

                    Wizard\Step::make('Design Layout')
                        ->description(__('Name, branding and access'))
                        ->schema([
                            TextInput::make('template_name')
                                ->label(__('Report / Template Name'))
                                ->placeholder(__('e.g., Q2 Outstanding Defaulters List'))
                                ->required()
                                ->rules([
                                    function () {
                                        return function (string $attribute, $value, \Closure $fail) {
                                            /** @var User|null $user */
                                            $user = Auth::user();
                                            $schoolId = session('current_tenant')->id ?? ($user ? $user->school_id : null);

                                            $exists = EnterpriseReportTemplate::where('school_id', $schoolId)
                                                ->where('name', $value)
                                                ->exists();

                                            if ($exists) {
                                                $fail('A reporting template with this name already exists. Please enter a unique layout name.');
                                            }
                                        };
                                    },
                                ]),

                            Radio::make('orientation')
                                ->label(__('Page Layout Orientation'))
                                ->options([
                                    'portrait' => 'Portrait (A4 Vertically-aligned)',
                                    'landscape' => 'Landscape (A4 Horizontally-aligned)',
                                ])
                                ->default('portrait')
                                ->required(),

                            Select::make('sharing_scope')
                                ->label(__('Sharing Scope'))
                                ->options([
                                    'private' => 'Private (only me)',
                                    'department' => 'Department',
                                    'school' => 'Whole school',
                                ])
                                ->default('private')
                                ->required(),

                            ColorPicker::make('layout_settings.primary_color')
                                ->label(__('Brand Signature Accent Color'))
                                ->default('#15803d'),

                            TextInput::make('layout_settings.header_text')
                                ->label(__('Header Subtitle Callout')),

                            TextInput::make('layout_settings.footer_text')
                                ->label(__('Footer Security/Confidentiality Disclaimer')),

                            Toggle::make('layout_settings.show_logo')
                                ->label(__('Include Institution Logo Badge'))
                                ->default(true),

                            Toggle::make('layout_settings.show_signature_block')
                                ->label(__('Add Official Signature Verification Block'))
                                ->default(true),
                        ]),

                    Wizard\Step::make('Output & Schedule')
                        ->description(__('Format, delivery and automation'))
                        ->schema([
                            Radio::make('output_format')
                                ->label(__('Compilation Output Format'))
                                ->options([
                                    'pdf' => 'Print-Ready Standard PDF (Pre-formatted)',
                                    'csv' => 'Data Export Spreadsheet CSV (Raw Data Layout)',
                                    'xls' => 'Excel Workbook (XLS)',
                                    'json' => 'Structured JSON Payload',
                                ])
                                ->default('pdf')
                                ->required(),

                            Toggle::make('schedule_enabled')
                                ->label(__('Schedule This Report (Automated Distribution)'))
                                ->reactive()
                                ->default(false),

                            TextInput::make('schedule_name')
                                ->label(__('Schedule Name'))
                                ->visible(fn (callable $get) => (bool) $get('schedule_enabled')),

                            Select::make('schedule_frequency')
                                ->label(__('Frequency'))
                                ->options([
                                    'daily' => 'Daily',
                                    'weekly' => 'Weekly',
                                    'monthly' => 'Monthly',
                                    'quarterly' => 'Quarterly',
                                    'yearly' => 'Yearly',
                                ])
                                ->default('monthly')
                                ->visible(fn (callable $get) => (bool) $get('schedule_enabled')),

                            Select::make('schedule_distribution')
                                ->label(__('Distribution Method'))
                                ->options([
                                    'email' => 'Email recipients',
                                    'notification' => 'In-app notification',
                                    'both' => 'Both',
                                ])
                                ->default('email')
                                ->visible(fn (callable $get) => (bool) $get('schedule_enabled')),

                            TagsInput::make('schedule_recipients')
                                ->label(__('Recipient Email Addresses'))
                                ->placeholder(__('type email and press enter'))
                                ->visible(fn (callable $get) => (bool) $get('schedule_enabled')),
                        ]),
                ])
                    ->submitAction(new HtmlString('<button type="submit" class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 fi-ac-btn-action inline-flex" style="--c-600:var(--primary-600);--c-500:var(--primary-500);--c-400:var(--primary-400);">Generate Report</button>')),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $input = $this->form->getState();

        /** @var User|null $user */
        $user = Auth::user();
        $schoolId = session('current_tenant')->id ?? ($user ? $user->school_id : null);

        if (! $schoolId) {
            Notification::make()
                ->title(__('Tenant Scoping Error'))
                ->danger()
                ->body('Could not establish institutional boundaries.')
                ->send();

            return;
        }

        if (empty($input['datasets'])) {
            Notification::make()
                ->title(__('Missing Data Source'))
                ->danger()
                ->body('Please select at least one reporting data source.')
                ->send();

            return;
        }

        try {
            $template = $this->saveTemplate($input, $schoolId);
            $this->saveSchedule($input, $schoolId, $template);

            $report = app(ReportExecutionService::class)->execute(
                $template,
                $input['output_format'] ?? 'pdf',
                [],
                Auth::id()
            );

            if ($report->status === 'completed') {
                Notification::make()
                    ->title(__('Report Generated'))
                    ->success()
                    ->body('Your enterprise report layout has been saved and compiled successfully.')
                    ->send();

                $this->redirect(GeneratedReportResource::getUrl('index'));
            } else {
                Notification::make()
                    ->title(__('Generation Run Failed'))
                    ->danger()
                    ->body($report->error_message ?? 'Check your server database connection states.')
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('Template Save Failed'))
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function saveTemplate(array $input, int $schoolId): EnterpriseReportTemplate
    {
        $existing = EnterpriseReportTemplate::where('school_id', $schoolId)
            ->where('name', $input['template_name'])
            ->first();

        $version = ($existing?->version ?? 0) + 1;

        $joins = [];
        foreach ($input['joins'] ?? [] as $edgeKey) {
            [$from, $to] = explode('::', (string) $edgeKey, 2);
            if ($from && $to) {
                $joins[] = ['from' => $from, 'to' => $to, 'type' => 'left'];
            }
        }

        return EnterpriseReportTemplate::updateOrCreate(
            ['school_id' => $schoolId, 'name' => $input['template_name']],
            [
                'module' => $this->primaryDatasetModule($input['datasets']),
                'report_type' => $input['datasets'][0],
                'report_category' => $this->primaryDatasetCategory($input['datasets']),
                'orientation' => $input['orientation'] ?? 'portrait',
                'sharing_scope' => $input['sharing_scope'] ?? 'private',
                'selected_fields' => $input['selected_fields'] ?? [],
                'layout_settings' => $input['layout_settings'] ?? [],
                'datasets' => $input['datasets'],
                'joins' => $joins,
                'filters' => array_values(array_filter($input['filters'] ?? [], fn ($f) => ! empty($f['key']))),
                'grouping' => $input['grouping'] ?? [],
                'calculations' => $input['calculations'] ?? [],
                'sorting' => $input['sorting'] ?? [],
                'visualizations' => $input['visualizations'] ?? [],
                'config_version' => 2,
                'version' => $version,
                'last_edited_by_id' => Auth::id(),
                'created_by_id' => Auth::id(),
            ]
        );
    }

    protected function saveSchedule(array $input, int $schoolId, EnterpriseReportTemplate $template): void
    {
        if (empty($input['schedule_enabled'])) {
            return;
        }

        ReportSchedule::updateOrCreate(
            [
                'school_id' => $schoolId,
                'name' => $input['schedule_name'] ?? "{$template->name} Schedule",
            ],
            [
                'enterprise_report_template_id' => $template->id,
                'frequency' => $input['schedule_frequency'] ?? 'monthly',
                'distribution_method' => $input['schedule_distribution'] ?? 'email',
                'output_format' => $input['output_format'] ?? 'pdf',
                'generate_on_demand' => true,
                'recipients' => array_values(array_filter($input['schedule_recipients'] ?? [])),
                'filter_overrides' => [],
                'is_active' => true,
                'next_run_at' => now()->addDay(),
            ]
        );
    }

    protected function primaryDatasetModule(array $datasets): string
    {
        $first = explode('.', $datasets[0])[0];

        return $first;
    }

    protected function primaryDatasetCategory(array $datasets): string
    {
        return explode('.', $datasets[0])[0];
    }

    protected function datasetOptions(DatasetRegistry $registry): array
    {
        $options = [];
        foreach ($registry->groupedForPicker() as $module => $datasets) {
            foreach ($datasets as $dataset) {
                $options[$dataset['key']] = "{$module} — {$dataset['label']}";
            }
        }

        return $options;
    }

    protected function fieldOptions(DatasetRegistry $registry, array $datasets, bool $numericOnly = false): array
    {
        $options = [];

        foreach (array_filter((array) $datasets) as $datasetKey) {
            $def = $registry->byKey($datasetKey);

            if (! $def) {
                continue;
            }

            foreach ($def['fields'] as $field) {
                if ($numericOnly && ! in_array($field['type'] ?? 'string', ['currency', 'decimal', 'integer', 'percent'], true)) {
                    continue;
                }

                $qualified = "{$datasetKey}.{$field['key']}";
                $options[$qualified] = "{$def['module']} · {$field['label']}";
            }
        }

        return $options;
    }

    protected function joinOptions(DatasetRegistry $registry, ?array $datasets): array
    {
        $options = [];
        $datasets = (array) ($datasets ?? []);

        foreach ($datasets as $datasetKey) {
            $def = $registry->byKey($datasetKey);

            foreach ($def['connections'] ?? [] as $connection) {
                if (! in_array($connection['to'], $datasets, true)) {
                    continue;
                }

                $toDef = $registry->byKey($connection['to']);
                $key = "{$datasetKey}::{$connection['to']}";
                $options[$key] = "{$def['label']} → {$toDef['label']}";
            }
        }

        return $options;
    }
}
