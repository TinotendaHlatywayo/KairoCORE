<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Many-to-many pivot table between books and authors
        Schema::create('library_book_author', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_book_id')->constrained('library_books')->onDelete('cascade');
            $table->foreignId('library_author_id')->constrained('library_authors')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['library_book_id', 'library_author_id'], 'uq_book_author_pivot');
        });

        // Safe cleanup: Drop the single foreign key column from library_books (migrated to pivot)
        Schema::table('library_books', function (Blueprint $table) {
            $table->dropForeign(['library_author_id']);
            $table->dropColumn('library_author_id');
        });
    }

    public function down(): void
    {
        Schema::table('library_books', function (Blueprint $table) {
            $table->foreignId('library_author_id')->nullable()->constrained('library_authors')->onDelete('cascade');
        });

        Schema::dropIfExists('library_book_author');
    }
};
