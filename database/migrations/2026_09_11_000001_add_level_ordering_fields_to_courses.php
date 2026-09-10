<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Levels are the `courses` rows (Form 2, Grade 3, ...). Add explicit
        // ordering + an explicit (self-referencing) next-level path and a
        // terminal flag so non-linear paths (bridging years, Grade 7 / Form 4
        // leavers) are representable without hardcoded name matching.
        Schema::table('courses', function (Blueprint $table) {
            $table->integer('sequence_order')->nullable();
            $table->foreignId('next_level_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->boolean('is_terminal')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['next_level_id']);
            $table->dropColumn(['sequence_order', 'next_level_id', 'is_terminal']);
        });
    }
};