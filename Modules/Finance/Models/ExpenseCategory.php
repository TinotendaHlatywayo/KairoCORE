<?php

namespace Modules\Finance\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use BelongsToTenant;

    protected $table = 'expense_categories';

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'account_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function expenseTypes()
    {
        return $this->hasMany(ExpenseType::class);
    }
}
