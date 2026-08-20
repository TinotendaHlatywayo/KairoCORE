<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('blood_group', 10)->nullable();
            $table->text('allergies')->nullable();
            $table->text('chronic_conditions')->nullable();
            $table->json('immunization_history')->nullable();
            $table->text('regular_medications')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'student_id']);
        });

        Schema::create('clinic_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('recorded_by_user_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('visit_time');
            $table->dateTime('departure_time')->nullable();
            $table->text('symptoms');
            $table->text('diagnosis')->nullable();
            $table->text('treatment_given')->nullable();
            $table->decimal('temperature_celsius', 4, 2)->nullable();
            $table->string('blood_pressure', 20)->nullable();
            $table->string('status')->default('checked_in'); // checked_in, treatment, admitted, referred, discharged
            $table->string('referral_destination')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('clinic_visit_id')->constrained('clinic_visits')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->onDelete('set null');
            $table->string('medicine_name');
            $table->string('dosage');
            $table->string('frequency');
            $table->integer('quantity_prescribed');
            $table->integer('quantity_dispensed')->default(0);
            $table->dateTime('dispensed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_medical_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('alert_level')->default('medium'); // low, medium, critical
            $table->string('message');
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_medical_alerts');
        Schema::dropIfExists('clinic_prescriptions');
        Schema::dropIfExists('clinic_visits');
        Schema::dropIfExists('student_medical_records');
    }
};
