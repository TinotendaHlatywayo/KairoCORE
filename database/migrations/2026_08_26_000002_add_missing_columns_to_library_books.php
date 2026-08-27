<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_books', function (Blueprint $table) {
            $table->string('external_url')->nullable()->after('media_type');
            $table->string('file_path')->nullable()->after('external_url');
            $table->text('abstract_description')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('library_books', function (Blueprint $table) {
            $table->dropColumn(['external_url', 'file_path', 'abstract_description']);
        });
    }
};
