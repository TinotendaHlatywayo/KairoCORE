<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\SaaS\Models\PlatformExpense;
use Modules\SaaS\Models\PlatformSetting;

/**
 * Materialises recurring platform expenses. Each recurring expense acts as a
 * template: when its `next_due_date` falls on/before today, a one-time child
 * expense is created for that occurrence and the template's `next_due_date`
 * advances by one interval. Missed periods are back-filled in order.
 */
class GenerateRecurringExpenses extends Command
{
    protected $signature = 'finance:generate-recurring-expenses {--dry-run : Report what would happen without writing anything}';

    protected $description = 'Generates due occurrences of recurring platform (KairoCORE) expenses';

    /**
     * Safety valve: never generate more than this many occurrences for a
     * single template in one run (protects against years-old next_due_date).
     */
    private const MAX_OCCURRENCES_PER_TEMPLATE = 60;

    public function handle(): int
    {
        if (! $this->autoGenerateEnabled()) {
            $this->info('Recurring platform expense generation is disabled in Platform Settings.');

            return self::SUCCESS;
        }

        $today = now()->startOfDay();
        $dryRun = (bool) $this->option('dry-run');
        $generated = 0;
        $templates = 0;

        $due = PlatformExpense::query()
            ->where('is_recurring', true)
            ->whereNull('parent_expense_id')
            ->whereNotNull('recurrence')
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<=', $today)
            ->orderBy('id')
            ->get();

        foreach ($due as $template) {
            $createdForTemplate = 0;

            while (
                $template->next_due_date
                && $template->next_due_date->lte($today)
                && $createdForTemplate < self::MAX_OCCURRENCES_PER_TEMPLATE
            ) {
                if ($template->recurrence_ends_at && $template->next_due_date->gt($template->recurrence_ends_at)) {
                    break;
                }

                $occurrenceDate = $template->next_due_date->copy();

                if ($dryRun) {
                    $this->line("Would generate: {$template->title} for {$occurrenceDate->toDateString()} (\${$template->amount}).");
                } else {
                    DB::transaction(function () use ($template, $occurrenceDate) {
                        PlatformExpense::create([
                            'title' => $template->title,
                            'category' => $template->category,
                            'description' => $template->description,
                            'amount' => $template->amount,
                            'currency' => $template->currency,
                            'expense_date' => $occurrenceDate->toDateString(),
                            'vendor' => $template->vendor,
                            'payment_method' => $template->payment_method,
                            'is_recurring' => false,
                            'parent_expense_id' => $template->id,
                            'is_paid' => false,
                            'user_id' => $template->user_id,
                        ]);

                        $template->next_due_date = $template->advanceOccurrence($occurrenceDate)->toDateString();
                        $template->save();
                    });

                    Log::info('Recurring platform expense generated', [
                        'template_id' => $template->id,
                        'expense_date' => $occurrenceDate->toDateString(),
                    ]);
                }

                // Keep in-memory pointer moving for dry-run and the loop guard.
                if ($dryRun) {
                    $template->next_due_date = $template->advanceOccurrence($occurrenceDate)->toDateString();
                }

                $createdForTemplate++;
                $generated++;
            }

            if ($createdForTemplate > 0) {
                $templates++;
            }
        }

        $this->info(sprintf(
            'Recurring expense generation complete: %d occurrence(s) across %d template(s) %s.',
            $generated,
            $templates,
            $dryRun ? 'matched' : 'created',
        ));

        return self::SUCCESS;
    }

    protected function autoGenerateEnabled(): bool
    {
        return filter_var(
            PlatformSetting::get('automation', 'auto_generate_recurring_expenses', '1'),
            FILTER_VALIDATE_BOOLEAN,
        );
    }
}
