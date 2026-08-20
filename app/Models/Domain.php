<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Domain extends Model
{
    protected $fillable = [
        'school_id',
        'domain',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $domain) {
            Cache::forget("tenant.domain:{$domain->domain}");
        });

        static::deleted(function (self $domain) {
            Cache::forget("tenant.domain:{$domain->domain}");
        });
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
