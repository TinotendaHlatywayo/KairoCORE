<?php

namespace App\Services\Csv;

use Modules\Inventory\Models\InventorySupplier;
use Modules\Inventory\Models\ProcurementOrder;
use Modules\Inventory\Models\ProcurementRequest;

class PurchaseOrderCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'request' => [
                'label' => __('Request Number'),
                'required' => false,
                'guesses' => ['Request', 'Request Number', 'PR Number'],
                'example' => 'PR-2026-0001',
            ],
            'supplier' => [
                'label' => __('Supplier Name'),
                'required' => true,
                'guesses' => ['Supplier', 'Supplier Name', 'Vendor'],
                'example' => 'Gonville Stationers',
            ],
            'order_number' => [
                'label' => __('Order Number'),
                'required' => true,
                'guesses' => ['Order Number', 'PO Number'],
                'example' => 'PO-2026-0001',
            ],
            'order_date' => [
                'label' => __('Order Date'),
                'required' => true,
                'guesses' => ['Order Date', 'Date'],
                'example' => '2026-07-01',
                'date' => true,
            ],
            'expected_delivery_date' => [
                'label' => __('Expected Delivery'),
                'required' => false,
                'guesses' => ['Expected Delivery', 'Delivery Date'],
                'example' => '2026-07-20',
                'date' => true,
            ],
            'status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status'],
                'example' => 'draft',
                'default' => 'draft',
                'in' => ['draft', 'sent', 'partially_received', 'completed', 'cancelled'],
            ],
            'total_amount' => [
                'label' => __('Total Amount'),
                'required' => false,
                'guesses' => ['Total Amount', 'Amount'],
                'example' => '0',
                'default' => '0',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Request Number', 'Supplier Name', 'Order Number', 'Order Date',
            'Expected Delivery', 'Status', 'Total Amount',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = ProcurementOrder::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['request', 'supplier'])
            ->orderBy('id');

        $lastId = 0;

        do {
            $orders = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($orders->isEmpty()) {
                break;
            }

            foreach ($orders as $order) {
                yield [
                    $order->request?->request_number,
                    $order->supplier?->name,
                    $order->order_number,
                    optional($order->order_date)->format('Y-m-d'),
                    optional($order->expected_delivery_date)->format('Y-m-d'),
                    $order->status,
                    $order->total_amount,
                ];
            }

            $lastId = $orders->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $lookups = [
            'requests' => ProcurementRequest::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn ($r): string => strtolower(trim($r->request_number))),
            'suppliers' => InventorySupplier::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn ($s): string => strtolower(trim($s->name))),
            'existingOrderNumbers' => ProcurementOrder::withoutTenantScope()->where('school_id', $schoolId)->pluck('order_number')
                ->map(fn ($v): string => strtolower(trim((string) $v)))->flip(),
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

        foreach (['supplier', 'order_number', 'order_date'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        $orderNumber = strtolower(trim($data['order_number'] ?? ''));
        if ($orderNumber !== '' && isset($lookups['existingOrderNumbers'][$orderNumber])) {
            $errors[] = 'Order Number ['.$data['order_number'].'] already exists for another order in this school.';
        }

        foreach (['order_date', 'expected_delivery_date'] as $dateField) {
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

        if ($data['order_date'] !== '' && $data['expected_delivery_date'] !== ''
            && $data['expected_delivery_date'] < $data['order_date']) {
            $errors[] = 'Expected Delivery ['.$data['expected_delivery_date'].'] must be on or after Order Date ['.$data['order_date'].'].';
        }

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] !== '' && ! in_array($data['status'], ['draft', 'sent', 'partially_received', 'completed', 'cancelled'], true)) {
            $errors[] = 'Status must be one of: draft, sent, partially_received, completed, cancelled.';
        }

        $totalAmount = trim($data['total_amount'] ?? '');
        if ($totalAmount !== '' && ! is_numeric($totalAmount)) {
            $errors[] = 'Total Amount ['.$totalAmount.'] must be a number.';
        }

        $requestNumber = trim($data['request'] ?? '');
        $data['_request'] = $requestNumber !== '' ? ($lookups['requests'][strtolower($requestNumber)] ?? null) : null;
        if ($requestNumber !== '' && ! $data['_request']) {
            $errors[] = 'Request Number ['.$requestNumber.'] was not found in this school. Available requests: '.($lookups['requests']->pluck('request_number')->implode(', ') ?: 'none').'.';
        }

        $supplierName = trim($data['supplier'] ?? '');
        $data['_supplier'] = $supplierName !== '' ? ($lookups['suppliers'][strtolower($supplierName)] ?? null) : null;
        if ($supplierName !== '' && ! $data['_supplier']) {
            $errors[] = 'Supplier ['.$supplierName.'] was not found in this school. Available suppliers: '.($lookups['suppliers']->pluck('name')->implode(', ') ?: 'none').'.';
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        ProcurementOrder::create([
            'school_id' => $schoolId,
            'procurement_request_id' => $data['_request']?->id,
            'supplier_id' => $data['_supplier']->id,
            'order_number' => $data['order_number'],
            'order_date' => $data['order_date'],
            'expected_delivery_date' => $data['expected_delivery_date'] !== '' ? $data['expected_delivery_date'] : null,
            'status' => $data['status'] !== '' ? $data['status'] : 'draft',
            'total_amount' => $data['total_amount'] !== '' ? (float) $data['total_amount'] : 0,
        ]);

        $lookups['existingOrderNumbers'][strtolower(trim($data['order_number']))] = true;
    }
}
