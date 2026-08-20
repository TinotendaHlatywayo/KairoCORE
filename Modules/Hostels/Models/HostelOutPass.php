<?php

namespace Modules\Hostels\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Students\Models\Student;

class HostelOutPass extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id', 'student_id', 'hostel_id', 'requester_id', 'type', 'status',
        'reason', 'expected_departure', 'expected_return', 'actual_departure',
        'actual_return', 'parent_otp', 'parent_approved_at', 'warden_approver_id',
        'warden_approved_at', 'gate_scanned_at', 'gate_scanner_id', 'qr_code',
    ];

    protected static function booted()
    {
        static::creating(function ($outPass) {
            $outPass->qr_code = 'OP-'.strtoupper(bin2hex(random_bytes(5)));
            $outPass->parent_otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class, 'hostel_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function wardenApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warden_approver_id');
    }
}
