<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\GeneratedReportResource;
use App\Models\User;
use App\Services\ModuleVisibilityManager;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Modules\Reports\Models\EnterpriseReportTemplate;
use Modules\Reports\Models\ReportSchedule;
use Modules\Reports\Services\DatasetRegistry;
use Modules\Reports\Services\ReportExecutionService;
use Modules\Reports\Support\ReportPresetCatalogue;

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

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isModuleVisible('reports');
    }

    protected static ?string $title = 'Enterprise Report Designer & Generator';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.app.pages.report-generator-page';

    public ?array $data = [];

    /**
     * Name of the catalogue preset currently driving the form, or null when the
     * user is building a custom report.
     */
    public ?string $activePreset = null;

    /**
     * Category filter for the preset cards.
     */
    public string $presetCategory = 'all';

    public function mount(): void
    {
        $this->form->fill([
            'layout_settings' => ReportPresetCatalogue::layoutSettings(),
            'orientation' => 'portrait',
            'output_format' => 'pdf',
            'sharing_scope' => 'private',
            'schedule_enabled' => false,
        ]);
    }

    /**
     * Category tabs for the card grid, in catalogue order.
     *
     * @return array<string, string>
     */
    public function getPresetCategories(): array
    {
        $categories = [__('All Reports') => 'all'];

        foreach (ReportPresetCatalogue::categories() as $category) {
            $meta = ReportPresetCatalogue::categoryMeta()[$category] ?? null;
            $categories[$meta['label'] ?? $category] = $category;
        }

        return $categories;
    }

    /**
     * Presets for the current category, recommended ones first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPresets(): array
    {
        $presets = ReportPresetCatalogue::forPicker($this->presetCategory);

        usort($presets, function (array $a, array $b) {
            if ($a['recommended'] !== $b['recommended']) {
                return $a['recommended'] ? -1 : 1;
            }

            return strcmp($a['name'], $b['name']);
        });

        return $presets;
    }

    public function setPresetCategory(string $category): void
    {
        $this->presetCategory = $category;
    }

    /**
     * Load a catalogue preset into the form. One click replaces the whole
     * dataset, field, join, filter, calculation, sort and chart definition.
     */
    public function applyPreset(string $name): void
    {
        $preset = ReportPresetCatalogue::findByName($name);

        if (! $preset) {
            return;
        }

        $this->activePreset = $name;

        $this->form->fill(array_merge(
            $this->presetToFormState($preset),
            [
                'template_name' => $preset['name'],
                'orientation' => $preset['orientation'] ?? 'portrait',
                'output_format' => 'pdf',
                'sharing_scope' => 'private',
                'schedule_enabled' => false,
                'layout_settings' => ReportPresetCatalogue::layoutSettings(),
            ]
        ));

        Notification::make()
            ->title(__('Preset loaded'))
            ->success()
            ->body(__(':name is ready. Press Generate report, or edit the columns below.', ['name' => $name]))
            ->send();
    }

    /**
     * Start a blank custom report.
     */
    public function startCustomReport(): void
    {
        $this->activePreset = null;

        $this->form->fill([
            'template_name' => '',
            'datasets' => [],
            'joins' => [],
            'selected_fields' => [],
            'filters' => [],
            'grouping' => [],
            'calculations' => [],
            'sorting' => [],
            'visualizations' => [],
            'orientation' => 'portrait',
            'output_format' => 'pdf',
            'sharing_scope' => 'private',
            'schedule_enabled' => false,
            'layout_settings' => ReportPresetCatalogue::layoutSettings(),
        ]);
    }

    /**
     * Convert a catalogue preset into this form's state shape.
     *
     * The catalogue stores engine config (join objects, calculations keyed by a
     * single qualified field). The form wants CheckboxList edge keys and
     * separate dataset/field parts, so the two are translated here.
     *
     * @param  array<string, mixed>  $preset
     * @return array<string, mixed>
     */
    protected function presetToFormState(array $preset): array
    {
        $joinKeys = [];

        foreach ($preset['joins'] ?? [] as $join) {
            $from = $join['from'] ?? null;
            $to = $join['to'] ?? null;

            if ($from && $to) {
                $joinKeys[] = "{$from}::{$to}";
            }
        }

        $calculations = [];

        foreach ($preset['calculations'] ?? [] as $calc) {
            [$dataset, $field] = $this->splitQualifiedKey($calc['field'] ?? '');

            if (! $dataset || ! $field) {
                continue;
            }

            $calculations[] = [
                'type' => $calc['type'] ?? 'sum',
                'dataset' => $dataset,
                'field' => $field,
                'alias' => $calc['alias'] ?? null,
            ];
        }

        $sorting = [];

        foreach ($preset['sorting'] ?? [] as $sort) {
            $dataset = $sort['dataset'] ?? $this->datasetOf($sort['field'] ?? '');
            $field = $sort['field'] ?? '';

            if (! $dataset || ! $field) {
                continue;
            }

            $sorting[] = [
                'dataset' => $dataset,
                'field' => $field,
                'direction' => $sort['direction'] ?? 'asc',
            ];
        }

        return [
            'datasets' => $preset['datasets'] ?? [],
            'joins' => $joinKeys,
            'selected_fields' => $preset['selected_fields'] ?? [],
            'filters' => $preset['filters'] ?? [],
            'grouping' => $preset['grouping'] ?? [],
            'calculations' => $calculations,
            'sorting' => $sorting,
            'visualizations' => $preset['visualizations'] ?? [],
        ];
    }

    /**
     * Split `dataset.key` into its two parts, or [null, null] when unqualified.
     *
     * @return array{0: ?string, 1: ?string}
     */
    protected function splitQualifiedKey(?string $key): array
    {
        $key = (string) $key;

        if (! str_contains($key, '.')) {
            return [null, null];
        }

        [$dataset, $field] = explode('.', $key, 2);

        return [$dataset ?: null, $field ?: null];
    }

    protected function datasetOf(?string $key): ?string
    {
        return $this->splitQualifiedKey($key)[0];
    }

    public function form(Form $form): Form
    {
        $registry = app(DatasetRegistry::class);

        return $form
            ->schema([
                Section::make(__('Report'))
                    ->schema([
                        TextInput::make('template_name')
                            ->label(__('Report Name'))
                            ->placeholder(__('e.g., Q2 Outstanding Defaulters List'))
                            ->required()
                            ->maxLength(191)
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

                        Radio::make('output_format')
                            ->label(__('Output Format'))
                            ->options([
                                'pdf' => __('PDF document'),
                                'csv' => __('CSV spreadsheet'),
                                'xls' => __('Excel workbook (.xls)'),
                                'json' => __('JSON data'),
                                'print' => __('Print view'),
                                'copy' => __('Clipboard (HTML)'),
                            ])
                            ->descriptions([
                                'pdf' => __('Formatted and signed, ready to file'),
                                'csv' => __('Raw rows for Excel or Sheets'),
                                'xls' => __('Native workbook for further editing'),
                                'json' => __('For integrations and dashboards'),
                                'print' => __('Opens the browser print dialog'),
                                'copy' => __('Rich text for email and documents'),
                            ])
                            ->default('pdf')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make(__('Data'))
                    ->description(__('Pick a source, then choose the columns to show.'))
                    ->schema([
                        Select::make('datasets')
                            ->label(__('Data Source'))
                            ->options($this->datasetOptions($registry))
                            ->multiple()
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($set, $get, $state) {
                                // Prune fields whose source dataset was just
                                // deselected, and rebuild the join graph.
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
                            })
                            ->helperText(__('Choose the closest source. Related modules can be joined from Advanced options.')),

                        CheckboxList::make('selected_fields')
                            ->label(__('Columns'))
                            ->helperText(__('Fields are labelled with their source dataset.'))
                            ->options(fn (callable $get) => $this->fieldOptions($registry, $get('datasets')))
                            ->columns(2)
                            ->searchable()
                            ->reactive()
                            ->required(),
                    ]),

                Section::make(__('Advanced options'))
                    ->description(__('Joins, filters, totals, charts, branding and scheduling.'))
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        CheckboxList::make('joins')
                            ->label(__('Joins'))
                            ->helperText(__('Link additional sources. Only options related to your selected sources are shown.'))
                            ->options(fn (callable $get) => $this->joinOptions($registry, $get('datasets')))
                            ->columns(2)
                            ->searchable()
                            ->visible(fn (callable $get) => count((array) $get('datasets')) > 1),

                        Repeater::make('filters')
                            ->label(__('Filters'))
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
                                    ->searchable()
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

                        CheckboxList::make('grouping')
                            ->label(__('Group Rows By'))
                            ->options(fn (callable $get) => $this->fieldOptions($registry, $get('datasets')))
                            ->columns(2),

                        Repeater::make('calculations')
                            ->label(__('Totals'))
                            ->helperText(__('Adds a calculated column to the output.'))
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
                                    ->searchable()
                                    ->reactive()
                                    ->required(),
                                TextInput::make('alias')
                                    ->label(__('Label'))
                                    ->placeholder(__('e.g. total_outstanding')),
                            ])
                            ->defaultItems(0)
                            ->collapsible()
                            ->grid(3),

                        Repeater::make('sorting')
                            ->label(__('Sort Order'))
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
                                    ->searchable()
                                    ->reactive()
                                    ->required(),
                                Select::make('direction')
                                    ->label(__('Direction'))
                                    ->options(['asc' => 'Ascending', 'desc' => 'Descending'])
                                    ->default('asc'),
                            ])
                            ->defaultItems(0)
                            ->collapsible()
                            ->grid(3),

                        Repeater::make('visualizations')
                            ->label(__('Charts'))
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
                                    ->searchable()
                                    ->reactive(),
                                Repeater::make('series')
                                    ->label(__('Data Series'))
                                    ->schema([
                                        Select::make('field')
                                            ->label(__('Value Field'))
                                            ->options(fn (callable $get) => $this->fieldOptions($registry, $get('../../../../datasets')))
                                            ->searchable()
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

                        Grid::make(2)
                            ->schema([
                                Radio::make('orientation')
                                    ->label(__('Page Orientation'))
                                    ->options([
                                        'portrait' => __('Portrait'),
                                        'landscape' => __('Landscape'),
                                    ])
                                    ->default('portrait')
                                    ->required(),

                                Select::make('sharing_scope')
                                    ->label(__('Sharing Scope'))
                                    ->options([
                                        'private' => __('Private (only me)'),
                                        'department' => __('Department'),
                                        'school' => __('Whole school'),
                                    ])
                                    ->default('private')
                                    ->required(),
                            ]),

                        ColorPicker::make('layout_settings.primary_color')
                            ->label(__('Accent Color'))
                            ->default('#15803d'),

                        TextInput::make('layout_settings.header_text')
                            ->label(__('Header Text')),

                        TextInput::make('layout_settings.footer_text')
                            ->label(__('Footer Text')),

                        Toggle::make('layout_settings.show_logo')
                            ->label(__('Show Institution Logo'))
                            ->default(true),

                        Toggle::make('layout_settings.show_signature_block')
                            ->label(__('Show Signature Block'))
                            ->default(true),

                        Toggle::make('schedule_enabled')
                            ->label(__('Schedule This Report'))
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
                            ->label(__('Distribution'))
                            ->options([
                                'email' => 'Email recipients',
                                'notification' => 'In-app notification',
                                'both' => 'Both',
                            ])
                            ->default('email')
                            ->visible(fn (callable $get) => (bool) $get('schedule_enabled')),

                        TagsInput::make('schedule_recipients')
                            ->label(__('Recipient Emails'))
                            ->placeholder(__('type email and press enter'))
                            ->visible(fn (callable $get) => (bool) $get('schedule_enabled')),
                    ]),
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
                ->body(__('Could not establish institutional boundaries.'))
                ->send();

            return;
        }

        if (empty($input['datasets'])) {
            Notification::make()
                ->title(__('Missing Data Source'))
                ->danger()
                ->body(__('Please select at least one reporting data source.'))
                ->send();

            return;
        }

        $config = $this->configFromInput($input);
        $service = app(ReportExecutionService::class);

        // Prove the query runs before anything is written. A saved-but-broken
        // template is worse than no template: it clutters the library, re-fails
        // on every manual run, and its schedule keeps firing against a config
        // that can never succeed.
        try {
            $probe = $service->validate($config);
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('Report Configuration Problem'))
                ->danger()
                ->body($e->getMessage())
                ->persistent()
                ->send();

            return;
        }

        if (! empty($probe['warnings'])) {
            Notification::make()
                ->title(__('Report Configuration Adjusted'))
                ->warning()
                ->body(implode(' ', $probe['warnings']))
                ->persistent()
                ->send();
        }

        // Render against a transient, unsaved template. The artifact is produced
        // from exactly the config that was proven above, and the template row is
        // only written once that artifact exists. A failure here leaves the
        // library untouched instead of dropping in a template that can never run.
        $transient = $this->makeTemplate($input, $schoolId, $probe['config'], false);

        try {
            $report = $service->render(
                $transient,
                $input['output_format'] ?? 'pdf',
                [],
                Auth::id()
            );
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('Generation Failed'))
                ->danger()
                ->body($e->getMessage())
                ->persistent()
                ->send();

            return;
        }

        if ($report->status !== 'completed') {
            Notification::make()
                ->title(__('Generation Run Failed'))
                ->danger()
                ->body($report->error_message ?? __('Check your server database connection states.'))
                ->persistent()
                ->send();

            return;
        }

        // The run succeeded, so the template is now worth keeping.
        try {
            $template = $this->makeTemplate($input, $schoolId, $probe['config'], true);

            // The artifact was produced before this row existed, so stamp the
            // run metadata here rather than relying on execute() to do it.
            $template->update([
                'last_run_at' => now(),
                'config_version' => 2,
            ]);

            $report->update(['enterprise_report_template_id' => $template->id]);

            $this->saveSchedule($input, $schoolId, $template);
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('Template Save Failed'))
                ->danger()
                ->body(__('Your report was generated but could not be added to the template library.').' '.$e->getMessage())
                ->persistent()
                ->send();

            $this->redirect(GeneratedReportResource::getUrl('index'));

            return;
        }

        Notification::make()
            ->title(__('Report Generated'))
            ->success()
            ->body(__('Your report layout has been saved and compiled successfully.'))
            ->send();

        $this->redirect(GeneratedReportResource::getUrl('index'));
    }

    /**
     * Turn the form state into an engine config, independent of the UI shape.
     *
     * Shared by the pre-flight probe and the persisted template so the config
     * that was proven to run is exactly the config that gets saved.
     */
    protected function configFromInput(array $input): array
    {
        $joins = [];

        foreach ($input['joins'] ?? [] as $edgeKey) {
            [$from, $to] = explode('::', (string) $edgeKey, 2);

            if ($from && $to) {
                $joins[] = ['from' => $from, 'to' => $to, 'type' => 'left'];
            }
        }

        return [
            'config_version' => 2,
            'datasets' => $input['datasets'] ?? [],
            'joins' => $joins,
            'selected_fields' => $input['selected_fields'] ?? [],
            'filters' => array_values(array_filter($input['filters'] ?? [], fn ($f) => ! empty($f['key']))),
            'grouping' => $input['grouping'] ?? [],
            'calculations' => $input['calculations'] ?? [],
            'sorting' => $input['sorting'] ?? [],
            'visualizations' => $input['visualizations'] ?? [],
        ];
    }

    /**
     * Build the template record from the *repaired* config, so a saved template
     * never carries fields or datasets the planner had to drop.
     *
     * With `$persist = false` the model is returned hydrated but unsaved, which
     * is what lets the generator render an artifact and only write the template
     * once that artifact exists.
     *
     * @param  array<string, mixed>  $config
     */
    protected function makeTemplate(array $input, int $schoolId, array $config, bool $persist = true): EnterpriseReportTemplate
    {
        $existing = EnterpriseReportTemplate::where('school_id', $schoolId)
            ->where('name', $input['template_name'])
            ->first();

        $datasets = $config['datasets'];

        $attributes = [
            'school_id' => $schoolId,
            'module' => $this->primaryDatasetModule($datasets),
            'report_type' => $datasets[0] ?? '',
            'report_category' => $this->primaryDatasetCategory($datasets),
            'orientation' => $input['orientation'] ?? 'portrait',
            'sharing_scope' => $input['sharing_scope'] ?? 'private',
            'selected_fields' => $config['selected_fields'],
            'layout_settings' => $input['layout_settings'] ?? [],
            'datasets' => $datasets,
            'joins' => array_map(
                fn (array $edge) => ['from' => $edge['from'], 'to' => $edge['to'], 'type' => $edge['type'] ?? 'left'],
                $config['joins']
            ),
            'filters' => $config['filters'],
            'grouping' => $config['grouping'],
            'calculations' => $config['calculations'],
            'sorting' => $config['sorting'],
            'visualizations' => $config['visualizations'],
            'config_version' => 2,
            'version' => ($existing?->version ?? 0) + 1,
            'last_edited_by_id' => Auth::id(),
            'created_by_id' => Auth::id(),
        ];

        if (! $persist) {
            $template = new EnterpriseReportTemplate(array_merge($attributes, [
                'name' => $input['template_name'],
            ]));

            // `school` is read by the exporters, so resolve it now rather than
            // letting an unsaved model resolve it later.
            $template->setRelation('school', $existing?->school ?? \App\Models\School::find($schoolId));

            return $template;
        }

        return EnterpriseReportTemplate::updateOrCreate(
            ['school_id' => $schoolId, 'name' => $input['template_name']],
            $attributes
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
                $options[$dataset['key']] = __("{$module} — {$dataset['label']}");
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
