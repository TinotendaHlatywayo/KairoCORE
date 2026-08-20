<?php

namespace Modules\Communication\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatThread extends Model
{
    use BelongsToTenant;

    protected $table = 'communication_chat_threads';

    protected $fillable = [
        'school_id',
        'type',
        'name',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'thread_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ChatParticipant::class, 'thread_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'communication_chat_participants', 'thread_id', 'user_id')
            ->using(ChatParticipant::class)
            ->withPivot(['id', 'school_id', 'last_read_at', 'is_muted'])
            ->withTimestamps();
    }
}
