<?php

namespace App\Services\Csv;

use Illuminate\Support\Str;
use Modules\Inventory\Models\AssetMaintenanceLog;
use Modules\Inventory\Models\FixedAsset;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryItem;

class AssetMaintenanceCsvService extends CsvBulkService
{
    /**
     * Maintenance logs reference a fixed asset by its asset number. When an
     * imported row references an asset that does not exist yet, the import
     * auto-creates a minimal fixed asset (and its Registry item) so the log can
     * attach to it — unless the user explicitly chose "Skip" in the Match
     * Columns step.
     *
     * @return array<string, array{label: string, model: string, column: string}>
     */
    public static function referenceFields(): array
    {
        return [
            'asset' => [
                'label' => __('Asset Number'),
                'model' => FixedAsset::class,
                'column' => 'asset_number',
            ],
        ];
    }

    public static function columns(): array
    {
        return [
            'asset' => [
                'label' => __('Asset Number'),
                'required' => true,
                'guesses' => ['Asset', 'Asset Number', 'Fixed Asset'],
                'example' => 'FA-2026-0001',
            ],
            'title' => [
                'label' => __('Title'),
                'required' => true,
                'guesses' => ['Title', 'Maintenance Title'],
                'example' => 'Brake service',
            ],
            'type' => [
                'label' => __('Type'),
                'required' => false,
                'guesses' => ['Type'],
                'example' => 'preventive',
                'default' => 'preventive',
                'in' => ['preventive', 'corrective', 'calibration'],
            ],
            'schedule_type' => [
                'label' => __('Schedule Type'),
                'required' => false,
                'guesses' => ['Schedule Type'],
                'example' => 'one_time',
                'default' => 'one_time',
                'in' => ['one_time', 'recurring'],
            ],
            'recurrence_interval_days' => [
                'label' => __('Recurrence Interval Days'),
                'required' => false,
                'guesses' => ['Recurrence Interval Days', 'Interval Days'],
                'example' => '90',
            ],
            'scheduled_date' => [
                'label' => __('Scheduled Date'),
                'required' => true,
                'guesses' => ['Scheduled Date', 'Date'],
                'example' => '2026-08-01',
                'date' => true,
            ],
            'completed_date' => [
                'label' => __('Completed Date'),
                'required' => false,
                'guesses' => ['Completed Date', 'Date Completed'],
                'example' => '2026-08-15',
                'date' => true,
            ],
            'cost' => [
                'label' => __('Cost'),
                'required' => false,
                'guesses' => ['Cost', 'Amount'],
                'example' => '150.00',
                'default' => '0',
            ],
            'performed_by' => [
                'label' => __('Performed By'),
                'required' => false,
                'guesses' => ['Performed By', 'Technician'],
                'example' => 'AutoCare Garage',
            ],
            'status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status'],
                'example' => 'pending',
                'default' => 'pending',
                'in' => ['pending', 'in_progress', 'completed', 'overdue'],
            ],
            'notes' => [
                'label' => __('Notes'),
                'required' => false,
                'guesses' => ['Notes'],
                'example' => 'Inspect brake pads and fluid levels.',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Asset Number', 'Title', 'Type', 'Schedule Type', 'Recurrence Interval Days',
            'Scheduled Date', 'Completed Date', 'Cost', 'Performed By', 'Status', 'Notes',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = AssetMaintenanceLog::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with('fixedAsset')
            ->orderBy('id');

        $lastId = 0;

        do {
            $logs = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($logs->isEmpty()) {
                break;
            }

            foreach ($logs as $log) {
                yield [
                    $log->fixedAsset?->asset_number,
                    $log->title,
                    $log->type,
                    $log->schedule_type,
                    $log->recurrence_interval_days,
                    optional($log->scheduled_date)->format('Y-m-d'),
                    optional($log->completed_date)->format('Y-m-d'),
                    $log->cost,
                    $log->performed_by,
                    $log->status,
                    $log->notes,
                ];
            }

            $lastId = $logs->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null, array $options = []): array
    {
        $missingRefs = $options['missingRefs'] ?? [];

        $lookups = [
            'fixedAssets' => FixedAsset::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn ($a): string => strtolower(trim($a->asset_number))),
            'missingRefs' => $missingRefs,
            'unknownAssets' => static::unknownReferenceValues($filePath, $schoolId, $columnMap, 'asset'),
            'items' => InventoryItem::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn ($i): string => strtolower(trim($i->sku))),
        ];

