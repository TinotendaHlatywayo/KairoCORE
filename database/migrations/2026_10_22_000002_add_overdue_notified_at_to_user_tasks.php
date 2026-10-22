<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('user_tasks', 'overdue_notified_at')) {
            Schema::table('user_tasks', function (Blueprint $table) {
                // The moment the assignee was told that this task is overdue.
                // The sweeper uses it so the same task is never announced
                // twice, and a task that is reopened clears it again.
                $table->timestamp('overdue_notified_at')->nullable()->after('reminder_sent_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_tasks', 'overdue_notified_at')) {
            Schema::table('user_tasks', function (Blueprint $table) {
                $table->dropColumn('overdue_notified_at');
            });
        }
    }
};
