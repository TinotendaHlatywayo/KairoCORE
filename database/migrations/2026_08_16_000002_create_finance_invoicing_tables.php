<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
                $table->foreignId('fee_waiver_id')->nullable()->constrained('fee_waivers')->nullOnDelete();
                $table->string('invoice_number')->unique();
                $table->string('currency', 10)->default('USD');
                $table->decimal('subtotal_amount', 14, 2)->default(0);
                $table->decimal('discount_amount', 14, 2)->default(0);
                $table->string('waiver_details')->nullable();
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->decimal('paid_amount', 14, 2)->default(0);
                $table->decimal('balance_amount', 14, 2)->default(0);
                $table->string('status', 20)->default('unpaid');
                $table->boolean('is_locked')->default(false);
                $table->date('due_date')->nullable();
                $table->string('integrity_hash')->nullable();
                $table->timestamps();

                $table->index(['school_id', 'student_id', 'status']);
            });
        }

        if (! Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->foreignId('fee_structure_id')->nullable()->constrained('fee_structures')->nullOnDelete();
                $table->string('name');
                $table->decimal('amount', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->string('receipt_number')->nullable();
                $table->string('reference_number')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('currency', 10)->default('USD');
                $table->string('payment_method', 50)->default('cash');
                $table->date('payment_date')->nullable();
                $table->timestamps();

                $table->index(['school_id', 'invoice_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
