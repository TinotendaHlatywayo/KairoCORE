<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add branding parameters to schools table
        Schema::table('schools', function (Blueprint $table) {
            $table->string('motto')->nullable()->after('name');
            $table->string('phone_number')->nullable()->after('motto');
            $table->string('email_address')->nullable()->after('phone_number');
            $table->string('website_url')->nullable()->after('email_address');
            $table->text('physical_address')->nullable()->after('website_url');
            $table->string('logo_path')->nullable()->after('physical_address');
            $table->string('signature_path')->nullable()->after('logo_path');
            $table->string('stamp_path')->nullable()->after('signature_path');
        });

        // 2. Add structural variables to students table
        Schema::table('students', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id'); // Secure token for public QR verification
            $table->string('national_id')->nullable()->after('admission_number');
            $table->string('house')->nullable()->after('status'); // e.g., Chiadzwa, Nyanga, Bvumba
            $table->string('boarding_status')->default('day_scholar')->after('house'); // day_scholar, boarder
            $table->string('blood_group')->nullable()->after('boarding_status'); // e.g., O+, A-
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'national_id', 'house', 'boarding_status', 'blood_group']);
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['motto', 'phone_number', 'email_address', 'website_url', 'physical_address', 'logo_path', 'signature_path', 'stamp_path']);
        });
    }
};
