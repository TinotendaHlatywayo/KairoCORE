<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students') && !Schema::hasColumn('students', 'credit_balance')) {
            Schema::table('students', function (Blueprint $table) {
                $table->decimal('credit_balance', 10, 2)->default(0.00)->after('boarding_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'credit_balance')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('credit_balance');
            });
        }
    }
};
