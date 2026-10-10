<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('communication_polls') && ! Schema::hasColumn('communication_polls', 'created_by')) {
            Schema::table('communication_polls', function (Blueprint $table) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('target_user_ids')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('communication_polls') && Schema::hasColumn('communication_polls', 'created_by')) {
            Schema::table('communication_polls', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }
    }
};