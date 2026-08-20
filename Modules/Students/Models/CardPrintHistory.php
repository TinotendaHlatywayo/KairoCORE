<?php

namespace Modules\Students\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CardPrintHistory extends Model
{
    use BelongsToTenant;

    protected $table = 'card_print_history';

    protected $fillable = [
        'school_id',
        'student_id',
        'card_template_id',
        'serial_number',
        'verification_code',
        'printed_by_id',
        'printed_at',
        'printer_type',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
    ];
}
