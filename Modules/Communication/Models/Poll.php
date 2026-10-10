<?php

namespace Modules\Communication\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    use BelongsToTenant;

    protected $table = 'communication_polls';

    protected $fillable = [
        'school_id',
        'question',
        'description',
        'type',
        'is_anonymous',
        'target_roles',
        'target_user_ids',
        'created_by',
        'expires_at',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'target_roles' => 'array',
        'target_user_ids' => 'array',
        'expires_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class, 'poll_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class, 'poll_id');
    }
}
