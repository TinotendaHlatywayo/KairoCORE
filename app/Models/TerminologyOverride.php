<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TerminologyOverride extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'key', 'value'];
}
