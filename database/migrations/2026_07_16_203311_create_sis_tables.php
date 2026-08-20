<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Students
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Nullable until enrolled & user login is auto-provisioned
            $table->string('admission_number');
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('gender', ['male', 'female', 'other'])->required();
            $table->date('date_of_birth');
            $table->date('admission_date');
            $table->string('status')->default('active'); // active, inactive, suspended, graduated
            $table->string('avatar_path')->nullable();

            // Health & Emergency contact fields
            $table->text('medical_notes')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Ensure admission numbers are unique only within the same school
            $table->unique(['school_id', 'admission_number']);
        });

        // 2. Guardians
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('relationship')->nullable(); // e.g., Father, Aunt, Grandmother
            $table->timestamps();
        });

        // 3. Student-Guardian Pivot (Many-to-Many)
        Schema::create('student_guardian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('guardian_id')->constrained('guardians')->onDelete('cascade');
        });

        // 4. Academic Year Enrollments (Multi-year history tracker)
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade'); // Representing the Class
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade'); // Representing the Stream
            $table->string('roll_number')->nullable(); // Class roll number / seat number
            $table->timestamps();

            // Ensure a student is only enrolled in ONE class per academic year
            $table->unique(['school_id', 'student_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('student_guardian');
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('students');
    }
};
