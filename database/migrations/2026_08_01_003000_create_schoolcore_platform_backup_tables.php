<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Safe drop triggers to prevent schema remnants from blocking migrations
        Schema::dropIfExists('platform_restore_logs');
        Schema::dropIfExists('platform_backups');

        // Global Platform Backups Tracker
        Schema::create('platform_backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->bigInteger('size_bytes')->default(0);
            $table->string('checksum')->nullable(); // SHA-256
            $table->string('disk')->default('local');
            $table->boolean('is_verified')->default(false);
            $table->string('status')->default('completed'); // completed, failed, corrupted
            $table->text('error_log')->nullable();
            $table->timestamps();
        });

        // Global Platform Restoration Audit Log
        Schema::create('platform_restore_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('backup_id');
            $table->unsignedBigInteger('performed_by_id');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('error_details')->nullable();
            $table->timestamps();

            $table->foreign('backup_id')->references('id')->on('platform_backups')->onDelete('cascade');
            $table->foreign('performed_by_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_restore_logs');
        Schema::dropIfExists('platform_backups');
    }
};
