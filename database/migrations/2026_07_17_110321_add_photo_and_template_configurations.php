<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add photo_path to students table
        Schema::table('students', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('avatar_path');
        });

        // Add photo_path to admissions table
        Schema::table('applications', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('course_id');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
