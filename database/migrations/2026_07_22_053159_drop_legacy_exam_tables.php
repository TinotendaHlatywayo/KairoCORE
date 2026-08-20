<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Temporarily disable foreign key constraints to prevent dropping order conflicts
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('exam_marks');
        Schema::dropIfExists('exam_papers');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('sbp_assessments');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Reverse migration is not required for dropping obsolete legacy tables
    }
};
