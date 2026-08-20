<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSubscription extends Model
{
    protected $fillable = [
        'school_id',
        'plan_name',
        'amount',
        'currency',
        'status',
        'starts_at',
        'ends_at',
        'payment_proof_path',
        'confirmed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
