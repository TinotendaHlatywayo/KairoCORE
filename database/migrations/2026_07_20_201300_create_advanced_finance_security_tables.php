<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Add lock mechanism to Invoices
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('invoices', 'is_locked')) {
                    $table->boolean('is_locked')->default(false)->after('status');
                }
            });
        }

        // 2. Add status tracking to Payments
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (! Schema::hasColumn('payments', 'is_reversed')) {
                    $table->boolean('is_reversed')->default(false)->after('payment_method');
                    $table->string('reversal_reason')->nullable()->after('is_reversed');
                    $table->foreignId('reversed_by_id')->nullable()->constrained('users')->onDelete('set null')->after('reversal_reason');
                }
            });
        }

        // 3. Create Immutable Finance Audits Table
        Schema::create('finance_auditing_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // The Accountant / Bursar
            $table->string('action'); // create_invoice, reverse_payment, void_invoice
            $table->string('model_type'); // Invoice, Payment
            $table->unsignedBigInteger('model_id');
            $table->json('payload_before')->nullable();
            $table->json('payload_after')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        // 4. Create Parent Payment Plans Registry Table
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->decimal('proposed_installments_count', 3, 0);
            $table->decimal('installment_amount', 15, 2);
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('parent_notes')->nullable();
            $table->text('admin_feedback')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plans');
        Schema::dropIfExists('finance_auditing_trails');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['is_reversed', 'reversal_reason', 'reversed_by_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });
    }
};
