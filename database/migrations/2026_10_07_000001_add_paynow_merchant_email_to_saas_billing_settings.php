<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_billing_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('saas_billing_settings', 'paynow_merchant_email')) {
                $table->string('paynow_merchant_email')->nullable()->after('paynow_integration_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('saas_billing_settings', function (Blueprint $table) {
            if (Schema::hasColumn('saas_billing_settings', 'paynow_merchant_email')) {
                $table->dropColumn('paynow_merchant_email');
            }
        });
    }
};