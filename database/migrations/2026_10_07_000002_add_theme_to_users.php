<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'theme')) {
                // Per-user personal theme override. When null the user inherits
                // the school-wide branding theme from system_settings; when set
                // it only affects this account.
                $table->string('theme', 64)->nullable()->after('locale');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'theme')) {
                $table->dropColumn('theme');
            }
        });
    }
};