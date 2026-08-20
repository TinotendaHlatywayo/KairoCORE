<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('thread_id')->index();
            $table->enum('sender_type', ['platform', 'school'])->default('school');
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
            $table->enum('recipient_type', ['platform', 'school'])->default('school');
            $table->enum('recipient_scope', ['all', 'selected', 'single'])->default('single');
            $table->json('target_meta')->nullable();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->enum('priority', ['info', 'normal', 'important'])->default('normal');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_message_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('platform_messages')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->enum('status', ['sent', 'delivered', 'read'])->default('sent');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'school_id']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_message_recipients');
        Schema::dropIfExists('platform_messages');
    }
};
