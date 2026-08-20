<?php

namespace Modules\Reports\Services;

/**
 * Transforms executed report rows into chart-ready payloads for the dashboard
 * builder and analytics explorer. Output is deliberately framework-agnostic
 * (labels + datasets) so any charting frontend (Chart.js, ApexCharts, DOM) can
 * consume it directly.
 */
class VisualizationService
{
    public function __construct(protected ReportQueryBuilder $builder) {}

    /**
     * Build chart payloads from a config's visualization definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function build(array $config, $rows): array
    {
        $charts = [];

        foreach ($config['visualizations'] ?? [] as $i => $viz) {
            $type = strtolower($viz['type'] ?? 'bar');
            $labelField = $viz['label'] ?? null;
            $series = $viz['series'] ?? [];

            if (! in_array($type, ['bar', 'line', 'pie', 'doughnut', 'polarArea', 'radar'], true)) {
                continue;
            }

            $labels = [];
            $datasets = [];

            foreach ($series as $s) {
                $field = $this->builder->qualifiedFieldKey($s['field'] ?? '');
                $values = [];

                foreach ($rows as $row) {
                    if ($labelField && $i === 0) {
                        $labels[] = (string) ($row->{$this->builder->qualifiedFieldKey($labelField)} ?? '');
                    }

                    $values[] = (float) ($row->{$field} ?? 0);
                }

                $datasets[] = [
                    'label' => $s['label'] ?? $field,
                    'data' => $values,
                    'backgroundColor' => $s['color'] ?? $this->palette(count($labels), $i),
                    'borderColor' => $s['color'] ?? $this->palette(count($labels), $i),
                ];
            }

            $charts[] = [
                'type' => $type,
                'title' => $viz['title'] ?? 'Visualization',
                'labels' => array_values(array_unique($labels)),
                'datasets' => $datasets,
            ];
        }

        return $charts;
    }

    /**
     * Compute KPI widgets (dashboards): single scalar per field/aggregate.
     */
    public function kpi(array $config, $rows, string $fieldKey, string $aggregate = 'count'): array
    {
        $field = $this->builder->fieldInfo($fieldKey);
        $column = $this->builder->qualifiedFieldKey($fieldKey);

        $numeric = in_array($field['type'] ?? 'string', ['currency', 'decimal', 'integer', 'percent'], true);

        $value = match (strtolower($aggregate)) {
            'count' => $rows->count(),
            'sum' => (float) $rows->sum(fn ($row) => (float) ($row->{$column} ?? 0)),
            'average', 'avg' => $rows->isEmpty() ? 0.0 : (float) $rows->avg(fn ($row) => (float) ($row->{$column} ?? 0)),
            'min' => $rows->isEmpty() ? 0.0 : (float) $rows->min(fn ($row) => (float) ($row->{$column} ?? 0)),
            'max' => $rows->isEmpty() ? 0.0 : (float) $rows->max(fn ($row) => (float) ($row->{$column} ?? 0)),
            default => $rows->count(),
        };

        return [
            'label' => $field['label'] ?? ucfirst(str_replace('_', ' ', $fieldKey)),
            'value' => $this->format($value, $field['type'] ?? 'string', $numeric),
            'aggregate' => $aggregate,
        ];
    }

    protected function format(float $value, string $type, bool $numeric): string
    {
        if (! $numeric) {
            return number_format($value);
        }

        return match ($type) {
            'currency' => number_format($value, 2),
            'percent' => number_format($value, 2).'%',
            'decimal' => number_format($value, 2),
            default => number_format($value),
        };
    }

    protected function palette(int $count, int $offset = 0): array
    {
        $base = ['#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#0ea5e9', '#a855f7', '#14b8a6', '#f97316', '#e11d48', '#84cc16'];

        $colors = [];
        for ($i = 0; $i < max($count, 1); $i++) {
            $colors[] = $base[($i + $offset) % count($base)];
        }

        return $colors;
    }
}
