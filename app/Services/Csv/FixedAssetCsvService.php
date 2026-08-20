<?php

namespace App\Services\Csv;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\FixedAsset;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryLocation;

class FixedAssetCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'asset_number' => [
                'label' => __('Asset Number'),
                'required' => true,
                'guesses' => ['Asset Number', 'Asset No', 'Asset Code'],
                'example' => 'SC-2026-FA-00001',
            ],
            'inventory_item' => [
                'label' => __('Asset Name'),
                'required' => true,
                'guesses' => ['Asset Name', 'Inventory Item', 'Item Name', 'Name'],
                'example' => 'Dell OptiPlex Computer',
            ],
            'serial_number' => [
                'label' => __('Serial Number'),
                'required' => false,
                'guesses' => ['Serial Number', 'Serial No', 'Serial'],
                'example' => 'DL-8XY-2026-0001',
            ],
            'acquisition_date' => [
                'label' => __('Acquisition Date'),
                'required' => true,
                'guesses' => ['Acquisition Date', 'Purchase Date', 'Date Acquired'],
                'example' => '2026-01-15',
                'date' => true,
            ],
            'purchase_cost' => [
                'label' => __('Purchase Cost'),
                'required' => true,
                'guesses' => ['Purchase Cost', 'Cost', 'Purchase Price'],
                'example' => '850.00',
            ],
            'salvage_value' => [
                'label' => __('Salvage Value'),
                'required' => false,
                'guesses' => ['Salvage Value', 'Residual Value'],
                'example' => '50.00',
                'default' => '0',
            ],
            'useful_life_years' => [
                'label' => __('Useful Life (Years)'),
                'required' => true,
                'guesses' => ['Useful Life (Years)', 'Useful Life', 'Useful Life Years'],
                'example' => '5',
            ],
            'depreciation_method' => [
                'label' => __('Depreciation Method'),
                'required' => true,
                'guesses' => ['Depreciation Method', 'Method'],
                'example' => 'straight_line',
                'default' => 'straight_line',
                'in' => ['straight_line', 'double_declining'],
            ],
            'current_value' => [
                'label' => __('Current Value'),
                'required' => false,
                'guesses' => ['Current Value', 'Book Value'],
                'example' => '850.00',
            ],
            'warranty_expiry' => [
                'label' => __('Warranty Expiry'),
                'required' => false,
                'guesses' => ['Warranty Expiry', 'Warranty'],
                'example' => '2028-01-15',
                'date' => true,
            ],
            'funding_source' => [
                'label' => __('Funding Source'),
                'required' => false,
                'guesses' => ['Funding Source', 'Source of Funds'],
                'example' => 'school_funds',
                'in' => ['school_funds', 'government', 'donor', 'pta'],
            ],
            'insurance_policy_number' => [
                'label' => __('Insurance Policy Number'),
                'required' => false,
                'guesses' => ['Insurance Policy Number', 'Insurance Policy', 'Policy Number'],
                'example' => 'INS-2026-887',
            ],
            'location' => [
                'label' => __('Current Room / Location'),
                'required' => false,
                'guesses' => ['Current Room / Location', 'Location', 'Room', 'Current Room'],
                'example' => 'ICT Lab',
            ],
            'custodian_email' => [
                'label' => __('Custodian Email'),
                'required' => false,
                'guesses' => ['Custodian Email', 'Custodian', 'Assigned To'],
                'example' => 'itadmin@schoolcore.test',
            ],
            'status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status'],
                'example' => 'active',
                'default' => 'active',
                'in' => ['active', 'maintenance', 'disposed', 'low_stock'],
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Asset Number', 'Asset Name', 'Serial Number', 'Acquisition Date',
            'Purchase Cost', 'Salvage Value', 'Useful Life (Years)', 'Depreciation Method',
            'Current Value', 'Warranty Expiry', 'Funding Source', 'Insurance Policy Number',
            'Current Room / Location', 'Custodian', 'Status',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = FixedAsset::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['inventoryItem', 'location', 'custodian'])
            ->orderBy('id');

        $lastId = 0;

        do {
            $assets = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($assets->isEmpty()) {
                break;
            }

            foreach ($assets as $asset) {
                yield [
                    $asset->asset_number,
                    $asset->inventoryItem?->name,
                    $asset->serial_number,
                    optional($asset->acquisition_date)->format('Y-m-d'),
                    $asset->purchase_cost,
                    $asset->salvage_value,
                    $asset->useful_life_years,
                    $asset->depreciation_method,
                    $asset->current_value,
                    optional($asset->warranty_expiry)->format('Y-m-d'),
                    $asset->funding_source,
                    $asset->insurance_policy_number,
                    $asset->location?->name,
                    $asset->custodian?->email,
                    $asset->status,
                ];
            }

            $lastId = $assets->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
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

        fgets($handle);

        $total = 0;
        while (fgetcsv($handle, 0, ',', escape: '\\') !== false) {
            $total++;
        }

        rewind($handle);
        fgets($handle);

        $items = InventoryItem::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($i): string => strtolower(trim($i->name)));

        $locations = InventoryLocation::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($l): string => strtolower(trim($l->name)));

        $usersByEmail = User::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($u): string => strtolower(trim($u->email)));

        $existingAssetNumbers = FixedAsset::withoutTenantScope()->where('school_id', $schoolId)->pluck('asset_number')
            ->map(fn ($v): string => strtolower(trim((string) $v)))->flip();

        $success = 0;
        $failures = [];
        $processed = 0;
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, ',', escape: '\\')) !== false) {
            $rowNumber++;
            $processed++;
            $row = array_map('trim', $row);

            $data = array_fill_keys(array_keys(static::columns()), '');

            foreach ($mappedIndexes as $key => $index) {
                $data[$key] = ($index !== null && isset($row[$index])) ? $row[$index] : '';
            }

            if (implode('', $data) === '') {
                $onProgress !== null && $onProgress($processed, $total, false, []);

                continue;
            }

            $errors = static::validateAndNormalize($data, $items, $locations, $usersByEmail, $existingAssetNumbers);

            if (! empty($errors)) {
                $failures[] = ['row' => $rowNumber, 'errors' => $errors, 'data' => $data];
                $onProgress !== null && $onProgress($processed, $total, true, $errors);

                continue;
            }

            try {
                DB::transaction(function () use (&$existingAssetNumbers, $data, $schoolId) {
                    FixedAsset::create([
                        'school_id' => $schoolId,
                        'inventory_item_id' => $data['_item']->id,
                        'asset_number' => $data['asset_number'],
                        'serial_number' => $data['serial_number'] !== '' ? $data['serial_number'] : null,
                        'acquisition_date' => $data['acquisition_date'],
                        'purchase_cost' => (float) $data['purchase_cost'],
                        'salvage_value' => $data['salvage_value'] !== '' ? (float) $data['salvage_value'] : 0,
                        'useful_life_years' => (int) $data['useful_life_years'],
                        'depreciation_method' => $data['depreciation_method'],
                        'current_value' => $data['current_value'] !== '' ? (float) $data['current_value'] : (float) $data['purchase_cost'],
                        'warranty_expiry' => $data['warranty_expiry'] !== '' ? $data['warranty_expiry'] : null,
                        'funding_source' => $data['funding_source'] !== '' ? $data['funding_source'] : null,
                        'insurance_policy_number' => $data['insurance_policy_number'] !== '' ? $data['insurance_policy_number'] : null,
                        'assigned_location_id' => $data['_location']?->id,
                        'custodian_id' => $data['_custodian']?->id,
                        'status' => $data['status'] !== '' ? $data['status'] : 'active',
                    ]);

                    if (filled($data['asset_number'])) {
                        $existingAssetNumbers[strtolower(trim($data['asset_number']))] = true;
                    }
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

    protected static function validateAndNormalize(
        array &$data,
        Collection $items,
        Collection $locations,
        Collection $usersByEmail,
        Collection $existingAssetNumbers,
    ): array {
        $errors = [];

        foreach (['asset_number', 'inventory_item', 'acquisition_date', 'purchase_cost', 'useful_life_years'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        $assetNumber = strtolower(trim($data['asset_number'] ?? ''));
        if ($assetNumber !== '' && isset($existingAssetNumbers[$assetNumber])) {
            $errors[] = 'Asset Number ['.$data['asset_number'].'] already exists for another asset in this school.';
        }

        foreach (['purchase_cost', 'salvage_value', 'useful_life_years', 'current_value'] as $numericField) {
            $raw = trim($data[$numericField] ?? '');

            if ($raw === '') {
                continue;
            }

            if (! is_numeric($raw)) {
                $errors[] = static::columns()[$numericField]['label'].' ['.$raw.'] must be a number.';
            }
        }

        foreach (['acquisition_date', 'warranty_expiry'] as $dateField) {
            $raw = trim($data[$dateField] ?? '');

            if ($raw === '') {
                continue;
            }

            try {
                $data[$dateField] = Carbon::parse($raw)->toDateString();
            } catch (\Throwable) {
                $errors[] = static::columns()[$dateField]['label'].' ['.$raw.'] is not a valid date. Use YYYY-MM-DD.';
            }
        }

        $data['depreciation_method'] = strtolower(trim($data['depreciation_method'] ?? ''));
        if (! in_array($data['depreciation_method'], ['straight_line', 'double_declining'], true)) {
            $errors[] = 'Depreciation Method must be one of: straight_line, double_declining.';
        }

        $data['funding_source'] = strtolower(trim($data['funding_source'] ?? ''));
        if ($data['funding_source'] !== '' && ! in_array($data['funding_source'], ['school_funds', 'government', 'donor', 'pta'], true)) {
            $errors[] = 'Funding Source must be one of: school_funds, government, donor, pta.';
        }

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] !== '' && ! in_array($data['status'], ['active', 'maintenance', 'disposed', 'low_stock'], true)) {
            $errors[] = 'Status must be one of: active, maintenance, disposed, low_stock.';
        }

        if (empty($errors) && $data['inventory_item'] !== '') {
            $item = $items[strtolower($data['inventory_item'])] ?? null;

            if (! $item) {
                $errors[] = 'Asset Name ['.$data['inventory_item'].'] was not found in the Item Registry. Available items: '.($items->pluck('name')->implode(', ') ?: 'none').'. Add the item first or use the exact registry name.';
            } else {
                $data['_item'] = $item;
            }
        }

        $locationName = trim($data['location'] ?? '');
        $data['_location'] = $locationName !== '' ? ($locations[strtolower($locationName)] ?? null) : null;
        if ($locationName !== '' && ! $data['_location']) {
            $errors[] = 'Location ['.$locationName.'] was not found in this school. Available locations: '.($locations->pluck('name')->implode(', ') ?: 'none').'.';
        }

        $custodianEmail = strtolower(trim($data['custodian_email'] ?? ''));
        $data['_custodian'] = $custodianEmail !== '' ? ($usersByEmail[$custodianEmail] ?? null) : null;
        if ($custodianEmail !== '' && ! $data['_custodian']) {
            $errors[] = 'Custodian Email ['.$data['custodian_email'].'] does not match any user account in this school.';
        }

        return $errors;
    }
}
