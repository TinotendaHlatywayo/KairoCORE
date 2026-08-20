<?php

namespace App\Services\Csv;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Single source of truth for a resource's CSV bulk features.
 *
 * Extend this per entity (Students, Employees, Inventory Items, Fixed Assets,
 * Suppliers, ...) and implement the four abstract methods. The shared helpers
 * here power the reusable import wizard, template download and CSV/PDF export.
 *
 * Column definitions use the same shape as the Student reference:
 *   'key' => [
 *       'label' => __('Human column header'),
 *       'required' => bool,
 *       'guesses'  => ['Alternative', 'headers'],
 *       'example'  => 'Sample value',
 *       'in'       => ['allowed', 'values'],   // optional enum
 *       'date'     => true,                    // optional date
 *       'default'  => 'fallback',              // optional default
 *   ]
 */
abstract class CsvBulkService
{
    /** Keyed column definitions used by the template, matching and export. */
    abstract public static function columns(): array;

    /** Headers used by the CSV/PDF export (labels, not system keys). */
    abstract public static function exportHeaders(): array;

    /** Yield one export row (list of values) per record for a school. */
    abstract public static function exportRows(int $schoolId): iterable;

    /**
     * Import rows for a school.
     *
     * @return array{success: int, total: int, failures: array}
     */
    abstract public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array;

    public static function templateHeaders(): array
    {
        return array_column(static::columns(), 'label');
    }

    public static function templateCsv(): string
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it cleanly
        fputcsv($out, static::templateHeaders());
        fputcsv($out, array_map(fn (array $column): string => $column['example'] ?? '', static::columns()));
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    public static function resolveTempFilePath(string|TemporaryUploadedFile|array $file): string
    {
        if (is_array($file)) {
            $file = Arr::first($file);
        }

        if ($file instanceof TemporaryUploadedFile) {
            return $file->getRealPath();
        }

        $disk = config('livewire.temporary_file_upload.disk') ?: config('filesystems.default');

        return Storage::disk($disk)->path($file);
    }

    /** Read the header row of an uploaded CSV (BOM-safe). */
    public static function readCsvHeaders(string $filePath): array
    {
        if (! is_readable($filePath)) {
            return [];
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return [];
        }

        $line = fgets($handle);
        fclose($handle);

        if ($line === false) {
            return [];
        }

        $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
        $headers = str_getcsv(trim($line), escape: '\\');

        return array_map('trim', array_map('strval', $headers ?: []));
    }

    /** Auto-match each expected column to the closest matching CSV header. */
    public static function guessMapping(array $csvHeaders): array
    {
        $lowerHeaders = array_map('strtolower', $csvHeaders);
        $mapping = [];

        foreach (static::columns() as $key => $column) {
            $guesses = array_map('strtolower', $column['guesses'] ?? [$column['label']]);
            $mapping[$key] = null;

            foreach ($lowerHeaders as $i => $lowerHeader) {
                if (in_array($lowerHeader, $guesses, true)) {
                    $mapping[$key] = $csvHeaders[$i];
                    break;
                }
            }
        }

        return $mapping;
    }

    /**
     * Shared import driver: reads the file once for totals, maps columns,
     * streams progress, collects per-row failures and wraps each insert in a
     * transaction. New services implement `columns()` plus `import()` that
     * calls this with validation + creation callbacks.
     *
     * @param  array  $lookups  lookups keyed by name, passed by reference so the
     *                          create callback can append newly-inserted keys
     *                          (e.g. dedupe sets).
     * @param  callable  $validate  fn(array &$data, array $lookups): array  errors
     * @param  callable  $create  fn(array $data, int $schoolId, array &$lookups): void
     * @return array{success: int, total: int, failures: array}
     */
    protected static function runImport(
        string $filePath,
        int $schoolId,
        array $columnMap,
        ?callable $onProgress,
        array &$lookups,
        callable $validate,
        callable $create,
    ): array {
        $csvHeaders = static::readCsvHeaders($filePath);

        if (empty($csvHeaders)) {
            throw new \RuntimeException('The CSV file has no readable header row. Download the template and use its exact column names.');
        }

        $headerIndex = [];
        foreach ($csvHeaders as $i => $header) {
            $headerIndex[strtolower($header)] = $i;
        }

        $mappedIndexes = [];
        foreach ($columnMap as $key => $header) {
            if (blank($header)) {
                $mappedIndexes[$key] = null;

                continue;
            }
            $mappedIndexes[$key] = $headerIndex[strtolower($header)] ?? null;
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Could not open the CSV file.');
        }

        fgets($handle); // skip header row

        $total = 0;
        while (fgetcsv($handle, 0, ',', escape: '\\') !== false) {
            $total++;
        }

        rewind($handle);
        fgets($handle); // skip header row again

        $columns = static::columns();
        $success = 0;
        $failures = [];
        $processed = 0;
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, ',', escape: '\\')) !== false) {
            $rowNumber++;
            $processed++;
            $row = array_map('trim', $row);

            $data = array_fill_keys(array_keys($columns), '');

            foreach ($mappedIndexes as $key => $index) {
                $data[$key] = ($index !== null && isset($row[$index])) ? $row[$index] : '';
            }

            if (implode('', $data) === '') {
                $onProgress !== null && $onProgress($processed, $total, false, []);

                continue;
            }

            $errors = $validate($data, $lookups);

            if (! empty($errors)) {
                $failures[] = ['row' => $rowNumber, 'errors' => $errors, 'data' => $data];
                $onProgress !== null && $onProgress($processed, $total, true, $errors);

                continue;
            }

            try {
                DB::transaction(function () use ($create, $data, $schoolId, &$lookups) {
                    $create($data, $schoolId, $lookups);
                });

                $success++;
                $onProgress !== null && $onProgress($processed, $total, false, []);
            } catch (\Throwable $e) {
                $failures[] = [
                    'row' => $rowNumber,
                    'errors' => ['Unexpected database error while saving: '.$e->getMessage()],
                    'data' => $data,
                ];
                $onProgress !== null && $onProgress($processed, $total, true, ['Unexpected database error']);
            }
        }

        fclose($handle);

        return compact('success', 'total', 'failures');
    }

    /** Normalise a boolean-ish CSV cell to a Laravel-ready boolean. */
    protected static function toBoolean(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'y'], true);
    }

    /** Normalise a decimal cell, returning float (0 when blank/invalid). */
    protected static function toDecimal(string $value, float $default = 0): float
    {
        $value = trim($value);

        return $value === '' || ! is_numeric($value) ? $default : (float) $value;
    }

    /** Normalise an integer cell, returning int (0 when blank/invalid). */
    protected static function toInt(string $value, int $default = 0): int
    {
        $value = trim($value);

        return $value === '' || ! is_numeric($value) ? $default : (int) $value;
    }

    /** Normalise a date cell to Y-m-d; null when blank/invalid. */
    protected static function toDate(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
