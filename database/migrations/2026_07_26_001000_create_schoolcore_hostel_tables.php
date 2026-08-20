<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['boys', 'girls', 'mixed']);
            $table->integer('capacity')->default(0);
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, maintenance, inactive
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['school_id', 'name']);
        });

        Schema::create('hostel_buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('hostel_id')->constrained('hostels')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'hostel_id', 'name'], 'uq_hostel_bld_name');
        });

        Schema::create('hostel_floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('building_id')->constrained('hostel_buildings')->onDelete('cascade');
            $table->integer('floor_number');
            $table->string('floor_name')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'building_id', 'floor_number'], 'uq_hostel_flr_num');
        });

        Schema::create('hostel_wings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('floor_id')->constrained('hostel_floors')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'floor_id', 'name'], 'uq_hostel_wng_name');
        });

        Schema::create('hostel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('wing_id')->constrained('hostel_wings')->onDelete('cascade');
            $table->foreignId('floor_id')->constrained('hostel_floors')->onDelete('cascade');
            $table->string('room_number');
            $table->string('name')->nullable();
            $table->string('room_type')->default('dormitory'); // dormitory, single, double, triple, quad, isolation, staff, vip
            $table->string('condition')->default('good'); // good, fair, needs_repair
            $table->string('status')->default('available'); // available, full, maintenance, locked
            $table->integer('capacity')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['school_id', 'floor_id', 'room_number'], 'uq_hostel_rm_num');
        });

        Schema::create('hostel_beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('hostel_rooms')->onDelete('cascade');
            $table->string('bed_number');
            $table->string('qr_code')->nullable();
            $table->string('barcode')->nullable();
            $table->string('condition')->default('good');
            $table->string('status')->default('vacant'); // vacant, occupied, maintenance
            $table->string('cleaning_status')->default('clean'); // clean, dirty, cleaning_in_progress
            $table->timestamps();
            $table->unique(['school_id', 'room_id', 'bed_number'], 'uq_hostel_bed_num');
        });

        Schema::create('hostel_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('hostel_id')->constrained('hostels')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['master', 'matron', 'assistant_warden']);
            $table->timestamps();
            $table->unique(['school_id', 'hostel_id', 'user_id', 'role'], 'uq_hostel_staff_role');
        });

        Schema::create('hostel_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('bed_id')->constrained('hostel_beds')->onDelete('restrict');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->enum('status', ['active', 'completed', 'cancelled', 'waiting_list'])->default('active');
            $table->date('allocated_at');
            $table->date('expected_checkout_at')->nullable();
            $table->date('checked_out_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['school_id', 'student_id', 'academic_year_id', 'status'], 'uq_hostel_alloc_student');
        });

        Schema::create('student_hostel_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->text('medical_conditions')->nullable();
            $table->text('dietary_restrictions')->nullable();
            $table->text('emergency_contacts')->nullable();
            $table->string('laundry_number')->nullable();
            $table->string('qr_pass_token')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'student_id']);
        });

        Schema::create('hostel_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('hostel_id')->constrained('hostels')->onDelete('cascade');
            $table->foreignId('recorded_by_user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->string('type'); // morning, evening, weekend, holiday, curfew
            $table->timestamps();
            $table->unique(['school_id', 'hostel_id', 'date', 'type'], 'uq_hostel_att_day_type');
        });

        Schema::create('hostel_attendance_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('hostel_attendance_id')->constrained('hostel_attendances')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('status', ['present', 'absent', 'late', 'leave'])->default('present');
            $table->text('remarks')->nullable();
            $table->dateTime('notified_parents_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'hostel_attendance_id', 'student_id'], 'uq_hostel_att_student');
        });

        Schema::create('hostel_out_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('hostel_id')->constrained('hostels')->onDelete('cascade');
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // emergency, weekend, medical, sports, educational, home
            $table->string('status')->default('draft'); // draft, pending_parent_otp, pending_warden, approved, checked_out, returned, overdue
            $table->text('reason');
            $table->dateTime('expected_departure');
            $table->dateTime('expected_return');
            $table->dateTime('actual_departure')->nullable();
            $table->dateTime('actual_return')->nullable();
            $table->string('parent_otp', 6)->nullable();
            $table->dateTime('parent_approved_at')->nullable();
            $table->foreignId('warden_approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('warden_approved_at')->nullable();
            $table->dateTime('gate_scanned_at')->nullable();
            $table->foreignId('gate_scanner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('qr_code')->unique();
            $table->timestamps();
        });

        Schema::create('hostel_visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('visitor_name');
            $table->string('national_id')->nullable();
            $table->string('phone_number');
            $table->string('relationship');
            $table->string('purpose');
            $table->dateTime('arrival_time');
            $table->dateTime('departure_time')->nullable();
            $table->string('vehicle_registration')->nullable();
            $table->text('items_brought')->nullable();
            $table->text('items_taken')->nullable();
            $table->string('badge_number')->nullable();
            $table->boolean('is_blacklisted')->default(false);
            $table->timestamps();
        });

        Schema::create('hostel_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('hostel_rooms')->onDelete('cascade');
            $table->foreignId('inspector_user_id')->constrained('users')->onDelete('cascade');
            $table->date('inspection_date');
            $table->integer('cleanliness_score'); // 1-100
            $table->integer('inventory_status_score'); // 1-100
            $table->integer('orderliness_score'); // 1-100
            $table->text('notes')->nullable();
            $table->boolean('passes_inspection')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_inspections');
        Schema::dropIfExists('hostel_visitors');
        Schema::dropIfExists('hostel_out_passes');
        Schema::dropIfExists('hostel_attendance_students');
        Schema::dropIfExists('hostel_attendances');
        Schema::dropIfExists('student_hostel_profiles');
        Schema::dropIfExists('hostel_allocations');
        Schema::dropIfExists('hostel_staff');
        Schema::dropIfExists('hostel_beds');
        Schema::dropIfExists('hostel_rooms');
        Schema::dropIfExists('hostel_wings');
        Schema::dropIfExists('hostel_floors');
        Schema::dropIfExists('hostel_buildings');
        Schema::dropIfExists('hostels');
    }
};
