<?php

namespace App\Services\Csv;

use App\Models\User;
use Modules\Inventory\Models\GoodsReceivedNote;
use Modules\Inventory\Models\ProcurementOrder;

class GoodsReceivedCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'purchase_order' => [
                'label' => __('Purchase Order No'),
                'required' => true,
                'guesses' => ['Purchase Order', 'Purchase Order No', 'PO Number', 'Order Number'],
                'example' => 'PO-2026-0001',
            ],
            'grn_number' => [
                'label' => __('GRN Number'),
                'required' => true,
                'guesses' => ['GRN Number', 'GRN'],
                'example' => 'GRN-2026-0001',
            ],
            'received_date' => [
                'label' => __('Received Date'),
                'required' => true,
                'guesses' => ['Received Date', 'Date Received'],
                'example' => '2026-07-02',
                'date' => true,
            ],
            'received_by' => [
                'label' => __('Received By'),
                'required' => false,
                'guesses' => ['Received By', 'Receiver'],
                'example' => 'Tendai Mutasa',
            ],
            'delivery_challan_number' => [
                'label' => __('Delivery Challan No'),
                'required' => false,
                'guesses' => ['Delivery Challan Number', 'Delivery Challan No', 'Challan'],
                'example' => 'DC-8821',
            ],
            'supplier_invoice_number' => [
                'label' => __('Supplier Invoice No'),
                'required' => false,
                'guesses' => ['Supplier Invoice Number', 'Supplier Invoice No', 'Invoice Number'],
                'example' => 'INV-1044',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Purchase Order No', 'GRN Number', 'Received Date', 'Received By',
            'Delivery Challan No', 'Supplier Invoice No',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = GoodsReceivedNote::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['procurementOrder', 'receivedBy'])
            ->orderBy('id');

        $lastId = 0;

        do {
            $notes = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($notes->isEmpty()) {
                break;
            }

            foreach ($notes as $note) {
                yield [
                    $note->procurementOrder?->order_number,
                    $note->grn_number,
                    optional($note->received_date)->format('Y-m-d'),
                    $note->receivedBy?->name,
                    $note->delivery_challan_number,
                    $note->supplier_invoice_number,
                ];
            }

            $lastId = $notes->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $lookups = [
            'procurementOrders' => ProcurementOrder::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn ($o): string => strtolower(trim($o->order_number))),
            'existingGrnNumbers' => GoodsReceivedNote::withoutTenantScope()->where('school_id', $schoolId)->pluck('grn_number')
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

        foreach (['purchase_order', 'grn_number', 'received_date'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        $grn = strtolower($data['grn_number'] ?? '');
        if ($grn !== '' && isset($lookups['existingGrnNumbers'][$grn])) {
            $errors[] = 'GRN Number ['.$data['grn_number'].'] already exists for another note in this school.';
        }

        $date = trim($data['received_date'] ?? '');
        if ($date !== '') {
            $parsed = static::toDate($date);

            if ($parsed === null) {
                $errors[] = 'Received Date ['.$date.'] is not a valid date. Use YYYY-MM-DD.';
            } else {
                $data['received_date'] = $parsed;
            }
        }

        $orderNumber = trim($data['purchase_order'] ?? '');
        $data['_procurementOrder'] = $orderNumber !== '' ? ($lookups['procurementOrders'][strtolower($orderNumber)] ?? null) : null;
        if ($orderNumber !== '' && ! $data['_procurementOrder']) {
            $errors[] = 'Purchase Order ['.$orderNumber.'] was not found in this school. Available orders: '.($lookups['procurementOrders']->pluck('order_number')->implode(', ') ?: 'none').'.';
        }

        $receivedBy = trim($data['received_by'] ?? '');
        $data['_receivedBy'] = $receivedBy !== '' ? ($lookups['usersByName'][strtolower($receivedBy)] ?? null) : null;
        if ($receivedBy !== '' && ! $data['_receivedBy']) {
            $errors[] = 'Received By ['.$receivedBy.'] does not match any user account in this school.';
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        GoodsReceivedNote::create([
            'school_id' => $schoolId,
            'procurement_order_id' => $data['_procurementOrder']->id,
            'grn_number' => $data['grn_number'],
            'received_date' => $data['received_date'],
            'received_by_id' => $data['_receivedBy']?->id,
            'delivery_challan_number' => $data['delivery_challan_number'] !== '' ? $data['delivery_challan_number'] : null,
            'supplier_invoice_number' => $data['supplier_invoice_number'] !== '' ? $data['supplier_invoice_number'] : null,
        ]);

        $lookups['existingGrnNumbers'][strtolower(trim($data['grn_number']))] = true;
    }
}
