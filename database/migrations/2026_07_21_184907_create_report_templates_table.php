<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Academic Report Templates registry table
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g. "Primary 1 to 7 Standard Template"
            $table->string('design_theme')->default('classic_line'); // classic_line, modern_grid, elegant_editorial, minimal_compact, royal_crest
            $table->string('target_level')->default('primary'); // ecd, primary, lower_secondary, upper_secondary, all
            $table->boolean('is_active')->default(false);
            $table->json('layout_config')->nullable(); // Stores font styles, header spacing, and structural visibilities
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};
