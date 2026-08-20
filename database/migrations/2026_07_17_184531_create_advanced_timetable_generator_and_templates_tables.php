<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Reusable Timetable Templates Registry
        Schema::create('timetable_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g., "Friday Half-Day Schedule", "Winter Calendar"
            $table->json('settings'); // Store generation presets (start, end, intervals)
            $table->timestamps();
        });

        // 2. Add customization attributes to time_slots table
        Schema::table('time_slots', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->constrained('timetable_templates')->onDelete('set null');
            $table->string('color')->default('#f1f5f9'); // Custom hex colors for cells
            $table->boolean('is_locked')->default(false); // Protect slot from regeneration loops
        });

        // 3. Add customization attributes to timetable_lessons table
        Schema::table('timetable_lessons', function (Blueprint $table) {
            $table->string('custom_label')->nullable(); // e.g., renaming buffer to "Assembly"
            $table->string('color')->default('#15803d');
            $table->boolean('is_locked')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('timetable_lessons', function (Blueprint $table) {
            $table->dropColumn(['custom_label', 'color', 'is_locked']);
        });

        Schema::table('time_slots', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['template_id', 'color', 'is_locked']);
        });

        Schema::dropIfExists('timetable_templates');
    }
};
