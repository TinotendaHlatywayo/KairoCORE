<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_payment_submissions')) {
            Schema::create('student_payment_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->cascadeOnDelete();
                $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
                $table->string('gateway', 20)->default('manual'); // manual | paynow
                $table->string('reference_number')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('currency', 10)->default('USD');
                $table->date('payment_date')->nullable();
                $table->string('bank_name')->nullable();
                $table->text('notes')->nullable();
                $table->string('proof_file_path')->nullable();
                $table->string('transaction_reference')->nullable(); // Paynow poll URL
                $table->string('status', 20)->default('pending'); // pending | approved | rejected
                $table->string('rejection_reason')->nullable();
                $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['school_id', 'status']);
                $table->index(['student_id', 'invoice_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_payment_submissions');
    }
};
