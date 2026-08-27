<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('course_subject', 'section_id')) {
            Schema::table('course_subject', function (Blueprint $table) {
                $table->foreignId('section_id')
                    ->nullable()
                    ->after('course_id')
                    ->constrained('sections')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('course_subject', 'section_id')) {
            Schema::table('course_subject', function (Blueprint $table) {
                $table->dropConstrainedForeignId('section_id');
            });
        }
    }
};
