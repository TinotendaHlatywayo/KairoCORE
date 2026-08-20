<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timetable_templates', function (Blueprint $table) {
            // Enforce that a school can only have one unique template name
            $table->unique(['school_id', 'name'], 'uq_school_template_name');
        });
    }

    public function down(): void
    {
        Schema::table('timetable_templates', function (Blueprint $table) {
            $table->dropUnique('uq_school_template_name');
        });
    }
};
