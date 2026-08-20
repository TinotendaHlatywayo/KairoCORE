<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the dynamic Format Classes table
        Schema::create('library_formats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g., 'Green Book', 'Revision Paper', 'Textbook'
            $table->string('media_type'); // 'physical' or 'digital'
            $table->timestamps();

            $table->unique(['school_id', 'name', 'media_type'], 'uq_lib_fmt_school_name');
        });

        // 2. Map books to the dynamic Format Class table
        Schema::table('library_books', function (Blueprint $table) {
            $table->foreignId('library_format_id')->nullable()->constrained('library_formats')->onDelete('set null');
            $table->string('media_type')->default('physical'); // 'physical' or 'digital'
            $table->dropColumn('type'); // Drop legacy hardcoded format types
        });
    }

    public function down(): void
    {
        Schema::table('library_books', function (Blueprint $table) {
            $table->string('type')->default('physical');
            $table->dropForeign(['library_format_id']);
            $table->dropColumn('library_format_id');
            $table->dropColumn('media_type');
        });

        Schema::dropIfExists('library_formats');
    }
};
