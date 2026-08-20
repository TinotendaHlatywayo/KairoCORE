<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->text('bio')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'name'], 'uq_library_authors_school_name');
        });

        Schema::create('library_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('library_categories')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['school_id', 'name', 'parent_id'], 'uq_library_categories_hierarchy');
        });

        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('library_author_id')->constrained('library_authors')->onDelete('cascade');
            $table->foreignId('library_category_id')->constrained('library_categories')->onDelete('cascade');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('publisher')->nullable();
            $table->string('publication_year', 4)->nullable();
            $table->string('isbn')->nullable();
            $table->string('language', 100)->default('English');
            $table->string('subject')->nullable();
            $table->string('grade_level')->nullable();
            $table->enum('type', ['physical', 'digital'])->default('physical');
            $table->string('cover_image_path')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'isbn'], 'idx_library_books_isbn');
            $table->index(['school_id', 'title'], 'idx_library_books_title');
        });

        Schema::create('library_book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('library_book_id')->constrained('library_books')->onDelete('cascade');
            $table->string('barcode');
            $table->string('qr_code');
            $table->string('shelf')->nullable();
            $table->string('rack')->nullable();
            $table->string('position')->nullable();
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor', 'damaged'])->default('excellent');
            $table->enum('status', ['available', 'issued', 'reserved', 'maintenance', 'lost', 'written_off'])->default('available');
            $table->decimal('purchase_cost', 10, 2)->nullable();
            $table->decimal('replacement_cost', 10, 2)->nullable();
            $table->date('acquired_date')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'barcode'], 'uq_library_book_copies_barcode');
            $table->unique(['school_id', 'qr_code'], 'uq_library_book_copies_qr');
        });

        Schema::create('library_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('library_book_copy_id')->constrained('library_book_copies')->onDelete('cascade');
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('issued_by_id')->constrained('users')->onDelete('cascade');
            $table->date('issued_at');
            $table->date('due_at');
            $table->date('returned_at')->nullable();
            $table->enum('status', ['issued', 'returned', 'overdue', 'lost', 'damaged'])->default('issued');
            $table->decimal('fine_amount', 10, 2)->default(0.00);
            $table->enum('fine_status', ['unpaid', 'paid', 'waived'])->default('unpaid');
            $table->integer('renewals_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status'], 'idx_library_issues_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_issues');
        Schema::dropIfExists('library_book_copies');
        Schema::dropIfExists('library_books');
        Schema::dropIfExists('library_categories');
        Schema::dropIfExists('library_authors');
    }
};
