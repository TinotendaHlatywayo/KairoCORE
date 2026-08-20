<?php

declare(strict_types=1);

namespace Modules\Library\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Students\Models\Student;

class LibraryIssue extends Model
{
    use BelongsToTenant;

    protected $table = 'library_issues';

    protected $fillable = [
        'school_id',
        'library_book_copy_id',
        'student_id',
        'user_id',
        'issued_by_id',
        'issued_at',
        'due_at',
        'returned_at',
        'status',
        'fine_amount',
        'fine_status',
        'renewals_count',
        'notes',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_at' => 'date',
        'returned_at' => 'date',
        'fine_amount' => 'decimal:2',
    ];

    // AUTOMATED COPY STATUS SYNCHRONIZATION
    protected static function booted(): void
    {
        static::created(function ($issue) {
            $copy = $issue->copy;
            if ($copy) {
                $copy->update(['status' => 'issued']);
            }
        });

        static::updated(function ($issue) {
            $copy = $issue->copy;
            if ($copy) {
                if ($issue->status === 'returned' && $issue->wasChanged('status')) {
                    $copy->update(['status' => 'available']);
                } elseif ($issue->status === 'lost' && $issue->wasChanged('status')) {
                    $copy->update(['status' => 'lost']);
                }
            }
        });

        static::deleted(function ($issue) {
            $copy = $issue->copy;
            if ($copy) {
                $copy->update(['status' => 'available']);
            }
        });
    }

    public function copy(): BelongsTo
    {
        return $this->belongsTo(LibraryBookCopy::class, 'library_book_copy_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function borrowerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }
}
