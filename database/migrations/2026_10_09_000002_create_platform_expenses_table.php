<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KairoCORE's own operating expenses. Deliberately separate from the tenant
 * `expenses` table (which is tenant-scoped) because platform costs are
 * central and belong to the SaaS operator, not to any school.
 *
 * A row with `is_recurring = true` is a recurrence template: the scheduler
 * generates a one-time child row (linked through `parent_expense_id`) for
 * each due occurrence and advances `next_due_date`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_expenses')) {
            return;
        }

        Schema::create('platform_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('expense_date');

            $table->string('vendor')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->string('attachment_path')->nullable();

            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence', 20)->nullable();
            $table->date('next_due_date')->nullable();
            $table->date('recurrence_ends_at')->nullable();
            $table->unsignedBigInteger('parent_expense_id')->nullable();

            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('expense_date');
            $table->index('category');
            $table->index(['is_recurring', 'next_due_date'], 'platform_expenses_recurrence_lookup');
            $table->index('parent_expense_id');

            $table->foreign('parent_expense_id')
                ->references('id')
                ->on('platform_expenses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_expenses');
    }
};
