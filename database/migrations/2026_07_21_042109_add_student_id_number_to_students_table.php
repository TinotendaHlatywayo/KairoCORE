<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Adds the admin-editable, auto-generated unique student id number
            if (! Schema::hasColumn('students', 'student_id_number')) {
                $table->string('student_id_number')->nullable()->after('user_id');

                // Composite unique index to allow clean multi-tenant numbering sequences
                $table->unique(['school_id', 'student_id_number'], 'uq_school_student_id_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('uq_school_student_id_number');
            $table->dropColumn('student_id_number');
        });
    }
};
