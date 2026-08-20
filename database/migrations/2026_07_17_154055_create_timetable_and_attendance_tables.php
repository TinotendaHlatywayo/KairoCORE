<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Time Slots / Period Definitions (e.g., "Period 1", 08:00 to 08:45)
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_break')->default(false); // e.g., recess, lunch
            $table->timestamps();
        });

        // 2. Timetable Lessons (Scheduled slots)
        Schema::create('timetable_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade'); // The Form
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade'); // The Class Stream
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade'); // Staff user account
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            $table->foreignId('time_slot_id')->constrained('time_slots')->onDelete('cascade');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thuesday', 'friday', 'saturday', 'sunday'])->default('monday');
            $table->timestamps();

            // DATABASE-LEVEL CONFLICT PREVENTION INDEXES:

            // Conflict 1: Prevent a Class Stream (Section) from having two classes at the same time
            $table->unique(
                ['school_id', 'academic_year_id', 'term_id', 'time_slot_id', 'day_of_week', 'section_id'],
                'uq_timetable_stream_time'
            );

            // Conflict 2: Prevent a Teacher from being booked in two places at the same time
            $table->unique(
                ['school_id', 'academic_year_id', 'term_id', 'time_slot_id', 'day_of_week', 'teacher_id'],
                'uq_timetable_teacher_time'
            );

            // Conflict 3: Prevent a Classroom from being double-booked at the same time
            $table->unique(
                ['school_id', 'academic_year_id', 'term_id', 'time_slot_id', 'day_of_week', 'classroom_id'],
                'uq_timetable_classroom_time'
            );
        });

        // 3. Student Attendances (Linked directly to Timetable Lessons)
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('timetable_lesson_id')->constrained('timetable_lessons')->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('present');
            $table->string('remarks')->nullable();
            $table->foreignId('marked_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Prevent duplicate attendance logs for the same student on the same day and lesson period
            $table->unique(['school_id', 'student_id', 'timetable_lesson_id', 'date'], 'uq_student_attendance_lesson');
        });

        // 4. Staff Attendances (Daily general ledger)
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Staff user ID
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'excused'])->default('present');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->string('method')->default('manual'); // manual, biometric, rfid, qr
            $table->foreignId('marked_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['school_id', 'user_id', 'date'], 'uq_staff_attendance_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendances');
        Schema::dropIfExists('student_attendances');
        Schema::dropIfExists('timetable_lessons');
        Schema::dropIfExists('time_slots');
    }
};
