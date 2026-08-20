<?php

namespace App\Services\Csv;

use App\Models\User;
use Modules\Inventory\Models\InventoryLocation;
use Modules\Inventory\Models\InventoryStockAdjustment;

class StockAdjustmentCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'location' => [
                'label' => __('Location Name'),
                'required' => true,
                'guesses' => ['Location', 'Location Name', 'Warehouse'],
                'example' => 'Main Store',
            ],
            'adjustment_number' => [
                'label' => __('Adjustment Number'),
                'required' => true,
                'guesses' => ['Adjustment Number', 'ADJ Number'],
                'example' => 'ADJ-2026-0001',
            ],
            'status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status'],
                'example' => 'draft',
                'default' => 'draft',
                'in' => ['draft', 'completed'],
            ],
            'conducted_date' => [
                'label' => __('Conducted Date'),
                'required' => true,
                'guesses' => ['Conducted Date', 'Date'],
                'example' => '2026-07-05',
                'date' => true,
            ],
            'conducted_by' => [
                'label' => __('Conducted By'),
                'required' => false,
                'guesses' => ['Conducted By'],
                'example' => 'Tendai Mutasa',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Location Name', 'Adjustment Number', 'Status', 'Conducted Date', 'Conducted By',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = InventoryStockAdjustment::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['location', 'conductedBy'])
            ->orderBy('id');

        $lastId = 0;

        do {
            $adjustments = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($adjustments->isEmpty()) {
                break;
            }

            foreach ($adjustments as $adjustment) {
                yield [
                    $adjustment->location?->name,
                    $adjustment->adjustment_number,
                    $adjustment->status,
                    optional($adjustment->conducted_date)->format('Y-m-d'),
                    $adjustment->conductedBy?->name,
                ];
            }

            $lastId = $adjustments->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $lookups = [
            'locations' => InventoryLocation::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn ($l): string => strtolower(trim($l->name))),
            'existingAdjustmentNumbers' => InventoryStockAdjustment::withoutTenantScope()->where('school_id', $schoolId)->pluck('adjustment_number')
                ->map(fn ($v): string => strtolower(trim((string) $v)))->flip(),
            'usersByName' => User::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn ($u): string => strtolower(trim($u->name))),
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

    protected static function validateAndNormalize(array &$data, array $lookups): array
    {
        $errors = [];

        foreach (['location', 'adjustment_number', 'conducted_date'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        $adjustmentNumber = strtolower(trim($data['adjustment_number'] ?? ''));
        if ($adjustmentNumber !== '' && isset($lookups['existingAdjustmentNumbers'][$adjustmentNumber])) {
            $errors[] = 'Adjustment Number ['.$data['adjustment_number'].'] already exists for another adjustment in this school.';
        }

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] !== '' && ! in_array($data['status'], ['draft', 'completed'], true)) {
            $errors[] = 'Status must be one of: draft, completed.';
        }

        $date = trim($data['conducted_date'] ?? '');
        if ($date !== '') {
            $parsed = static::toDate($date);

            if ($parsed === null) {
                $errors[] = 'Conducted Date ['.$date.'] is not a valid date. Use YYYY-MM-DD.';
            } else {
                $data['conducted_date'] = $parsed;
            }
        }

        $locationName = trim($data['location'] ?? '');
        $data['_location'] = $locationName !== '' ? ($lookups['locations'][strtolower($locationName)] ?? null) : null;
        if ($locationName !== '' && ! $data['_location']) {
            $errors[] = 'Location ['.$locationName.'] was not found in this school. Available locations: '.($lookups['locations']->pluck('name')->implode(', ') ?: 'none').'.';
        }

        $conductedBy = trim($data['conducted_by'] ?? '');
        $data['_conductedBy'] = $conductedBy !== '' ? ($lookups['usersByName'][strtolower($conductedBy)] ?? null) : null;
        if ($conductedBy !== '' && ! $data['_conductedBy']) {
            $errors[] = 'Conducted By ['.$conductedBy.'] does not match any user account in this school.';
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        InventoryStockAdjustment::create([
            'school_id' => $schoolId,
            'inventory_location_id' => $data['_location']->id,
            'adjustment_number' => $data['adjustment_number'],
            'status' => $data['status'] !== '' ? $data['status'] : 'draft',
            'conducted_date' => $data['conducted_date'],
            'conducted_by_id' => $data['_conductedBy']?->id,
        ]);

        $lookups['existingAdjustmentNumbers'][strtolower(trim($data['adjustment_number']))] = true;
    }
}
