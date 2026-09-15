<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('billing_period', 16)->nullable()->after('invoice_number')
                ->comment('Month bucket for monthly billing, e.g. 2026-03. Null for termly.');
            $table->index(['school_id', 'student_id', 'term_id', 'billing_period']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'student_id', 'term_id', 'billing_period']);
            $table->dropColumn('billing_period');
        });
    }
};
