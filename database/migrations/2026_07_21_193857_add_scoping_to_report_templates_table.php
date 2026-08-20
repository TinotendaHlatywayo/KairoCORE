<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('report_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('report_templates', 'scope_type')) {
                $table->string('scope_type')->default('level')->after('target_level'); // level, course, section
                $table->foreignId('course_id')->nullable()->after('scope_type')->constrained('courses')->onDelete('cascade');
                $table->foreignId('section_id')->nullable()->after('course_id')->constrained('sections')->onDelete('cascade');
            }
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('report_templates', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropForeign(['section_id']);
            $table->dropColumn(['scope_type', 'course_id', 'section_id']);
        });

        Schema::enableForeignKeyConstraints();
    }
};
