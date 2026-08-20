<?php

namespace Modules\Reports\Services;

use Modules\Admin\Services\AuditLogger;
use Modules\Reports\Models\EnterpriseReportTemplate;
use Modules\Reports\Models\GeneratedReport;

/**
 * End-to-end report execution: normalize config → build query → run →
 * compute summary → render artifact → persist GeneratedReport + stats.
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
            $rows = $this->builder->build($config, $runtimeFilters)->get();

            $columns = $config['selected_fields'];
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
                'filters_used' => array_merge($config['filters'] ?? [], $runtimeFilters),
            ]);

            $template->update([
                'last_run_at' => now(),
                'config_version' => 2,
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
     * Map fully-qualified field keys to their human labels for output headers.
     */
    protected function resolveHeadings(array $config, array $columns): array
    {
        $headings = [];

        foreach ($config['datasets'] ?? [] as $datasetKey) {
            foreach ($this->builder->requireDataset($datasetKey)['fields'] as $field) {
                $headings["{$datasetKey}.{$field['key']}"] = $field['label'];
            }
        }

        $result = [];
        foreach ($columns as $key) {
            $result[$key] = $headings[$key] ?? ucfirst(str_replace('_', ' ', $key));
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
        $labels = $this->resolveHeadings($config, $config['selected_fields']);

        foreach ($config['selected_fields'] as $key) {
            $field = $this->builder->fieldInfo($key);

            if (! $field || ! in_array($field['type'] ?? 'string', ['currency', 'decimal', 'integer', 'percent'], true)) {
                continue;
            }

            $column = $this->builder->qualifiedFieldKey($key);
            $total = (float) $rows->sum(fn ($row) => (float) ($row->{$column} ?? 0));

            $summary[$labels[$key].' Total'] = match ($field['type'] ?? null) {
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