        return static::runImport(
            $filePath,
            $schoolId,
            $columnMap,
            $onProgress,
            $lookups,
            fn (array &$data, array $lookups) => static::validateAndNormalize($data, $lookups),
            fn (array $data, int $schoolId, array &$lookups) => static::createRow($data, $schoolId, $lookups),
        );
    }

    protected static function unknownReferenceValues(string $filePath, int $schoolId, array $columnMap, string $fieldKey): array
    {
        $candidates = static::readReferenceValues($filePath, $columnMap, $fieldKey);
        $reference = static::referenceFields()[$fieldKey] ?? null;

        if ($reference === null || empty($candidates)) {
            return [];
        }

        $existing = $reference['model']::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->pluck($reference['column'])
            ->map(fn ($value): string => strtolower(trim((string) $value)))
            ->flip();

        $unknown = [];
        foreach ($candidates as $value) {
            if (! $existing->has(strtolower($value))) {
                $unknown[] = $value;
            }
        }

        return array_values($unknown);
    }

    protected static function assetPolicy(array $lookups, string $value): string
    {
        $missingRefs = $lookups['missingRefs'] ?? [];
        $unknownValues = $lookups['unknownAssets'] ?? [];

        foreach (array_keys($missingRefs['asset'] ?? []) as $index) {
            if (($unknownValues[(int) $index] ?? null) === $value) {
                return (string) ($missingRefs['asset'][$index] ?? 'create');
            }
        }

        return 'create';
    }

    protected static function validateAndNormalize(array &$data, array $lookups): array
    {
        $errors = [];

        foreach (['asset', 'title', 'scheduled_date'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        $assetNumber = trim($data['asset'] ?? '');
        $data['_fixedAsset'] = $assetNumber !== '' ? ($lookups['fixedAssets'][strtolower($assetNumber)] ?? null) : null;
        $data['_create_asset'] = false;

        if ($assetNumber !== '' && ! $data['_fixedAsset']) {
            $data['_create_asset'] = static::assetPolicy($lookups, $assetNumber) !== 'skip';
        }

        $data['type'] = strtolower(trim($data['type'] ?? ''));
        if ($data['type'] !== '' && ! in_array($data['type'], ['preventive', 'corrective', 'calibration'], true)) {
            $errors[] = 'Type must be one of: preventive, corrective, calibration.';
        }

        $data['schedule_type'] = strtolower(trim($data['schedule_type'] ?? ''));
        if ($data['schedule_type'] !== '' && ! in_array($data['schedule_type'], ['one_time', 'recurring'], true)) {
            $errors[] = 'Schedule Type must be one of: one_time, recurring.';
        }

        $recurrence = trim($data['recurrence_interval_days'] ?? '');
        if ($data['schedule_type'] === 'recurring' && $recurrence === '') {
            $errors[] = 'Recurrence Interval Days is required when Schedule Type is recurring.';
        }

        if ($recurrence !== '' && ! is_numeric($recurrence)) {
            $errors[] = 'Recurrence Interval Days ['.$recurrence.'] must be a number.';
        }

        foreach (['scheduled_date', 'completed_date'] as $dateField) {
            $raw = trim($data[$dateField] ?? '');

            if ($raw === '') {
                continue;
            }

            $parsed = static::toDate($raw);

            if ($parsed === null) {
                $errors[] = static::columns()[$dateField]['label'].' ['.$raw.'] is not a valid date. Use YYYY-MM-DD.';
            } else {
                $data[$dateField] = $parsed;
            }
        }

        if ($data['scheduled_date'] !== '' && $data['completed_date'] !== ''
            && $data['completed_date'] < $data['scheduled_date']) {
            $errors[] = 'Completed Date ['.$data['completed_date'].'] must be on or after Scheduled Date ['.$data['scheduled_date'].'].';
        }

        $cost = trim($data['cost'] ?? '');
        if ($cost !== '' && ! is_numeric($cost)) {
            $errors[] = 'Cost ['.$cost.'] must be a number.';
        }

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] !== '' && ! in_array($data['status'], ['pending', 'in_progress', 'completed', 'overdue'], true)) {
            $errors[] = 'Status must be one of: pending, in_progress, completed, overdue.';
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        $asset = $data['_fixedAsset'];

        if (($data['_create_asset'] ?? false) && $asset === null && ($data['asset'] ?? '') !== '') {
            $asset = static::createFixedAsset($data['asset'], $schoolId, $lookups);
        }

        if ($asset === null) {
            throw new \RuntimeException('Asset Number ['.($data['asset'] ?? '').'] was not found and could not be created.');
        }

        AssetMaintenanceLog::create([
            'school_id' => $schoolId,
            'fixed_asset_id' => $asset->id,
            'title' => $data['title'],
            'type' => $data['type'] !== '' ? $data['type'] : 'preventive',
            'schedule_type' => $data['schedule_type'] !== '' ? $data['schedule_type'] : 'one_time',
            'recurrence_interval_days' => $data['recurrence_interval_days'] !== '' ? (int) $data['recurrence_interval_days'] : null,
            'scheduled_date' => $data['scheduled_date'],
            'completed_date' => $data['completed_date'] !== '' ? $data['completed_date'] : null,
            'cost' => $data['cost'] !== '' ? (float) $data['cost'] : 0,
            'performed_by' => $data['performed_by'] !== '' ? $data['performed_by'] : null,
            'status' => $data['status'] !== '' ? $data['status'] : 'pending',
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ]);
    }

    protected static function createFixedAsset(string $assetNumber, int $schoolId, array &$lookups): FixedAsset
    {
        $assetNumber = trim($assetNumber);
        $key = strtolower($assetNumber);

        if (isset($lookups['fixedAssets'][$key])) {
            return $lookups['fixedAssets'][$key];
        }

        $category = InventoryCategory::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->orderBy('id')
            ->first();

        if ($category === null) {
            $category = InventoryCategory::withoutTenantScope()->create([
                'school_id' => $schoolId,
                'name' => 'Fixed Assets',
            ]);
        }

        $sku = static::makeUniqueSku($schoolId, 'Asset '.$assetNumber);
        $item = InventoryItem::withoutTenantScope()->create([
            'school_id' => $schoolId,
            'category_id' => $category->id,
            'name' => 'Fixed Asset '.$assetNumber,
            'sku' => $sku,
            'item_type' => 'fixed_asset',
            'unit_of_measure' => 'units',
            'current_quantity' => 1,
        ]);

        $asset = FixedAsset::withoutTenantScope()->create([
            'school_id' => $schoolId,
            'inventory_item_id' => $item->id,
            'asset_number' => $assetNumber,
            'acquisition_date' => now()->toDateString(),
            'purchase_cost' => 0,
            'salvage_value' => 0,
            'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
            'current_value' => 0,
            'status' => 'active',
        ]);

        $lookups['fixedAssets'][strtolower($assetNumber)] = $asset;

        return $asset;
    }

    protected static function makeUniqueSku(int $schoolId, string $name): string
    {
        $base = strtoupper(Str::slug($name, '-') ?: 'ITEM');
        $base = Str::limit($base, 20, '');

        if ($base === '') {
            $base = 'ITEM';
        }

        $existing = InventoryItem::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->pluck('sku')
            ->map(fn ($v): string => strtolower(trim((string) $v)))
            ->flip();

        $code = $base;
        $suffix = 1;

        while (isset($existing[strtolower($code)])) {
            $suffix++;
            $code = $base.'-'.$suffix;
        }

        return $code;
    }
}
