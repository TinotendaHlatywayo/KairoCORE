<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            // Adds relational tracking and audit-ready detail strings
            $table->foreignId('fee_waiver_id')->nullable()->after('term_id')->constrained('fee_waivers')->onDelete('set null');
            $table->string('waiver_details')->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['fee_waiver_id']);
            $table->dropColumn(['fee_waiver_id', 'waiver_details']);
        });
    }
};
