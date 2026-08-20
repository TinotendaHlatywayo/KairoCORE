<?php

namespace Modules\Communication\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpdeskTicket extends Model
{
    use BelongsToTenant;

    protected $table = 'communication_helpdesk_tickets';

    protected $fillable = [
        'school_id',
        'ticket_number',
        'user_id',
        'category',
        'subject',
        'description',
        'priority',
        'status',
        'assigned_to_id',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(HelpdeskTicketReply::class, 'ticket_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            // Automatically generate unique, incremental ticket reference key
            $year = date('Y');
            $count = self::where('school_id', $ticket->school_id)
                ->whereYear('created_at', $year)
                ->count() + 1;

            $ticket->ticket_number = 'HD-'.$year.'-'.str_pad($count, 5, '0', STR_PAD_LEFT);
        });
    }
}
