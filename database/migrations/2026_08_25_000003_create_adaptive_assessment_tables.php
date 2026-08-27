<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adaptive_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('digital_assessment_id', 'fk_aa_da_id', 'digital_assessments', 'id')->cascadeOnDelete();
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('base_difficulty')->default(50);
            $table->unsignedInteger('min_difficulty')->default(0);
            $table->unsignedInteger('max_difficulty')->default(100);
            $table->unsignedInteger('window_size')->default(3);
            $table->decimal('adjustment_rate', 5, 2)->default(10.00);
            $table->timestamps();

            $table->unique(['school_id', 'digital_assessment_id'], 'idx_aa_school_da');
        });

        Schema::create('adaptive_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('adaptive_assessment_id', 'fk_ar_adaptive', 'adaptive_assessments', 'id')->cascadeOnDelete();
            $table->string('rule_type', 50);
            $table->unsignedInteger('threshold_from')->nullable();
            $table->unsignedInteger('threshold_to')->nullable();
            $table->string('condition_op', 10)->default('>=');
            $table->unsignedInteger('adjustment')->default(0);
            $table->foreignId('target_question_bank_id', 'fk_ar_qb', 'question_bank', 'id')->nullable()->nullOnDelete();
            $table->unsignedInteger('target_difficulty')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();

            $table->index(['adaptive_assessment_id', 'priority'], 'idx_ar_aa_priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adaptive_rules');
        Schema::dropIfExists('adaptive_assessments');
    }
};
