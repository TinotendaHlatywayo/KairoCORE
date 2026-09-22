<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // card_templates never gained is_system_default through a migration
        // (it was only ever hand-added in one dev database). Production was
        // therefore missing the column, which 500'd every card print / designer
        // query that filtered on it. Guarded so it is a no-op where the column
        // already exists.
        if (! Schema::hasTable('card_templates')) {
            return;
        }

        Schema::table('card_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('card_templates', 'is_system_default')) {
                $table->boolean('is_system_default')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('card_templates')) {
            return;
        }

        Schema::table('card_templates', function (Blueprint $table) {
            if (Schema::hasColumn('card_templates', 'is_system_default')) {
                $table->dropColumn('is_system_default');
            }
        });
    }
};
