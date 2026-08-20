<?php

namespace Modules\Reports\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Renders report rows to physical artifacts. Supported targets:
 *  - pdf   → print-ready layout via DomPDF (engine template)
 *  - csv   → RFC-compliant flat CSV
 *  - xls   → Excel-compatible HTML workbook (no external binary required)
 *  - json  → structured JSON payload
 *  - print → HTML document for the browser print dialog
 *  - copy  → tab-separated text suitable for clipboard paste
 */
class ExportService
{
    /**
     * @param  array<int, object>  $rows
     * @param  array<int, string>  $columns  output column keys
     * @param  array<string, string>  $headings  column key → label
     */
    public function export(
        string $format,
        iterable $rows,
        array $columns,
        array $headings = [],
        array $options = []
    ): array {
        $format = strtolower($format);

        return match ($format) {
            'pdf' => $this->toPdf($rows, $columns, $headings, $options),
            'csv' => $this->toCsv($rows, $columns, $headings),
            'xls' => $this->toXls($rows, $columns, $headings),
            'json' => $this->toJson($rows, $columns),
            'print' => $this->toPrintHtml($rows, $columns, $headings, $options),
            'copy' => $this->toCopy($rows, $columns, $headings),
            default => throw new \InvalidArgumentException("Unsupported export format [{$format}]."),
        };
    }

    /**
     * Persist an exported artifact to the public disk and return its storage path.
     */
    public function store(string $format, iterable $rows, array $columns, array $headings = [], array $options = []): string
    {
        $payload = $this->export($format, $rows, $columns, $headings, $options);

        $extension = $format === 'print' ? 'html' : ($format === 'copy' ? 'tsv' : $format);
        $schoolId = $options['school_id'] ?? 0;
        $fileName = "reports/{$schoolId}/".date('Ymd_His').'_'.Str::lower(Str::random(6)).".{$extension}";

        Storage::disk('public')->put($fileName, $payload['content']);

        return $fileName;
    }

    protected function toPdf(iterable $rows, array $columns, array $headings, array $options): array
    {
        $settings = $options['settings'] ?? [];
        $primaryColor = $settings['primary_color'] ?? '#15803d';

        $pdf = Pdf::loadView('modules.reports.engine-pdf-template', [
            'school' => $options['school'] ?? null,
            'title' => $options['title'] ?? 'Enterprise Report',
            'rows' => $rows,
            'columns' => $columns,
            'headings' => $headings,
            'settings' => $settings,
            'summary' => $options['summary'] ?? [],
            'filtersSummary' => $options['filters_summary'] ?? null,
            'recordCount' => count($rows),
            'primaryColor' => $primaryColor,
        ]);

        $pdf->setPaper('a4', ($options['orientation'] ?? 'portrait') === 'landscape' ? 'landscape' : 'portrait');

        return ['content' => $pdf->output(), 'mime' => 'application/pdf'];
    }

    protected function toCsv(iterable $rows, array $columns, array $headings): array
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, $this->headingValues($columns, $headings), ',', '"', '\\');

        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $key) {
                $line[] = $this->rowValue($row, $key);
            }
            fputcsv($handle, $line, ',', '"', '\\');
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return ['content' => $content, 'mime' => 'text/csv'];
    }

    protected function toXls(iterable $rows, array $columns, array $headings): array
    {
        $escape = fn ($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES);

        $html = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        $html .= '<head><meta charset="utf-8"><style>td{mso-number-format:"\@";}</style></head><body><table>';
        $html .= '<tr>'.implode('', array_map(fn ($h) => "<th>{$escape($h)}</th>", $this->headingValues($columns, $headings))).'</tr>';

        foreach ($rows as $row) {
            $cells = [];
            foreach ($columns as $key) {
                $cells[] = "<td>{$escape($this->rowValue($row, $key))}</td>";
            }
            $html .= '<tr>'.implode('', $cells).'</tr>';
        }

        $html .= '</table></body></html>';

        return ['content' => $html, 'mime' => 'application/vnd.ms-excel'];
    }

    protected function toJson(iterable $rows, array $columns): array
    {
        $payload = [];
        foreach ($rows as $row) {
            $entry = [];
            foreach ($columns as $key) {
                $entry[$key] = $this->rowValue($row, $key);
            }
            $payload[] = $entry;
        }

        return ['content' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 'mime' => 'application/json'];
    }

    protected function toPrintHtml(iterable $rows, array $columns, array $headings, array $options): array
    {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>'.($options['title'] ?? 'Report').'</title></head><body>';
        $html .= '<h2 style="text-align:center;">'.e($options['title'] ?? 'Enterprise Report').'</h2>';
        $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:12px;">';
        $html .= '<thead><tr>'.implode('', array_map(fn ($h) => "<th style='background:#f1f5f9;'>".e($h).'</th>', $this->headingValues($columns, $headings))).'</tr></thead><tbody>';

        foreach ($rows as $row) {
            $cells = [];
            foreach ($columns as $key) {
                $cells[] = '<td>'.e((string) ($this->rowValue($row, $key) ?? '-')).'</td>';
            }
            $html .= '<tr>'.implode('', $cells).'</tr>';
        }

        $html .= '</tbody></table></body></html>';

        return ['content' => $html, 'mime' => 'text/html'];
    }

    protected function toCopy(iterable $rows, array $columns, array $headings): array
    {
        $lines = [implode("\t", $this->headingValues($columns, $headings))];

        foreach ($rows as $row) {
            $cell = [];
            foreach ($columns as $key) {
                $cell[] = (string) ($this->rowValue($row, $key) ?? '');
            }
            $lines[] = implode("\t", $cell);
        }

        return ['content' => implode("\n", $lines), 'mime' => 'text/plain'];
    }

    protected function headingValues(array $columns, array $headings): array
    {
        return array_map(fn ($key) => $headings[$key] ?? ucfirst(str_replace('_', ' ', $key)), $columns);
    }

    /**
     * SQL aliases use the plain field key (everything after the dataset
     * prefix), so row values are read with the unqualified key.
     */
    protected function rowValue(object $row, string $key): mixed
    {
        $accessor = str_contains($key, '.') ? Str::afterLast($key, '.') : $key;

        return $row->{$accessor} ?? null;
    }
}
