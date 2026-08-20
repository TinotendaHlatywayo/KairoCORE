<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role')->default('main'); // main, assistant, substitute
            $table->unsignedInteger('periods_per_week')->default(4);
            $table->string('room_preference')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'course_id', 'subject_id'], 'uq_course_subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_subject');
    }
};
