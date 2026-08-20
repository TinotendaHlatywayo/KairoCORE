<?php

namespace Modules\Finance\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class RevenueCategory extends Model
{
    use BelongsToTenant;

    protected $table = 'revenue_categories';

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'account_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function streams()
    {
        return $this->hasMany(RevenueStream::class);
    }
}
