<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class FinancialStatementEntry extends Model
{
    use BelongsToTenant;

    public const TYPE_FEE_INCOME = 'fee_income';

    public const TYPE_OTHER_INCOME = 'other_income';

    public const TYPE_REFUND = 'refund';

    public const TYPE_EXPENSE = 'expense';

    protected $fillable = [
        'school_id',
        'bank_account_id',
        'entry_date',
        'type',
        'category',
        'description',
        'amount',
        'reference',
        'notes',
        'source',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public static function typeLabels(): array
    {
        return [
            self::TYPE_FEE_INCOME => __('School Fees Income'),
            self::TYPE_OTHER_INCOME => __('Other Income'),
            self::TYPE_REFUND => __('Refund'),
            self::TYPE_EXPENSE => __('Expense'),
        ];
    }

    public function bankAccount()
    {
        return $this->belongsTo(SchoolBankAccount::class, 'bank_account_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
