<?php

namespace Modules\Reports\Services;

use Modules\Admin\Services\AuditLogger;
use Modules\Reports\Models\EnterpriseReportTemplate;
use Modules\Reports\Models\GeneratedReport;

/**
 * End-to-end report execution: normalize config → build query → run →
 * compute summary → render artifact → persist GeneratedReport + stats.
 *
 * {@see render()} does the work without touching the template record;
 * {@see execute()} wraps it and stamps the template once the run succeeds.
 */
class ReportExecutionService
{
    public function __construct(
        protected LegacyAdapter $adapter,
        protected ReportQueryBuilder $builder,
        protected ExportService $exporter,
        protected ReportAuditService $auditor,
    ) {}

    public function execute(
        EnterpriseReportTemplate $template,
        string $format = 'pdf',
        array $runtimeFilters = [],
        ?int $userId = null,
        array $options = []
    ): GeneratedReport {
        $report = $this->render($template, $format, $runtimeFilters, $userId, $options);

        // Only stamp a template that is already stored. A caller rendering a
        // transient template (the generator validates before it persists) must
        // not have `update()` silently INSERT it behind its back.
        if ($report->status === 'completed' && $template->exists) {
            $template->update([
                'last_run_at' => now(),
                'config_version' => 2,
            ]);
        }

        return $report;
    }

    /**
     * Run a report and persist the GeneratedReport + artifact, without touching
     * the template record itself.
     *
     * Separated from {@see execute()} so the generator can prove a config
     * produces an artifact *before* the template is written. Saving first and
     * executing second leaves a broken template in the library whenever the
     * run fails, and its schedule then keeps firing against a config that can
     * never succeed.
     *
     * @param  array<string, mixed>  $options
     */
    public function render(
        EnterpriseReportTemplate $template,
        string $format = 'pdf',
        array $runtimeFilters = [],
        ?int $userId = null,
        array $options = []
    ): GeneratedReport {
        $started = hrtime(true);

        $report = GeneratedReport::create([
            'school_id' => $template->school_id,
            'enterprise_report_template_id' => $template->id,
            'name' => "{$template->name} - ".now()->format('Y-m-d His'),
            'format' => $format,
            'status' => 'processing',
            'generated_by_id' => $userId,
            'record_count' => 0,
        ]);

        try {
            $config = $this->adapter->normalize($template);
            $runtimeFilters = $this->normalizeRuntimeFilters($config, $runtimeFilters);

            // Normalise up front so columns, headings and totals are derived from
            // the config the query was actually built from. Repairing inside
            // build() alone would leave the export asking for aliases that the
            // planner had dropped or renamed.
            $config = $this->builder->normalize($config, $runtimeFilters);

            $rows = $this->builder->build($config)->get();

            $columns = $this->outputColumns($config);
            $headings = $this->resolveHeadings($config, $columns);
            $summary = $this->computeSummary($config, $rows);

            $path = $this->exporter->store($format, $rows, $columns, $headings, array_merge([
                'school_id' => $template->school_id,
                'school' => $template->school ?? null,
                'title' => $template->name,
                'settings' => $template->layout_settings ?? [],
                'orientation' => $template->orientation ?? 'portrait',
                'summary' => $summary,
                'filters_summary' => $this->filtersSummary($config['filters'] ?? [], $runtimeFilters),
            ], $options));

            $executionMs = max(1, (int) round((hrtime(true) - $started) / 1e6));

            $report->update([
                'status' => 'completed',
                'file_path' => $path,
                'record_count' => count($rows),
                'execution_ms' => $executionMs,
                'data_checksum' => $this->auditor->checksum($rows),
                'summary' => $summary,
                // Only the run-specific overrides. The template's own filters are
                // already part of its saved config, and recording them here too
                // would make ReportAuditService::verify() re-apply them a second
                // time when it replays the run.
                'filters_used' => $runtimeFilters,
            ]);

            AuditLogger::log(
                'Generate Report',
                'Reports & Intelligence',
                null,
                [
                    'template' => $template->name,
                    'format' => $format,
                    'record_count' => count($rows),
                    'execution_ms' => $executionMs,
                    'checksum' => $report->data_checksum,
                ],
                'success',
            );
        } catch (\Throwable $e) {
            $report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'execution_ms' => max(1, (int) round((hrtime(true) - $started) / 1e6)),
            ]);

