<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ledger of recurring platform-expense reminders that have already been sent.
 * A unique row per (expense, due date, stage) guarantees the "upcoming",
 * "day before" and "due today" emails each fire at most once for an occurrence,
 * even if the scheduler runs more than once a day.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_expense_reminders')) {
            return;
        }

        Schema::create('platform_expense_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('platform_expense_id');
            $table->date('due_date');
            $table->string('stage', 32);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['platform_expense_id', 'due_date', 'stage'],
                'platform_expense_reminders_unique',
            );

            $table->foreign('platform_expense_id')
                ->references('id')
                ->on('platform_expenses')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_expense_reminders');
    }
};
