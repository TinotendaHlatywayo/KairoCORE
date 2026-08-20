<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('homeworks', function (Blueprint $table) {
            if (Schema::hasColumn('homeworks', 'timetable_lesson_id')) {
                // Try to drop the foreign key constraint safely
                try {
                    $table->dropForeign('homeworks_timetable_lesson_id_foreign');
                } catch (Exception $e) {
                    // Suppress error if the key doesn't exist
                }

                // Try to drop the column safely
                try {
                    $table->dropColumn('timetable_lesson_id');
                } catch (Exception $e) {
                    // Suppress error if the column is already dropped
                }
            }

            // Safely add the new columns only if they do not exist yet
            if (! Schema::hasColumn('homeworks', 'section_id')) {
                $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('cascade');
            }

            if (! Schema::hasColumn('homeworks', 'subject_id')) {
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('cascade');
            }
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('homeworks', function (Blueprint $table) {
            if (Schema::hasColumn('homeworks', 'section_id')) {
                $table->dropColumn('section_id');
            }

            if (Schema::hasColumn('homeworks', 'subject_id')) {
                $table->dropColumn('subject_id');
            }

            if (! Schema::hasColumn('homeworks', 'timetable_lesson_id')) {
                $table->foreignId('timetable_lesson_id')->nullable()->constrained('timetable_lessons')->onDelete('cascade');
            }
        });

        Schema::enableForeignKeyConstraints();
    }
};
