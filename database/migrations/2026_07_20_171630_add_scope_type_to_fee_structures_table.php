<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('fee_structures', function (Blueprint $table) {
            // Adds scope_type column to fee_structures table
            if (! Schema::hasColumn('fee_structures', 'scope_type')) {
                $table->string('scope_type')->default('single')->after('fee_category_id');
            }
            // Makes course_id nullable to support school-wide and grouped scopes
            $table->foreignId('course_id')->nullable()->change();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('fee_structures', function (Blueprint $table) {
            if (Schema::hasColumn('fee_structures', 'scope_type')) {
                $table->dropColumn('scope_type');
            }
            $table->foreignId('course_id')->nullable(false)->change();
        });

        Schema::enableForeignKeyConstraints();
    }
};
