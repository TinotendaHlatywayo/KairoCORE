<?php

namespace Modules\Finance\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ExpenseType extends Model
{
    use BelongsToTenant;

    protected $table = 'expense_types';

    protected $fillable = [
        'school_id',
        'expense_category_id',
        'name',
        'account_id',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
