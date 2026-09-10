<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('screening_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained();
            $table->foreignId('source_academic_year_id')->constrained('academic_years');
            $table->foreignId('target_academic_year_id')->constrained('academic_years');
            $table->foreignId('promotion_run_id')->nullable()->constrained('promotion_runs')->nullOnDelete();
            $table->enum('status', ['draft', 'in_progress', 'committed', 'archived'])->default('draft');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('screening_runs');
    }
};