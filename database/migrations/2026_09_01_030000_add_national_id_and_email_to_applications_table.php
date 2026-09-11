<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'national_id')) {
                $table->string('national_id')->nullable()->after('last_name');
            }
            if (!Schema::hasColumn('applications', 'email')) {
                $table->string('email')->nullable()->after('parent_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'national_id')) {
                $table->dropColumn('national_id');
            }
            if (Schema::hasColumn('applications', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
