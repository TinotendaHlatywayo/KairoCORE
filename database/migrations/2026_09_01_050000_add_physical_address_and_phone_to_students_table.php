<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'physical_address')) {
                $table->text('physical_address')->nullable()->after('parent_email');
            }
            if (!Schema::hasColumn('students', 'phone')) {
                $table->string('phone')->nullable()->after('physical_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('students', 'physical_address')) {
                $table->dropColumn('physical_address');
            }
        });
    }
};
