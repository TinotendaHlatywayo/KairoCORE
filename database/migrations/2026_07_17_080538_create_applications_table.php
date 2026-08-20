<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('application_number');
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('gender', ['male', 'female', 'other'])->required();
            $table->date('date_of_birth');

            // Parent/Guardian details
            $table->string('parent_name');
            $table->string('parent_email');
            $table->string('parent_phone');

            // Target level
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade'); // The Form level applied for

            $table->string('status')->default('pending'); // pending, confirmed, enrolled, rejected
            $table->timestamps();

            $table->unique(['school_id', 'application_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
