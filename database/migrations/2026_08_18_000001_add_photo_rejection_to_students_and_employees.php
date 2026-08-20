<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['students', 'employees'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->text('photo_rejected_reason')->nullable()->after('avatar_path');
                $table->foreignId('photo_rejected_by')->nullable()->after('photo_rejected_reason');
                $table->timestamp('photo_rejected_at')->nullable()->after('photo_rejected_by');
            });
        }
    }

    public function down(): void
    {
        foreach (['students', 'employees'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['photo_rejected_reason', 'photo_rejected_by', 'photo_rejected_at']);
            });
        }
    }
};
