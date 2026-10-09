<?php

namespace Modules\SaaS\Models;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A KairoCORE (platform-level) operating expense. Not tenant scoped: these
 * costs belong to the SaaS operator. A recurring row doubles as the template
 * that the scheduler clones into one-time child rows.
 */
class PlatformExpense extends Model
{
    use SoftDeletes;

    protected $table = 'platform_expenses';

    protected $fillable = [
        'title',
        'category',
        'description',
        'amount',
        'currency',
        'expense_date',
        'vendor',
        'payment_method',
        'reference',
        'attachment_path',
        'is_recurring',
        'recurrence',
        'next_due_date',
        'recurrence_ends_at',
        'parent_expense_id',
        'is_paid',
        'paid_at',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'next_due_date' => 'date',
        'recurrence_ends_at' => 'date',
        'is_recurring' => 'boolean',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public const RECURRENCES = [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
    ];

    public const SUGGESTED_CATEGORIES = [
        'Hosting & Domains',
        'Cloud Infrastructure',
        'Software & Licenses',
        'Payment Gateway Fees',
        'Marketing & Advertising',
        'Salaries & Contractors',
        'Professional Services',
        'Office & Admin',
        'Hardware & Equipment',
        'Travel',
        'Taxes & Statutory',
        'Other',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_expense_id');
    }

    public function generatedExpenses(): HasMany
    {
        return $this->hasMany(self::class, 'parent_expense_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(PlatformExpenseReminder::class, 'platform_expense_id');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('is_paid', true);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('is_paid', false);
    }

    public function recurrenceLabel(): string
    {
        if (! $this->is_recurring) {
            return __('One-time');
        }

        $label = self::RECURRENCES[$this->recurrence] ?? __('Recurring');

        return __($label);
    }

    /**
     * Advance a date by this expense's recurrence interval, never spilling
     * (e.g. Jan 31 -> Feb 28, not Mar 03).
     */
    public function advanceOccurrence(CarbonInterface $date): Carbon
    {
        return static::nextOccurrenceFor((string) $this->recurrence, $date);
    }

    /**
     * Resolve the next date for a recurrence interval. Shared by the model and
     * the admin create/edit forms so the default is always consistent.
     */
    public static function nextOccurrenceFor(?string $recurrence, CarbonInterface|string $date): Carbon
    {
        $date = Carbon::parse($date);

        return match ($recurrence) {
            'daily' => $date->addDay(),
            'weekly' => $date->addWeek(),
            'quarterly' => $date->addMonthsNoOverflow(3),
            'yearly' => $date->addYearNoOverflow(),
            default => $date->addMonthNoOverflow(),
        };
    }
}
