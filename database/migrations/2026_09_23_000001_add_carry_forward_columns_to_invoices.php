<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'carried_forward_at')) {
                $table->timestamp('carried_forward_at')->nullable()->after('is_locked');
            }
            if (! Schema::hasColumn('invoices', 'carried_forward_to_invoice_id')) {
                $table->unsignedBigInteger('carried_forward_to_invoice_id')->nullable()->after('carried_forward_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'carried_forward_to_invoice_id')) {
                $table->dropColumn('carried_forward_to_invoice_id');
            }
            if (Schema::hasColumn('invoices', 'carried_forward_at')) {
                $table->dropColumn('carried_forward_at');
            }
        });
    }
};
