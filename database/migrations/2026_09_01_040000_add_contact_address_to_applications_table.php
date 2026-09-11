<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'physical_address')) {
                $table->text('physical_address')->nullable()->after('email');
            }
            if (!Schema::hasColumn('applications', 'phone')) {
                $table->string('phone')->nullable()->after('physical_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('applications', 'physical_address')) {
                $table->dropColumn('physical_address');
            }
        });
    }
};
