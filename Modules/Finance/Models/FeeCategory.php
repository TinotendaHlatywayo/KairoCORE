<?php

namespace Modules\Finance\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class FeeCategory extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'name', 'description'];

    public function structures()
    {
        return $this->hasMany(FeeStructure::class);
    }
}
