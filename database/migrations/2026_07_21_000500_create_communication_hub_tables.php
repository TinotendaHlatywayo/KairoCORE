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
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('communication_events');
        Schema::dropIfExists('communication_poll_votes');
        Schema::dropIfExists('communication_poll_options');
        Schema::dropIfExists('communication_polls');
        Schema::dropIfExists('communication_helpdesk_replies');
        Schema::dropIfExists('communication_helpdesk_tickets');
        Schema::dropIfExists('communication_resources');
        Schema::dropIfExists('communication_chat_messages');
        Schema::dropIfExists('communication_chat_participants');
        Schema::dropIfExists('communication_chat_threads');
        Schema::dropIfExists('communication_announcements');

        Schema::enableForeignKeyConstraints();

        // 1. Notice Board & Announcements
        Schema::create('communication_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->json('attachments')->nullable(); // PDFs, Word, Excel, Youtube links
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 50)->default('draft'); // draft, scheduled, published, expired
            $table->json('visibility')->nullable(); // target roles, classes, sections, students
            $table->string('priority', 50)->default('normal'); // low, normal, important, critical, emergency
            $table->string('display_style', 50)->default('card'); // banner, card, popup, ticker
            $table->boolean('requires_acknowledgement')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status', 'priority']);
        });

        // 2. Chat Threads (One-to-One, Groups, Classes)
        Schema::create('communication_chat_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('type', 50)->default('one_to_one'); // one_to_one, group, department, class, parent_teacher
            $table->string('name')->nullable(); // Nullable for direct messaging
            $table->json('metadata')->nullable(); // e.g. class_id, department_id, pinned_state
            $table->timestamps();
        });

        // 3. Chat Thread Participants Pivot
        Schema::create('communication_chat_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('thread_id')->constrained('communication_chat_threads')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'thread_id', 'user_id'], 'uq_thread_participant');
        });

        // 4. Chat Messages Ledger
        Schema::create('communication_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('thread_id')->constrained('communication_chat_threads')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('message');
            $table->json('attachments')->nullable(); // Document links
            $table->json('reactions')->nullable(); // Emojis mappings
            $table->timestamps();

            $table->index(['school_id', 'thread_id', 'created_at']);
        });

        // 5. Campus Resource Library
        Schema::create('communication_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('thumbnail_path')->nullable();
            $table->string('file_path')->nullable();
            $table->string('category', 100); // Academic, Policies, HR, Finance
            $table->json('visibility')->nullable(); // roles eligible to download
            $table->string('version')->default('1.0');
            $table->json('tags')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // 6. Helpdesk Support Tickets
        Schema::create('communication_helpdesk_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('ticket_number');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Submitter
            $table->string('category', 100); // IT, Payroll, Maintenance, Transport
            $table->string('subject');
            $table->text('description');
            $table->string('priority', 50)->default('medium'); // low, medium, high
            $table->string('status', 50)->default('open'); // open, assigned, accepted, in_progress, waiting_for_user, resolved, closed
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'ticket_number'], 'uq_ticket_number');
            $table->index(['school_id', 'status', 'priority']);
        });

        // 7. Helpdesk Conversational Replies
        Schema::create('communication_helpdesk_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('ticket_id')->constrained('communication_helpdesk_tickets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Replier
            $table->text('message');
            $table->boolean('is_internal')->default(false); // Notes hidden from parent/student portal
            $table->json('attachments')->nullable();
            $table->timestamps();
        });

        // 8. Polls, Elections, & Surveys
        Schema::create('communication_polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('question');
            $table->text('description')->nullable();
            $table->string('type', 50)->default('poll'); // poll, survey, election
            $table->boolean('is_anonymous')->default(false);
            $table->json('target_roles')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // 9. Poll Choices
        Schema::create('communication_poll_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('poll_id')->constrained('communication_polls')->onDelete('cascade');
            $table->string('option_value');
            $table->timestamps();
        });

        // 10. Poll Votes Registry
        Schema::create('communication_poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('poll_id')->constrained('communication_polls')->onDelete('cascade');
            $table->foreignId('option_id')->nullable()->constrained('communication_poll_options')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('written_response')->nullable(); // For open text survey answers
            $table->timestamps();

            // Prevents double voting in surveys/polls
            $table->unique(['school_id', 'poll_id', 'user_id'], 'uq_user_poll_vote');
        });

        // 11. Calendar & Events Ledger
        Schema::create('communication_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 100); // Academic, Meetings, Sports, Examinations, Holiday
            $table->datetime('start_time');
            $table->datetime('end_time');
            $table->string('location')->nullable();
            $table->string('color', 50)->default('#1e3a8a');
            $table->json('target_roles')->nullable();
            $table->json('target_sections')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('communication_events');
        Schema::dropIfExists('communication_poll_votes');
        Schema::dropIfExists('communication_poll_options');
        Schema::dropIfExists('communication_polls');
        Schema::dropIfExists('communication_helpdesk_replies');
        Schema::dropIfExists('communication_helpdesk_tickets');
        Schema::dropIfExists('communication_resources');
        Schema::dropIfExists('communication_chat_messages');
        Schema::dropIfExists('communication_chat_participants');
        Schema::dropIfExists('communication_chat_threads');
        Schema::dropIfExists('communication_announcements');
        Schema::enableForeignKeyConstraints();
    }
};
