<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'custom_fields')) {
            Schema::table('students', function (Blueprint $table) {
                $table->json('custom_fields')->nullable();
            });
        }

        if (Schema::hasTable('employees') && ! Schema::hasColumn('employees', 'custom_fields')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->json('custom_fields')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'custom_fields')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('custom_fields');
            });
        }

        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'custom_fields')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('custom_fields');
            });
        }
    }
};
