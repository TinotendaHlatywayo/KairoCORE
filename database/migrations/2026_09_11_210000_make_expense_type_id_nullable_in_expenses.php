<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->table('expenses', function (Blueprint $table) {
            $table->foreignId('expense_type_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('expenses', function (Blueprint $table) {
            $table->foreignId('expense_type_id')->nullable(false)->change();
        });
    }
};