<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Disable constraints to prevent MySQL dropping locks
        Schema::disableForeignKeyConstraints();

        // 2. Clear out any legacy tables safely
        Schema::dropIfExists('knowledge_asset_copies');
        Schema::dropIfExists('knowledge_asset_author');
        Schema::dropIfExists('knowledge_asset_versions');
        Schema::dropIfExists('knowledge_assets');
        Schema::dropIfExists('knowledge_formats');

        // 3. Create dynamic Knowledge Format classes table
        Schema::create('knowledge_formats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->string('media_type'); // 'physical' or 'digital'
            $table->timestamps();

            $table->unique(['school_id', 'name', 'media_type'], 'uq_kno_fmt_school_name');
        });

        // 4. Create the redesigned Knowledge Assets table
        Schema::create('knowledge_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('uploaded_by_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('library_category_id')->constrained('library_categories')->onDelete('cascade');
            $table->foreignId('knowledge_format_id')->nullable()->constrained('knowledge_formats')->onDelete('set null');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('subtype')->nullable(); // Optional sub-classification
            $table->text('abstract_description')->nullable(); // Optional description
            $table->string('visibility')->default('library_only');
            $table->string('isbn')->nullable(); // Optional reference number
            $table->string('publisher')->nullable();
            $table->string('publication_year', 4)->nullable();
            $table->string('language', 100)->default('English');
            $table->string('media_type')->default('physical');
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'media_type'], 'idx_kno_as_lookup');
        });

        // 5. Create many-to-many pivot table for authors
        Schema::create('knowledge_asset_author', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_asset_id')->constrained('knowledge_assets')->onDelete('cascade');
            $table->foreignId('library_author_id')->constrained('library_authors')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['knowledge_asset_id', 'library_author_id'], 'uq_kno_as_author_pivot');
        });

        // 6. Create copy tracking table for physical repository assets
        Schema::create('knowledge_asset_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('knowledge_asset_id')->constrained('knowledge_assets')->onDelete('cascade');
            $table->string('barcode');
            $table->string('qr_code');
            $table->string('shelf')->nullable();
            $table->string('rack')->nullable();
            $table->string('position')->nullable();
            $table->string('condition')->default('excellent');
            $table->string('status')->default('available');
            $table->timestamps();

            $table->unique(['school_id', 'barcode'], 'uq_kno_cop_barcode');
        });

        // 7. Re-enable constraints to preserve database integrity
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('knowledge_asset_copies');
        Schema::dropIfExists('knowledge_asset_author');
        Schema::dropIfExists('knowledge_assets');
        Schema::dropIfExists('knowledge_formats');
        Schema::enableForeignKeyConstraints();
    }
};
