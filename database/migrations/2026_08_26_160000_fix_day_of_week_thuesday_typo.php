<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The original migration created the enum with a 'thuesday' typo while
        // every PHP code path writes 'thursday'. Normalise the column and any
        // historical rows so Thursday lessons can be stored and matched.
        DB::statement("UPDATE timetable_lessons SET day_of_week = 'thursday' WHERE day_of_week = 'thuesday'");
        DB::statement("ALTER TABLE timetable_lessons MODIFY day_of_week ENUM('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL DEFAULT 'monday'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE timetable_lessons MODIFY day_of_week ENUM('monday','tuesday','wednesday','thuesday','friday','saturday','sunday') NOT NULL DEFAULT 'monday'");
    }
};
