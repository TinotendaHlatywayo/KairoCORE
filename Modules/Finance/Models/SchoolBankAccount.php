<?php

namespace Modules\Finance\Models;

use App\Models\School;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolBankAccount extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'bank_name',
        'account_name',
        'account_number',
        'branch_code',
        'swift_code',
        'is_default',
        'is_active',
        'balance',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'balance' => 'decimal:2',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * A query-builder closure that scopes a transaction to a bank account.
     *
     * Unassigned transactions (bank_account_id IS NULL) are treated as belonging
     * to the school's default account — and to a school's only account when no
     * default is flagged — so that "All Accounts" and the default bank always
     * agree, as the user expects. Selecting a non-default account excludes them.
     */
    public static function filterClosure(?int $bankAccountId, int $schoolId, string $column = 'bank_account_id'): \Closure
    {
        $holdsUnassigned = $bankAccountId && self::accountHoldsUnassignedFinances($bankAccountId, $schoolId);

        return function ($query) use ($bankAccountId, $column, $holdsUnassigned) {
            if (! $bankAccountId) {
                return;
            }

            $query->where(function ($q) use ($column, $bankAccountId, $holdsUnassigned) {
                $q->where($column, $bankAccountId);

                if ($holdsUnassigned) {
                    $q->orWhereNull($column);
                }
            });
        };
    }

    /**
     * Resolved once per account so the analytics engine does not repeat the same
     * lookup on every chart query.
     */
    private static array $unassignedHolderCache = [];

    protected static function accountHoldsUnassignedFinances(int $bankAccountId, int $schoolId): bool
    {
        $key = $schoolId.':'.$bankAccountId;

        if (! array_key_exists($key, static::$unassignedHolderCache)) {
            $selected = static::withoutTenantScope()->where('school_id', $schoolId)->where('id', $bankAccountId)->first();

            $isDefault = (bool) ($selected?->is_default);

            if (! $isDefault && $selected) {
                // A single account is implicitly the default: unassigned money
                // cannot have been meant for any other bank.
                $isDefault = static::withoutTenantScope()->where('school_id', $schoolId)->count() === 1;
            }

            static::$unassignedHolderCache[$key] = $isDefault;
        }

        return static::$unassignedHolderCache[$key];
    }
}
