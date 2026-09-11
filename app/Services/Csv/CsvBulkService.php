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

    /**
     * Record types referenced from an uploaded file whose values may not exist
     * yet in the system. When a mapped column's values do not match an existing
     * record, the import wizard shows a create-or-skip prompt before saving.
     *
     * Format:
     *   'department' => ['label' => __('Department'), 'model' => Department::class, 'column' => 'name']
     */
    public static function referenceFields(): array
    {
        return [];
    }

    /**
     * Distinct, non-blank cell values found in the mapped column of an uploaded
     * file, keyed by the field key. Order is stable (sorted) so a value's index
     * matches the index used by the create-or-skip prompt in the import wizard.
     */
    public static function readReferenceValues(string $filePath, array $columnMap, string $fieldKey): array
    {
        $headers = static::readCsvHeaders($filePath);

        if (empty($headers) || blank($columnMap[$fieldKey] ?? null)) {
            return [];
        }

        $headerIndex = [];
        foreach ($headers as $i => $header) {
            $headerIndex[strtolower($header)] = $i;
        }

        $index = $headerIndex[strtolower(trim((string) $columnMap[$fieldKey]))] ?? null;

        if ($index === null) {
            return [];
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return [];
        }

        fgets($handle); // skip header row

        $values = [];
        while (($row = fgetcsv($handle, 0, ',', escape: '\\')) !== false) {
            $value = trim((string) ($row[$index] ?? ''));

            if ($value !== '') {
                $values[$value] = $value;
            }
        }

        fclose($handle);
        ksort($values);

        return array_values($values);
    }

    public static function templateHeaders(): array
    {
        return array_column(static::columns(), 'label');
    }

    public static function templateCsv(): string
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it cleanly
        fputcsv($out, static::templateHeaders());

        $sample = array_map(fn (array $column): string => $column['example'] ?? '', static::columns());
        
        // Generate at least 5 rows of data. Identifier/code columns are varied
        // so each row is unique and the template can be imported end-to-end
        // without tripping unique constraints (e.g. asset numbers, SKUs).
        for ($i = 1; $i <= 5; $i++) {
            $row = $sample;
            if ($i > 1) {
                foreach ($row as $colKey => $val) {
                    $row[$colKey] = static::varySample((string) $val, $i);
                }
            }
            fputcsv($out, $row);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    /**
     * Vary a sample cell value for row $index (1-based) of the template.
     *
     * - Fully numeric values are incremented.
     * - String identifiers/codes that end in a run of digits have that trailing
     *   run incremented (e.g. SC-2026-FA-00001 -> SC-2026-FA-00002). This keeps
     *   identifier and date columns distinct across template rows so an import
     *   of the template itself succeeds without duplicate-key errors.
     */
    protected static function varySample(string $value, int $index): string
    {
        if ($value === '') {
            return $value;
        }

        if (is_numeric($value)) {
            if (str_contains(strtolower($value), '1')) {
                return (string) ((int) $value + ($index - 1));
            }

            return $value;
        }

        if (preg_match('/^(.*?[^0-9])([0-9]+)$/', $value, $m)) {
            $suffix = (int) $m[2] + ($index - 1);

            return $m[1].str_pad((string) $suffix, strlen($m[2]), '0', STR_PAD_LEFT);
        }

        return $value;
    }

    public static function resolveTempFilePath(string|TemporaryUploadedFile|array $file): string
    {
        if (is_array($file)) {
            $file = Arr::first($file);
        }

        if ($file instanceof TemporaryUploadedFile) {
            return $file->getRealPath();
        }

        $disk = config('filesystems.default');
        if (Storage::disk($disk)->exists($file)) {
            return Storage::disk($disk)->path($file);
        }

        return storage_path('app/public/' . $file);
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

    /**
     * Normalise a header for fuzzy matching: lowercase, strip everything that
     * is not a letter or digit, so "Student Name", "student_name" and
     * "STUDENT-NAME" all resolve to the same key.
     */
    protected static function normaliseHeader(string $header): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]/i', '', $header));
    }

    /**
     * Auto-match each expected column to the closest matching CSV header.
     *
     * Name-based matching is tried first. Headers are normalised (case,
     * spaces, underscores, dashes all ignored) and every alias in a column's
     * "guesses" list is checked, so `student_name` matches `Student Name`.
     * Any column still unmatched then falls back to the CSV column in the same
     * ordinal position, since files usually follow the template order. The
     * result is only a suggestion — the import wizard always lets the user
     * override it manually.
     */
    public static function guessMapping(array $csvHeaders): array
    {
        $normalisedHeaders = array_map([static::class, 'normaliseHeader'], $csvHeaders);
        $used = [];
        $mapping = [];

        foreach (static::columns() as $key => $column) {
            $guesses = array_map(
                fn (string $guess): string => static::normaliseHeader($guess),
                $column['guesses'] ?? [$column['label']],
            );
            $mapping[$key] = null;

            // Also compare against the column's system key itself, e.g. a
            // header of "fee_waiver_id" maps to the fee_waiver_id column.
            $guesses[] = static::normaliseHeader((string) $key);

            foreach ($normalisedHeaders as $i => $normalisedHeader) {
                if (($used[$normalisedHeader] ?? false) || ! in_array($normalisedHeader, $guesses, true)) {
                    continue;
                }

                $mapping[$key] = $csvHeaders[$i];
                $used[$normalisedHeader] = true;
                break;
            }
        }

        foreach (array_keys(static::columns()) as $order => $key) {
            if ($mapping[$key] !== null || ! isset($csvHeaders[$order])) {
                continue;
            }

            $fallback = $csvHeaders[$order];

            if ($used[static::normaliseHeader($fallback)] ?? false) {
                continue;
            }

            $mapping[$key] = $fallback;
            $used[static::normaliseHeader($fallback)] = true;
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
