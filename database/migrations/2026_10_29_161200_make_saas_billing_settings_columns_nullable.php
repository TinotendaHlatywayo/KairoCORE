<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('saas_billing_settings', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->change();
            $table->string('bank_account_name')->nullable()->change();
            $table->string('bank_account_number')->nullable()->change();
            $table->string('bank_branch_code')->nullable()->change();
            $table->string('bank_swift_code')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saas_billing_settings', function (Blueprint $table) {
            $table->string('bank_name')->nullable(false)->change();
            $table->string('bank_account_name')->nullable(false)->change();
            $table->string('bank_account_number')->nullable(false)->change();
            $table->string('bank_branch_code')->nullable(false)->change();
            $table->string('bank_swift_code')->nullable(false)->change();
        });
    }
};
