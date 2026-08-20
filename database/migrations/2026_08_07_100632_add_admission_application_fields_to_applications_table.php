<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('applying_year')->nullable()->after('course_id');
            $table->string('applying_term')->nullable()->after('applying_year');
            $table->string('applying_level')->nullable()->after('applying_term');
            $table->string('transfer_letter_path')->nullable()->after('applying_level');
            $table->boolean('transfer_letter_verified')->default(false)->after('transfer_letter_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['applying_year', 'applying_term', 'applying_level', 'transfer_letter_path', 'transfer_letter_verified']);
        });
    }
};
