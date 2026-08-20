<?php

namespace Modules\Finance\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Term;
use Modules\Students\Models\Student;

class Invoice extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'student_id',
        'academic_year_id',
        'term_id',
        'fee_waiver_id',
        'invoice_number',
        'currency',
        'subtotal_amount',
        'discount_amount',
        'waiver_details',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'status',
        'is_locked',
        'due_date',
    ];

    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'due_date' => 'date',
        'is_locked' => 'boolean',
    ];

    protected static function booted()
    {
        parent::booted();

        static::saving(function ($invoice) {
            // 1. Force recalculation of net totals
            $invoice->total_amount = max(0, $invoice->subtotal_amount - $invoice->discount_amount);

            // 2. Recalculate outstanding balance
            $invoice->balance_amount = max(0, $invoice->total_amount - $invoice->paid_amount);

            // 3. Auto-adjust status based on balances
            if ($invoice->balance_amount <= 0) {
                $invoice->status = 'paid';
            } elseif ($invoice->paid_amount > 0) {
                $invoice->status = 'partially_paid';
            } else {
                $invoice->status = 'unpaid';
            }

            // 4. Generate unique tamper-proof signature if empty. For brand-new
            //    records the primary key is not available yet, so a temporary
            //    hash is stored here and recomputed with the real id on created.
            if (empty($invoice->integrity_hash)) {
                $invoice->integrity_hash = $invoice->id
                    ? hash_hmac('sha256', $invoice->id.$invoice->invoice_number, config('app.key'))
                    : bin2hex(random_bytes(32));
            }
        });

        static::created(function ($invoice) {
            $finalHash = hash_hmac('sha256', $invoice->id.$invoice->invoice_number, config('app.key'));
            if ($invoice->integrity_hash !== $finalHash) {
                $invoice->updateQuietly(['integrity_hash' => $finalHash]);
            }
        });
    }

    // FIX: Generates a secure validation signature, falling back to runtime calculation if column is empty
    public function getIntegrityHashAttribute()
    {
        return $this->attributes['integrity_hash'] ?? hash_hmac('sha256', $this->id.$this->invoice_number, config('app.key'));
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function waiver()
    {
        return $this->belongsTo(FeeWaiver::class, 'fee_waiver_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
