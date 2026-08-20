<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use BelongsToTenant;

    protected $table = 'journal_entries';

    protected $fillable = [
        'school_id',
        'entry_date',
        'reference_number',
        'narration',
        'status', // draft, posted, void
        'user_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function lineItems()
    {
        return $this->hasMany(JournalLineItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
