<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->integer('lead_time_days')->default(7);
            $table->timestamps();

            $table->unique(['school_id', 'name'], 'uq_inventory_suppliers_name');
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('inventory_supplier_id')->nullable()->constrained('inventory_suppliers')->onDelete('set null');
            $table->string('name');
            $table->string('sku');
            $table->string('barcode');
            $table->string('serial_number')->nullable();
            $table->enum('category', [
                'laboratory_equipment', 'ict_equipment', 'furniture', 'sports_equipment',
                'cleaning_supplies', 'stationery', 'kitchen_inventory', 'maintenance_supplies',
                'electrical_equipment', 'musical_instruments', 'office_supplies', 'uniform_stock', 'medical_supplies',
            ]);
            $table->string('storage_location')->nullable();
            $table->string('unit_of_measure')->default('units');
            $table->integer('minimum_stock')->default(0);
            $table->integer('maximum_stock')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->decimal('purchase_cost', 10, 2)->default(0.00);
            $table->decimal('current_value', 10, 2)->default(0.00);
            $table->decimal('depreciation_rate', 5, 2)->default(0.00);
            $table->date('warranty_expiry')->nullable();
            $table->enum('status', ['active', 'maintenance', 'disposed', 'low_stock'])->default('active');
            $table->integer('quantity_on_hand')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'sku'], 'uq_inventory_items_sku');
            $table->unique(['school_id', 'barcode'], 'uq_inventory_items_barcode');
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('transaction_type', ['purchase', 'issue', 'return', 'adjustment', 'transfer', 'write_off', 'audit']);
            $table->integer('quantity');
            $table->integer('balance_after');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_procurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('requested_by_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->constrained('inventory_suppliers')->onDelete('set null');
            $table->enum('type', ['request', 'order', 'receipt', 'invoice']);
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'ordered', 'received', 'cancelled'])->default('draft');
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->json('items_payload');
            $table->string('reference_number');
            $table->timestamps();

            $table->unique(['school_id', 'reference_number'], 'uq_inventory_procurements_ref');
        });

        Schema::create('inventory_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->string('performed_by');
            $table->date('maintenance_date');
            $table->date('next_due_date')->nullable();
            $table->decimal('cost', 10, 2)->default(0.00);
            $table->text('description');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'failed'])->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_maintenance_logs');
        Schema::dropIfExists('inventory_procurements');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_suppliers');
    }
};
