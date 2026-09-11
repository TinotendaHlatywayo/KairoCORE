<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['students', 'employees'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'photo_approved_at')) {
                    $table->timestamp('photo_approved_at')->nullable()->after('avatar_path');
                }
                if (!Schema::hasColumn($tableName, 'photo_approved_by')) {
                    $table->foreignId('photo_approved_by')->nullable()->after('photo_approved_at');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['students', 'employees'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'photo_approved_by')) {
                    $table->dropColumn('photo_approved_by');
                }
                if (Schema::hasColumn($tableName, 'photo_approved_at')) {
                    $table->dropColumn('photo_approved_at');
                }
            });
        }
    }
};
