<?php

namespace Modules\Reports\DataSources\Providers;

use Modules\Reports\DataSources\AbstractDatasetProvider;

class InventoryProvider extends AbstractDatasetProvider
{
    public function module(): string
    {
        return __('Inventory & Assets');
    }

    public function datasets(): array
    {
        return [
            $this->d('inventory.item', __('Inventory Items'), 'inventory_items', [
                $this->f('sku', __('SKU Code')),
                $this->f('barcode', __('Barcode')),
                $this->f('name', __('Item Name')),
                $this->f('item_type', __('Item Type')),
                $this->f('unit_of_measure', __('UOM')),
                $this->f('reorder_level', __('Reorder Level'), 'integer'),
                $this->f('current_quantity', __('Quantity on Hand'), 'integer'),
                $this->f('average_unit_cost', __('Avg Unit Cost'), 'currency'),
                $this->money('stock_value', __('Stock Value'), '(inventory_item.current_quantity * inventory_item.average_unit_cost)'),
                $this->f('is_saleable', __('Saleable'), 'boolean'),
                $this->f('sale_price', __('Sale Price'), 'currency'),
                $this->f('category_name', __('Category'), 'string', 'inventory_item_cat.name'),
                $this->f('low_stock', __('Low Stock Alert'), 'boolean', 'CASE WHEN inventory_item.current_quantity <= inventory_item.reorder_level THEN 1 ELSE 0 END'),
            ], [
                'description' => __('Current stock levels with valuation and reorder alerts.'),
                'autoJoins' => [
                    ['alias' => 'inventory_item_cat', 'table' => 'inventory_categories', 'type' => 'left', 'on' => [['inventory_item_cat.id', 'inventory_item.category_id']]],
                ],
                'connections' => [
                    $this->connect('inventory.transaction', 'inventory_item.id', 'inventory_transaction.inventory_item_id'),
                    $this->connect('assets.fixed_asset', 'inventory_item.id', 'fixed_asset.inventory_item_id'),
                    $this->connect('procurement.item', 'inventory_item.id', 'procurement_item.inventory_item_id'),
                ],
                'filters' => [
                    ['key' => 'item_type', 'label' => __('Item Type'), 'type' => 'select', 'options' => ['consumable', 'durable', 'equipment', 'stationery']],
                    ['key' => 'low_stock', 'label' => __('Low Stock Only'), 'type' => 'select', 'options' => [1 => 'Low stock', 0 => 'In stock']],
                ],
            ]),

            $this->d('inventory.transaction', __('Inventory Movements'), 'inventory_transactions', [
                $this->f('transaction_type', __('Transaction Type')),
                $this->f('quantity', __('Quantity'), 'integer'),
                $this->f('balance_after', __('Balance After'), 'integer'),
                $this->f('notes', __('Notes')),
                $this->f('created_at', __('Transaction Date'), 'datetime'),
                $this->f('item_name', __('Item Name'), 'string', 'inventory_transaction_item.name'),
                $this->f('item_sku', __('SKU'), 'string', 'inventory_transaction_item.sku'),
            ], [
                'description' => __('Stock movement ledger.'),
                'autoJoins' => [
                    ['alias' => 'inventory_transaction_item', 'table' => 'inventory_items', 'type' => 'left', 'on' => [['inventory_transaction_item.id', 'inventory_transaction.inventory_item_id']]],
                ],
                'connections' => [
                    $this->connect('inventory.item', 'inventory_transaction.inventory_item_id', 'inventory_item.id'),
                ],
                'filters' => [
                    ['key' => 'transaction_type', 'label' => __('Type'), 'type' => 'select', 'options' => ['receipt', 'issue', 'adjustment', 'return', 'transfer']],
                ],
            ]),

            $this->d('inventory.supplier', __('Suppliers'), 'inventory_suppliers', [
                $this->f('name', __('Supplier Name')),
                $this->f('contact_person', __('Contact Person')),
                $this->f('phone', __('Phone')),
                $this->f('email', __('Email')),
                $this->f('tax_number', __('Tax Number')),
            ], [
                'description' => __('Registered suppliers.'),
                'connections' => [
                    $this->connect('procurement.order', 'inventory_supplier.id', 'procurement_order.supplier_id'),
                ],
            ]),

            $this->d('assets.fixed_asset', __('Fixed Assets'), 'fixed_assets', [
                $this->f('asset_number', __('Asset Number')),
                $this->f('serial_number', __('Serial Number')),
                $this->f('acquisition_date', __('Acquisition Date'), 'date'),
                $this->money('purchase_cost', __('Purchase Cost'), 'fixed_asset.purchase_cost'),
                $this->money('salvage_value', __('Salvage Value'), 'fixed_asset.salvage_value'),
                $this->f('useful_life_years', __('Useful Life (yrs)'), 'integer'),
                $this->f('depreciation_method', __('Depreciation Method')),
                $this->money('current_value', __('Current Value'), 'fixed_asset.current_value'),
                $this->f('warranty_expiry', __('Warranty Expiry'), 'date'),
                $this->f('status', __('Status')),
                $this->f('item_name', __('Item Name'), 'string', 'fixed_asset_item.name'),
            ], [
                'description' => __('Fixed asset register with valuation and lifecycle.'),
                'autoJoins' => [
                    ['alias' => 'fixed_asset_item', 'table' => 'inventory_items', 'type' => 'left', 'on' => [['fixed_asset_item.id', 'fixed_asset.inventory_item_id']]],
                ],
                'connections' => [
                    $this->connect('inventory.item', 'fixed_asset.inventory_item_id', 'inventory_item.id'),
                    $this->connect('assets.maintenance', 'fixed_asset.id', 'asset_maintenance.fixed_asset_id'),
                ],
                'filters' => [
                    ['key' => 'status', 'label' => __('Status'), 'type' => 'select', 'options' => ['in_use', 'under_maintenance', 'retired', 'disposed']],
                ],
            ]),

            $this->d('assets.maintenance', __('Asset Maintenance Logs'), 'asset_maintenance_logs', [
                $this->f('title', __('Title')),
                $this->f('type', __('Type')),
                $this->f('scheduled_date', __('Scheduled Date'), 'date'),
                $this->f('completed_date', __('Completed Date'), 'date'),
                $this->money('cost', __('Maintenance Cost'), 'asset_maintenance.cost'),
                $this->f('status', __('Status')),
                $this->f('asset_number', __('Asset Number'), 'string', 'asset_maintenance_asset.asset_number'),
            ], [
                'description' => __('Preventive and corrective maintenance logs.'),
                'autoJoins' => [
                    ['alias' => 'asset_maintenance_asset', 'table' => 'fixed_assets', 'type' => 'left', 'on' => [['asset_maintenance_asset.id', 'asset_maintenance.fixed_asset_id']]],
                ],
                'connections' => [
                    $this->connect('assets.fixed_asset', 'asset_maintenance.fixed_asset_id', 'fixed_asset.id'),
                ],
            ]),

            $this->d('procurement.request', __('Procurement Requests'), 'procurement_requests', [
                $this->f('request_number', __('Request Number')),
                $this->f('urgency', __('Urgency')),
                $this->f('status', __('Status')),
                $this->f('notes', __('Notes')),
                $this->f('created_at', __('Requested At'), 'datetime'),
                $this->f('department_name', __('Department'), 'string', 'procurement_request_dept.name'),
            ], [
                'description' => __('Purchase requests with department context.'),
                'autoJoins' => [
                    ['alias' => 'procurement_request_dept', 'table' => 'departments', 'type' => 'left', 'on' => [['procurement_request_dept.id', 'procurement_request.department_id']]],
                ],
                'connections' => [
                    $this->connect('hr.department', 'procurement_request.department_id', 'hr_department.id'),
                    $this->connect('procurement.order', 'procurement_request.id', 'procurement_order.procurement_request_id'),
                ],
                'filters' => [
                    ['key' => 'status', 'label' => __('Status'), 'type' => 'select', 'options' => ['draft', 'submitted', 'approved', 'rejected']],
                ],
            ]),

            $this->d('procurement.order', __('Purchase Orders'), 'procurement_orders', [
                $this->f('order_number', __('Order Number')),
                $this->f('order_date', __('Order Date'), 'date'),
                $this->f('expected_delivery_date', __('Expected Delivery'), 'date'),
                $this->f('status', __('Status')),
                $this->money('total_amount', __('Order Total'), 'procurement_order.total_amount'),
                $this->f('supplier_name', __('Supplier'), 'string', 'procurement_order_supplier.name'),
            ], [
                'description' => __('Purchase orders with supplier context.'),
                'autoJoins' => [
                    ['alias' => 'procurement_order_supplier', 'table' => 'inventory_suppliers', 'type' => 'left', 'on' => [['procurement_order_supplier.id', 'procurement_order.supplier_id']]],
                    ['alias' => 'procurement_order_req', 'table' => 'procurement_requests', 'type' => 'left', 'on' => [['procurement_order_req.id', 'procurement_order.procurement_request_id']]],
                ],
                'connections' => [
                    $this->connect('procurement.request', 'procurement_order.procurement_request_id', 'procurement_request.id'),
                    $this->connect('inventory.supplier', 'procurement_order.supplier_id', 'inventory_supplier.id'),
                ],
                'filters' => [
                    ['key' => 'status', 'label' => __('Status'), 'type' => 'select', 'options' => ['draft', 'sent', 'partially_received', 'received', 'cancelled']],
                ],
            ]),

            $this->d('procurement.item', __('Procurement Line Items'), 'procurement_order_items', [
                $this->f('quantity_ordered', __('Quantity Ordered'), 'integer'),
                $this->f('quantity_received', __('Quantity Received'), 'integer'),
                $this->money('unit_cost', __('Unit Cost'), 'procurement_item.unit_cost'),
                $this->f('order_number', __('Order Number'), 'string', 'procurement_item_order.order_number'),
                $this->f('item_name', __('Item'), 'string', 'procurement_item_inv.name'),
            ], [
                'description' => __('Line items across purchase orders.'),
                'autoJoins' => [
                    ['alias' => 'procurement_item_order', 'table' => 'procurement_orders', 'type' => 'left', 'on' => [['procurement_item_order.id', 'procurement_item.procurement_order_id']]],
                    ['alias' => 'procurement_item_inv', 'table' => 'inventory_items', 'type' => 'left', 'on' => [['procurement_item_inv.id', 'procurement_item.inventory_item_id']]],
                ],
                'connections' => [
                    $this->connect('inventory.item', 'procurement_item.inventory_item_id', 'inventory_item.id'),
                ],
            ]),
        ];
    }
}
