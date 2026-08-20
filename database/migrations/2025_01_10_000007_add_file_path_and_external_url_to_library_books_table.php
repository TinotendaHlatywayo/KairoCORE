<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('library_books')) {
            return;
        }

        Schema::table('library_books', function (Blueprint $table) {
            // Adds the missing file_path column safely if it doesn't already exist
            if (! Schema::hasColumn('library_books', 'file_path')) {
                $table->string('file_path')->nullable()->after('media_type');
            }

            // Adds the missing external_url column safely if it doesn't already exist
            if (! Schema::hasColumn('library_books', 'external_url')) {
                $table->string('external_url')->nullable()->after('file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('library_books', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'external_url']);
        });
    }
};
