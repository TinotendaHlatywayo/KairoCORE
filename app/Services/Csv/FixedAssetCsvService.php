<?php

namespace App\Services\Csv;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Inventory\Models\FixedAsset;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryLocation;

class FixedAssetCsvService extends CsvBulkService
{
    /**
     * Reference types that may not exist yet in the school. When an imported
     * row references a Registry item or location that is missing, the import
     * auto-creates it (default), unless the user explicitly chose "Skip" in the
     * Match Columns step.
     *
     * @return array<string, array{label: string, model: string, column: string}>
     */
    public static function referenceFields(): array
    {
        return [
            'inventory_item' => [
                'label' => __('Asset Name'),
                'model' => InventoryItem::class,
                'column' => 'name',
            ],
            'location' => [
                'label' => __('Current Room / Location'),
                'model' => InventoryLocation::class,
                'column' => 'name',
            ],
        ];
    }

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

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null, array $options = []): array
    {
        $missingRefs = $options['missingRefs'] ?? [];

        $lookups = [
            'items' => InventoryItem::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn ($i): string => strtolower(trim($i->name))),
            'locations' => InventoryLocation::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn ($l): string => strtolower(trim($l->name))),
            'usersByEmail' => User::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn ($u): string => strtolower(trim($u->email))),
            'existingAssetNumbers' => FixedAsset::withoutTenantScope()->where('school_id', $schoolId)->pluck('asset_number')
                ->map(fn ($v): string => strtolower(trim((string) $v)))->flip(),
            'missingRefs' => $missingRefs,
            'unknownItems' => static::unknownReferenceValues($filePath, $schoolId, $columnMap, 'inventory_item'),
            'unknownLocations' => static::unknownReferenceValues($filePath, $schoolId, $columnMap, 'location'),
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

    /**
     * Read the mapped column of the uploaded file and return only the values
     * that do not yet exist in the reference model, sorted for stable indices.
     */
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

    protected static function referencePolicy(array $lookups, string $fieldKey, string $value): string
    {
        $missingRefs = $lookups['missingRefs'] ?? [];
        $unknownKey = $fieldKey === 'inventory_item' ? 'unknownItems' : ($fieldKey === 'location' ? 'unknownLocations' : null);
        $unknownValues = $unknownKey !== null ? ($lookups[$unknownKey] ?? []) : [];

        foreach (array_keys($missingRefs[$fieldKey] ?? []) as $index) {
            if (($unknownValues[(int) $index] ?? null) === $value) {
                return (string) ($missingRefs[$fieldKey][$index] ?? 'create');
            }
        }

        return 'create';
    }

    protected static function validateAndNormalize(array &$data, array $lookups): array
    {
        $errors = [];

        foreach (['asset_number', 'inventory_item', 'acquisition_date', 'purchase_cost', 'useful_life_years'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        $assetNumber = strtolower(trim($data['asset_number'] ?? ''));
        if ($assetNumber !== '' && isset($lookups['existingAssetNumbers'][$assetNumber])) {
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
            $itemName = $data['inventory_item'];
            $item = $lookups['items'][strtolower($itemName)] ?? null;

            if ($item) {
                $data['_item'] = $item;
                $data['_create_item'] = false;
            } else {
                $policy = static::referencePolicy($lookups, 'inventory_item', $itemName);
                $data['_item'] = null;
                $data['_create_item'] = $policy !== 'skip';
            }
        }

        $locationName = trim($data['location'] ?? '');
        $location = $locationName !== '' ? ($lookups['locations'][strtolower($locationName)] ?? null) : null;

        if ($location) {
            $data['_location'] = $location;
            $data['_create_location'] = false;
        } else {
            $data['_location'] = null;
            $data['_create_location'] = $locationName !== '' && static::referencePolicy($lookups, 'location', $locationName) !== 'skip';
        }

        $custodianEmail = strtolower(trim($data['custodian_email'] ?? ''));
        $data['_custodian'] = $custodianEmail !== '' ? ($lookups['usersByEmail'][$custodianEmail] ?? null) : null;

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        $item = $data['_item'];

        if ((($data['_create_item'] ?? false)) && $item === null && ($data['inventory_item'] ?? '') !== '') {
            $item = static::createInventoryItem($data['inventory_item'], $schoolId, $lookups);
        }

        $location = $data['_location'];

        if (($data['_create_location'] ?? false) && $location === null && ($data['location'] ?? '') !== '') {
            $location = static::createLocation($data['location'], $schoolId, $lookups);
        }

        if ($item === null) {
            throw new \RuntimeException('Asset Name ['.($data['inventory_item'] ?? '').'] was not found and could not be created.');
        }

        FixedAsset::create([
            'school_id' => $schoolId,
            'inventory_item_id' => $item->id,
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
            'assigned_location_id' => $location?->id,
            'custodian_id' => $data['_custodian']?->id,
            'status' => $data['status'] !== '' ? $data['status'] : 'active',
        ]);

        if (filled($data['asset_number'])) {
            $lookups['existingAssetNumbers'][strtolower(trim($data['asset_number']))] = true;
        }
    }

    protected static function createInventoryItem(string $name, int $schoolId, array &$lookups): InventoryItem
    {
        $name = trim($name);

        if (isset($lookups['items'][strtolower($name)])) {
            return $lookups['items'][strtolower($name)];
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

        $sku = static::makeUniqueSku($schoolId, $name);

        $item = InventoryItem::withoutTenantScope()->create([
            'school_id' => $schoolId,
            'category_id' => $category->id,
            'name' => $name,
            'sku' => $sku,
            'item_type' => 'fixed_asset',
            'unit_of_measure' => 'units',
            'current_quantity' => 1,
        ]);

        $lookups['items'][strtolower($name)] = $item;

        return $item;
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

    protected static function createLocation(string $name, int $schoolId, array &$lookups): InventoryLocation
    {
        $name = trim($name);

        if (isset($lookups['locations'][strtolower($name)])) {
            return $lookups['locations'][strtolower($name)];
        }

        $existingCodes = InventoryLocation::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->pluck('code')
            ->map(fn ($v): string => strtolower(trim((string) $v)))
            ->flip();

        $base = strtoupper(Str::slug($name, '-') ?: 'LOC');
        $base = Str::limit($base, 20, '');
        $code = $base;
        $suffix = 1;

        while (isset($existingCodes[strtolower($code)])) {
            $suffix++;
            $code = $base.'-'.$suffix;
        }

        $location = InventoryLocation::withoutTenantScope()->create([
            'school_id' => $schoolId,
            'name' => $name,
            'code' => $code,
            'type' => 'general',
        ]);

        $lookups['locations'][strtolower($name)] = $location;

        return $location;
    }
}
