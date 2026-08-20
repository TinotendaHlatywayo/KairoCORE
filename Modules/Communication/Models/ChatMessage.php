<?php

namespace Modules\Communication\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use BelongsToTenant;

    protected $table = 'communication_chat_messages';

    protected $fillable = [
        'school_id',
        'thread_id',
        'sender_id',
        'message',
        'attachments',
        'reactions',
    ];

    protected $casts = [
        'attachments' => 'array',
        'reactions' => 'array',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class, 'thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
