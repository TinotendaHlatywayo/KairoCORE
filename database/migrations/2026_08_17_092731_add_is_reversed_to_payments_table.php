<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'is_reversed')) {
                $table->boolean('is_reversed')->default(false)->after('payment_method');
                $table->string('reversal_reason')->nullable()->after('is_reversed');
                $table->foreignId('reversed_by_id')->nullable()->constrained('users')->onDelete('set null')->after('reversal_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['is_reversed', 'reversal_reason', 'reversed_by_id']);
        });
    }
};
