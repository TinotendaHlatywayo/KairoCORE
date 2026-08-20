<?php

namespace Modules\Communication\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Auth;

class ChatParticipant extends Pivot
{
    use BelongsToTenant;

    protected $table = 'communication_chat_participants';

    protected $fillable = [
        'school_id',
        'thread_id',
        'user_id',
        'last_read_at',
        'is_muted',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'is_muted' => 'boolean',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class, 'thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($pivot) {
            // Automatically capture and inject the current school context
            if (app()->bound('current_tenant')) {
                $pivot->school_id = app('current_tenant')->id;
            } elseif (Auth::check()) {
                $pivot->school_id = Auth::user()->school_id;
            }
        });
    }
}
