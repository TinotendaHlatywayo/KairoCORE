<?php

namespace App\Services\Csv;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryItem;

class InventoryItemCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'name' => [
                'label' => __('Item Name'),
                'required' => true,
                'guesses' => ['Item Name', 'Name', 'Product'],
                'example' => 'A4 Copy Paper',
            ],
            'sku' => [
                'label' => __('SKU'),
                'required' => true,
                'guesses' => ['SKU', 'SKU Reference', 'Item Code'],
                'example' => 'SKU-2026-1024',
            ],
            'barcode' => [
                'label' => __('Barcode'),
                'required' => false,
                'guesses' => ['Barcode', 'Barcode Tracking Code', 'Bar Code'],
                'example' => '6001234567890',
            ],
            'category' => [
                'label' => __('Category'),
                'required' => true,
                'guesses' => ['Category', 'Category Name', 'Item Category'],
                'example' => 'Stationery',
            ],
            'item_type' => [
                'label' => __('Item Type'),
                'required' => true,
                'guesses' => ['Item Type', 'Type', 'Class'],
                'example' => 'consumable',
                'default' => 'consumable',
                'in' => ['consumable', 'returnable', 'fixed_asset'],
            ],
            'unit_of_measure' => [
                'label' => __('Unit of Measure'),
                'required' => true,
                'guesses' => ['Unit of Measure', 'UOM', 'Unit'],
                'example' => 'reams',
                'default' => 'pieces',
            ],
            'reorder_level' => [
                'label' => __('Reorder Level'),
                'required' => false,
                'guesses' => ['Reorder Level', 'Low Stock Warning Threshold', 'Re-Order Level'],
                'example' => '10',
                'default' => '10',
            ],
            'current_quantity' => [
                'label' => __('Opening Quantity'),
                'required' => false,
                'guesses' => ['Opening Quantity', 'Current Quantity', 'Quantity on Hand', 'Qty'],
                'example' => '50',
                'default' => '0',
            ],
            'average_unit_cost' => [
                'label' => __('Unit Cost'),
                'required' => false,
                'guesses' => ['Unit Cost', 'Average Unit Cost', 'Cost'],
                'example' => '12.50',
                'default' => '0',
            ],
            'is_saleable' => [
                'label' => __('Is Saleable'),
                'required' => false,
                'guesses' => ['Is Saleable', 'Saleable'],
                'example' => 'no',
                'default' => 'no',
                'in' => ['yes', 'no', 'true', 'false', '1', '0'],
            ],
            'sale_price' => [
                'label' => __('Sale Price'),
                'required' => false,
                'guesses' => ['Sale Price', 'Price'],
                'example' => '18.00',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'SKU', 'Item Name', 'Barcode', 'Category', 'Item Type',
            'Unit of Measure', 'Reorder Level', 'Qty on Hand', 'Average Unit Cost',
            'Is Saleable', 'Sale Price',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = InventoryItem::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with('category')
            ->orderBy('id');

        $lastId = 0;

        do {
            $items = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($items->isEmpty()) {
                break;
            }

            foreach ($items as $item) {
                yield [
                    $item->sku,
                    $item->name,
                    $item->barcode,
                    $item->category?->name,
                    $item->item_type,
                    $item->unit_of_measure,
                    $item->reorder_level,
                    $item->current_quantity,
                    $item->average_unit_cost,
                    $item->is_saleable ? 'yes' : 'no',
                    $item->sale_price,
                ];
            }

            $lastId = $items->last()->id;
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

        $categories = InventoryCategory::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($c): string => strtolower(trim($c->name)));

        $existingSkus = InventoryItem::withoutTenantScope()->where('school_id', $schoolId)->pluck('sku')
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

            $errors = static::validateAndNormalize($data, $categories, $existingSkus);

            if (! empty($errors)) {
                $failures[] = ['row' => $rowNumber, 'errors' => $errors, 'data' => $data];
                $onProgress !== null && $onProgress($processed, $total, true, $errors);

                continue;
            }

            try {
                DB::transaction(function () use (&$existingSkus, $data, $schoolId) {
                    InventoryItem::create([
                        'school_id' => $schoolId,
                        'category_id' => $data['_category']->id,
                        'name' => $data['name'],
                        'sku' => $data['sku'],
                        'barcode' => $data['barcode'] !== '' ? $data['barcode'] : null,
                        'item_type' => $data['item_type'],
                        'unit_of_measure' => $data['unit_of_measure'],
                        'reorder_level' => (int) $data['reorder_level'],
                        'current_quantity' => (int) $data['current_quantity'],
                        'average_unit_cost' => (float) $data['average_unit_cost'],
                        'is_saleable' => $data['is_saleable'],
                        'sale_price' => $data['sale_price'] !== '' ? (float) $data['sale_price'] : null,
                    ]);

                    if (filled($data['sku'])) {
                        $existingSkus[strtolower(trim($data['sku']))] = true;
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
        Collection $categories,
        Collection $existingSkus,
    ): array {
        $errors = [];

        foreach (['name', 'sku'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        $sku = strtolower(trim($data['sku'] ?? ''));
        if ($sku !== '' && isset($existingSkus[$sku])) {
            $errors[] = 'SKU ['.$data['sku'].'] already exists for another item in this school.';
        }

        $data['category'] = trim($data['category'] ?? '');
        if ($data['category'] === '') {
            $errors[] = 'Category is required (column empty or not mapped).';
        }

        $data['item_type'] = strtolower(trim($data['item_type'] ?? ''));
        if (! in_array($data['item_type'], ['consumable', 'returnable', 'fixed_asset'], true)) {
            $errors[] = 'Item Type must be one of: consumable, returnable, fixed_asset.';
        }

        $data['unit_of_measure'] = trim($data['unit_of_measure'] ?? '');
        if ($data['unit_of_measure'] === '') {
            $errors[] = 'Unit of Measure is required (column empty or not mapped).';
        }

        foreach (['reorder_level', 'current_quantity', 'average_unit_cost', 'sale_price'] as $numericField) {
            $raw = trim($data[$numericField] ?? '');

            if ($raw === '') {
                continue;
            }

            if (! is_numeric($raw)) {
                $errors[] = static::columns()[$numericField]['label'].' ['.$raw.'] must be a number.';
            }
        }

        if (empty($errors) && $data['category'] !== '') {
            $category = $categories[strtolower($data['category'])] ?? null;

            if (! $category) {
                $errors[] = 'Category ['.$data['category'].'] was not found in this school. Available categories: '.($categories->pluck('name')->implode(', ') ?: 'none').'.';
            } else {
                $data['_category'] = $category;
            }
        }

        $saleable = strtolower(trim($data['is_saleable'] ?? ''));
        $data['is_saleable'] = in_array($saleable, ['yes', 'true', '1'], true);

        return $errors;
    }
}
