<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unify the Calendar + Todo experience by extending the two existing
 * schedule entities (personal tasks and school calendar events) with the
 * shared scheduling concepts: priority, important flag, reminders,
 * recurrence, completion timestamps and event organizers.
 *
 * No new table is created — tasks and events stay semantically distinct and
 * simply gain the fields the unified Schedule/My Day experience relies on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('user_tasks', 'priority')) {
                $table->string('priority', 20)->default('medium')->after('due_time');
            }
            if (! Schema::hasColumn('user_tasks', 'important')) {
                $table->boolean('important')->default(false)->after('priority');
            }
            if (! Schema::hasColumn('user_tasks', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('user_tasks', 'reminder_at')) {
                $table->dateTime('reminder_at')->nullable()->after('completed_at');
            }
            if (! Schema::hasColumn('user_tasks', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('reminder_at');
            }
            if (! Schema::hasColumn('user_tasks', 'recurrence')) {
                $table->string('recurrence', 20)->default('none')->after('reminder_sent_at');
            }

            $table->index(['school_id', 'due_date', 'status'], 'user_tasks_due_status_idx');
            $table->index(['school_id', 'assigned_to_id', 'due_date'], 'user_tasks_assignee_due_idx');
            $table->index(['school_id', 'important', 'status'], 'user_tasks_important_idx');
        });

        Schema::table('communication_events', function (Blueprint $table) {
            if (! Schema::hasColumn('communication_events', 'all_day')) {
                $table->boolean('all_day')->default(false)->after('end_time');
            }
            if (! Schema::hasColumn('communication_events', 'created_by_id')) {
                $table->unsignedBigInteger('created_by_id')->nullable()->after('school_id');
                $table->foreign('created_by_id')->references('id')->on('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('communication_events', 'organizer_id')) {
                $table->unsignedBigInteger('organizer_id')->nullable()->after('created_by_id');
                $table->foreign('organizer_id')->references('id')->on('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('communication_events', 'reminder_minutes')) {
                $table->unsignedInteger('reminder_minutes')->nullable()->after('location');
            }
            if (! Schema::hasColumn('communication_events', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('reminder_minutes');
            }
            if (! Schema::hasColumn('communication_events', 'recurrence')) {
                $table->string('recurrence', 20)->default('none')->after('reminder_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_tasks', function (Blueprint $table) {
            $table->dropIndex(['user_tasks_due_status_idx']);
            $table->dropIndex(['user_tasks_assignee_due_idx']);
            $table->dropIndex(['user_tasks_important_idx']);
        });

        Schema::table('communication_events', function (Blueprint $table) {
            if (Schema::hasColumn('communication_events', 'created_by_id')) {
                $table->dropForeign(['created_by_id']);
            }
            if (Schema::hasColumn('communication_events', 'organizer_id')) {
                $table->dropForeign(['organizer_id']);
            }
        });
    }
};
