<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mark_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('assessment_marks_ledger_id')->constrained('assessment_marks_ledger')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');

            $table->decimal('old_mark', 5, 2)->nullable();
            $table->decimal('new_mark', 5, 2);
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('reason_for_change');

            $table->string('approval_status')->default('pending'); // pending, approved, rejected
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mark_correction_requests');
    }
};
