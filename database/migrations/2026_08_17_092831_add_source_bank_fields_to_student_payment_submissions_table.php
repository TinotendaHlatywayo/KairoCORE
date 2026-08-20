<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_payment_submissions', function (Blueprint $table) {
            $table->string('source_bank_name')->nullable()->after('bank_name');
            $table->string('source_account_number')->nullable()->after('source_bank_name');
            $table->unsignedBigInteger('destination_bank_account_id')->nullable()->after('source_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('student_payment_submissions', function (Blueprint $table) {
            $table->dropColumn(['source_bank_name', 'source_account_number', 'destination_bank_account_id']);
        });
    }
};
