<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Adapt and expand the Students table to hold card parameters
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'card_expiry_date')) {
                $table->date('card_expiry_date')->nullable()->after('status');
            }
            if (! Schema::hasColumn('students', 'card_status')) {
                $table->string('card_status')->default('pending_issuance')->after('card_expiry_date'); // active, lost, stolen, reissued, pending_issuance
            }
        });

        // 2. Card Templates table (Drag-and-Drop parameters store)
        Schema::create('card_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g. "Primary Portrait Badge", "Form 5/6 Landscape Smart Card"
            $table->string('orientation')->default('portrait'); // portrait, landscape, auto
            $table->string('card_type')->default('student'); // student, staff
            $table->string('barcode_format')->default('Code128'); // Code 128, Code 39, EAN
            $table->string('background_path')->nullable(); // Uploaded card backgrounds
            $table->string('watermark_path')->nullable(); // Uploaded watermarks
            $table->boolean('is_active')->default(false);
            $table->json('layout_config')->nullable(); // Stores absolute coordinates of fields (x, y, width, height, colors, fonts, margins)
            $table->timestamps();
        });

        // 3. Card Template Versions (Tracks layout edits)
        Schema::create('card_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_template_id')->constrained('card_templates')->onDelete('cascade');
            $table->integer('version_number')->default(1);
            $table->json('layout_config');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 4. Card Print History & Audit Trail (The Security Ledger)
        Schema::create('card_print_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('card_template_id')->constrained('card_templates')->onDelete('cascade');
            $table->string('serial_number')->unique(); // Unique printed card serial (e.g. SR-00123)
            $table->string('verification_code'); // Unique token generated for QR security
            $table->foreignId('printed_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('printed_at')->useCurrent();
            $table->string('printer_type')->default('pvc'); // pvc, paper, labels
            $table->timestamps();
        });

        // 5. Student Card Status Log (History of replacements/lost cards)
        Schema::create('student_card_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('action'); // lost, stolen, reissued, activated
            $table->text('reason')->nullable();
            $table->foreignId('processed_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('student_card_status_logs');
        Schema::dropIfExists('card_print_history');
        Schema::dropIfExists('card_template_versions');
        Schema::dropIfExists('card_templates');

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['card_expiry_date', 'card_status']);
        });
    }
};
