<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->table('screening_runs', function (Blueprint $table) {
            $table->string('score_basis')->nullable()->after('status');
            $table->string('academic_year_mode')->nullable()->after('score_basis');
            $table->json('subject_ids')->nullable()->after('academic_year_mode');
            $table->json('term_ids')->nullable()->after('subject_ids');
            $table->json('academic_year_ids')->nullable()->after('term_ids');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('screening_runs', function (Blueprint $table) {
            $table->dropColumn(['score_basis', 'academic_year_mode', 'subject_ids', 'term_ids', 'academic_year_ids']);
        });
    }
};