            AuditLogger::log(
                'Generate Report',
                'Reports & Intelligence',
                null,
                ['template' => $template->name, 'error' => $e->getMessage()],
                'failure',
            );
        }

        return $report;
    }

    /**
     * Dry-run a config before anything is persisted.
     *
     * A template is only useful if it can run, so the generator must be able to
     * prove a config executes before it is written. Only `SELECT` is issued and
     * one row is fetched, so this costs a single cheap round trip.
     *
     * Returns the repaired config plus the planner's notes, so the caller can
     * both show the user what was adjusted and save the corrected config rather
     * than the one that was submitted.
     *
     * @return array{config: array, warnings: array<int, string>}
     */
    public function validate(array $config, array $runtimeFilters = []): array
    {
        $normalized = $this->builder->normalize($config, $runtimeFilters);

        $this->builder->build($normalized)->limit(1)->get();

        return [
            'config' => $normalized,
            'warnings' => $this->builder->warnings(),
        ];
    }

    /**
     * The columns the SELECT list actually produced, in order.
     *
     * These are the SQL aliases — not the qualified field keys — because that is
     * what the rows are keyed by. The planner uniquifies colliding names (two
     * datasets with a `status` column, or a `SUM(total_amount)` alongside the
     * raw `total_amount`), so the qualified key is not a usable row accessor.
     *
     * @return array<int, string>
     */
    protected function outputColumns(array $config): array
    {
        $aliases = $config['output_aliases'] ?? [];
        $columns = [];

        foreach ($config['selected_fields'] ?? [] as $key) {
            $columns[] = $aliases[$key] ?? $this->builder->qualifiedFieldKey($key);
        }

        // Aggregates are extra SELECT columns appended after the fields.
        foreach ($config['calculations'] ?? [] as $calc) {
            $columns[] = $calc['output_alias'] ?? $calc['alias'] ?? 'total';
        }

        return array_values(array_unique($columns));
    }

    /**
     * Map each output column (a SQL alias) to its human label.
     */
    protected function resolveHeadings(array $config, array $columns): array
    {
        $labels = [];

        foreach ($config['datasets'] ?? [] as $datasetKey) {
            foreach ($this->builder->requireDataset($datasetKey)['fields'] as $field) {
                $labels["{$datasetKey}.{$field['key']}"] = $field['label'];
            }
        }

        $aliases = $config['output_aliases'] ?? [];
        $result = [];

        foreach ($columns as $column) {
            // A column arrives as a SQL alias, which may be a uniquified variant
            // of the field key rather than the field key itself.
            $key = isset($labels[$column])
                ? $column
                : ($aliases[$column] ?? $column);

            $result[$column] = $labels[$key] ?? ucfirst(str_replace('_', ' ', $column));
        }

        return $result;
    }

    /**
     * Totals for numeric/currency columns to pin at the top of print artifacts.
     */
    protected function computeSummary(array $config, $rows): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $summary = ['Records' => number_format($rows->count())];
        $labels = $this->resolveHeadings($config, $this->outputColumns($config));

        foreach ($config['selected_fields'] as $key) {
            $field = $this->builder->fieldInfo($key);

            if (! $field || ! in_array($field['type'] ?? 'string', ['currency', 'decimal', 'integer', 'percent'], true)) {
                continue;
            }

            // Must resolve through the alias map exactly like outputColumns().
            // A colliding field (two datasets each with `amount`) is uniquified by
            // the planner, so the qualified key is not a usable row accessor.
            $column = $config['output_aliases'][$key] ?? $this->builder->qualifiedFieldKey($key);
            $total = (float) $rows->sum(fn ($row) => (float) ($row->{$column} ?? 0));

            $summary[$labels[$column] ?? ucfirst(str_replace('_', ' ', $column)).' Total'] = match ($field['type'] ?? null) {
                'currency' => number_format($total, 2),
                'percent' => number_format($total, 2).'%',
                'decimal' => number_format($total, 2),
                default => number_format($total),
            };
        }

        return $summary;
    }

    /**
     * Accept both new-style filters (`dataset/key/op/value`) and legacy simple
     * overrides (`fieldKey => value`) and normalise them against the config.
     */
    protected function normalizeRuntimeFilters(array $config, array $runtimeFilters): array
    {
        if (empty($runtimeFilters)) {
            return [];
        }

        // Already new-style list of filter rows.
        if (isset($runtimeFilters[0]) && is_array($runtimeFilters[0]) && isset($runtimeFilters[0]['key'])) {
            return $runtimeFilters;
        }

        // Legacy flat map: fieldKey => value.
        $normalized = [];

        foreach ($runtimeFilters as $fieldKey => $value) {
            if ($value === '' || $value === null) {
                continue;
            }

            if ($this->isQualifiedKey($fieldKey)) {
                $normalized[] = [
                    'dataset' => (string) $this->builder->datasetOf($fieldKey),
                    'key' => $this->builder->qualifiedFieldKey($fieldKey),
                    'op' => is_array($value) ? 'in' : 'eq',
                    'value' => $value,
                ];

                continue;
            }

            foreach ($config['datasets'] ?? [] as $datasetKey) {
                $field = $this->builder->fieldInfo("{$datasetKey}.{$fieldKey}");

                if (! $field) {
                    continue;
                }

                $normalized[] = [
                    'dataset' => $datasetKey,
                    'key' => $fieldKey,
                    'op' => is_array($value) ? 'in' : 'eq',
                    'value' => $value,
                ];
                break;
            }
        }

        return $normalized;
    }

    protected function isQualifiedKey(string $key): bool
    {
        return $this->builder->fieldInfo($key) !== null;
    }

    protected function filtersSummary(array $configFilters, array $runtimeFilters): ?string
    {
        $all = array_merge($configFilters, $runtimeFilters);
        if (empty($all)) {
            return null;
        }

        $parts = [];
        foreach ($all as $filter) {
            $key = $filter['key'] ?? '';
            $op = $filter['op'] ?? 'eq';
            $value = $filter['value'] ?? '';
            $value = is_array($value) ? implode(', ', $value) : $value;
            $parts[] = "{$key} {$op} {$value}";
        }

        return implode('; ', $parts);
    }
}
