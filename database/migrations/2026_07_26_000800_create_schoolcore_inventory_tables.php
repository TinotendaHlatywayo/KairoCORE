<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. SURGICAL CLEANUP BLOCK: Disable keys and clean old modular inventory tables
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('inventory_stock_adjustment_items');
        Schema::dropIfExists('inventory_stock_adjustments');
        Schema::dropIfExists('asset_maintenance_logs');
        Schema::dropIfExists('goods_received_items');
        Schema::dropIfExists('goods_received_notes');
        Schema::dropIfExists('procurement_order_items');
        Schema::dropIfExists('procurement_orders');
        Schema::dropIfExists('procurement_request_items');
        Schema::dropIfExists('procurement_requests');
        Schema::dropIfExists('inventory_suppliers');
        Schema::dropIfExists('inventory_issuances');
        Schema::dropIfExists('inventory_stock_movements');
        Schema::dropIfExists('depreciation_schedules');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_locations');
        Schema::dropIfExists('inventory_categories');
        Schema::dropIfExists('inventory_settings');

        Schema::enableForeignKeyConstraints();

        // 2. REBUILD MODULE: Create clean, normalized structures

        // Inventory Settings
        Schema::create('inventory_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->json('active_modules');
            $table->string('valuation_method')->default('moving_average');
            $table->json('low_stock_notification_roles')->nullable();
            $table->boolean('auto_bill_issued_uniforms')->default(false);
            $table->timestamps();

            $table->unique(['school_id'], 'uq_inv_settings_school');
        });

        // Taxonomy Categories
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('inventory_categories')->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'parent_id', 'name'], 'uq_inv_cat_name');
        });

        // Recursive Storage Locations
        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('inventory_locations')->onDelete('set null');
            $table->string('name');
            $table->string('code');
            $table->string('type')->default('general'); // general, hostel, canteen, laboratory, ict, sports, clinic
            $table->foreignId('responsible_officer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('temperature_sensitive')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'code'], 'uq_inv_loc_code');
        });

        // Item Master
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('inventory_categories')->onDelete('cascade');
            $table->string('sku');
            $table->string('barcode')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('item_type'); // consumable, returnable, fixed_asset
            $table->string('unit_of_measure')->default('pieces');
            $table->integer('reorder_level')->unsigned()->default(10);
            $table->integer('current_quantity')->default(0);
            $table->decimal('average_unit_cost', 15, 4)->default(0.0000);
            $table->boolean('is_saleable')->default(false);
            $table->decimal('sale_price', 15, 2)->default(0.00);
            $table->json('meta_data')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'sku'], 'uq_inv_item_sku');
        });

        // Batch & Expiry Management
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->string('batch_number');
            $table->date('expiry_date')->nullable();
            $table->integer('initial_quantity');
            $table->integer('current_quantity');
            $table->decimal('unit_cost', 15, 4)->default(0.0000);
            $table->timestamps();

            $table->unique(['school_id', 'inventory_item_id', 'batch_number'], 'uq_inv_batch_num');
        });

        // Fixed Assets Registry
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->string('asset_number');
            $table->string('serial_number')->nullable();
            $table->date('acquisition_date');
            $table->decimal('purchase_cost', 15, 2);
            $table->decimal('salvage_value', 15, 2)->default(0.00);
            $table->integer('useful_life_years')->unsigned();
            $table->string('depreciation_method')->default('straight_line');
            $table->decimal('current_value', 15, 2);
            $table->date('warranty_expiry')->nullable();
            $table->string('funding_source')->default('school_funds');
            $table->string('insurance_policy_number')->nullable();
            $table->foreignId('assigned_location_id')->nullable()->constrained('inventory_locations')->onDelete('set null');
            $table->foreignId('custodian_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['school_id', 'asset_number'], 'uq_fixed_asset_num');
        });

        // Depreciation schedules
        Schema::create('depreciation_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->onDelete('cascade');
            $table->integer('fiscal_year');
            $table->decimal('depreciation_amount', 15, 2);
            $table->decimal('book_value_start', 15, 2);
            $table->decimal('book_value_end', 15, 2);
            $table->boolean('is_posted')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'fixed_asset_id', 'fiscal_year'], 'uq_depr_asset_year');
        });

        // Stock Movement Ledger
        Schema::create('inventory_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignId('inventory_location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->onDelete('set null');
            $table->string('type');
            $table->integer('quantity');
            $table->decimal('unit_cost', 15, 4)->default(0.0000);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('remarks')->nullable();
            $table->foreignId('performed_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // Unified Stock Issuance Ledger
        Schema::create('inventory_issuances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignId('inventory_location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->onDelete('set null');
            $table->string('issued_to_type');
            $table->unsignedBigInteger('issued_to_id');
            $table->integer('quantity');
            $table->boolean('is_returnable')->default(false);
            $table->date('expected_return_date')->nullable();
            $table->date('actual_return_date')->nullable();
            $table->string('condition_on_issue')->default('good');
            $table->string('condition_on_return')->nullable();
            $table->string('status')->default('issued');
            $table->string('remarks')->nullable();
            $table->foreignId('issued_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // Suppliers
        Schema::create('inventory_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('physical_address')->nullable();
            $table->string('tax_number')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'name'], 'uq_inv_sup_name');
        });

        // Procurement Phase: Purchase Requests
        Schema::create('procurement_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('request_number');
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->string('department_id')->nullable();
            $table->string('status')->default('draft');
            $table->string('urgency')->default('medium');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'request_number'], 'uq_proc_req_num');
        });

        Schema::create('procurement_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_request_id')->constrained('procurement_requests')->onDelete('cascade');
            $table->string('item_name');
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->onDelete('set null');
            $table->integer('quantity');
            $table->decimal('estimated_unit_cost', 15, 2)->default(0.00);
            $table->text('specifications')->nullable();
            $table->timestamps();
        });

        // Procurement Phase: Local Purchase Orders (LPO)
        Schema::create('procurement_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('procurement_request_id')->nullable()->constrained('procurement_requests')->onDelete('set null');
            $table->foreignId('supplier_id')->constrained('inventory_suppliers')->onDelete('cascade');
            $table->string('order_number');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['school_id', 'order_number'], 'uq_proc_ord_num');
        });

        Schema::create('procurement_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_order_id')->constrained('procurement_orders')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->integer('quantity_ordered');
            $table->integer('quantity_received')->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0.0000);
            $table->timestamps();
        });

        // Procurement Phase: Goods Received Notes (GRN)
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('procurement_order_id')->constrained('procurement_orders')->onDelete('cascade');
            $table->string('grn_number');
            $table->date('received_date');
            $table->foreignId('received_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('delivery_challan_number')->nullable();
            $table->string('supplier_invoice_number')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'grn_number'], 'uq_grn_num');
        });

        Schema::create('goods_received_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_received_note_id')->constrained('goods_received_notes')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->integer('quantity_accepted');
            $table->integer('quantity_rejected')->default(0);
            $table->string('rejection_reason')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        // Maintenance Logs
        Schema::create('asset_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->onDelete('cascade');
            $table->string('title');
            $table->string('type')->default('preventive');
            $table->string('schedule_type')->default('one_time');
            $table->integer('recurrence_interval_days')->nullable();
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->decimal('cost', 15, 2)->default(0.00);
            $table->string('performed_by')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Physical Stocktakes
        Schema::create('inventory_stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('inventory_location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->string('adjustment_number');
            $table->string('status')->default('draft');
            $table->date('conducted_date');
            $table->foreignId('conducted_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['school_id', 'adjustment_number'], 'uq_inv_adj_num');
        });

        Schema::create('inventory_stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained('inventory_stock_adjustments')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->integer('system_quantity');
            $table->integer('physical_quantity');
            $table->integer('variance');
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('inventory_stock_adjustment_items');
        Schema::dropIfExists('inventory_stock_adjustments');
        Schema::dropIfExists('asset_maintenance_logs');
        Schema::dropIfExists('goods_received_items');
        Schema::dropIfExists('goods_received_notes');
        Schema::dropIfExists('procurement_order_items');
        Schema::dropIfExists('procurement_orders');
        Schema::dropIfExists('procurement_request_items');
        Schema::dropIfExists('procurement_requests');
        Schema::dropIfExists('inventory_suppliers');
        Schema::dropIfExists('inventory_issuances');
        Schema::dropIfExists('inventory_stock_movements');
        Schema::dropIfExists('depreciation_schedules');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_locations');
        Schema::dropIfExists('inventory_categories');
        Schema::dropIfExists('inventory_settings');
        Schema::enableForeignKeyConstraints();
    }
};
