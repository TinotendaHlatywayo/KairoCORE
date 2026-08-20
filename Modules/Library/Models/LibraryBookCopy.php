<?php

declare(strict_types=1);

namespace Modules\Library\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryBookCopy extends Model
{
    use BelongsToTenant;

    protected $table = 'library_book_copies';

    protected $fillable = [
        'school_id',
        'library_book_id',
        'barcode',
        'qr_code',
        'shelf',
        'rack',
        'position',
        'condition',
        'status',
        'purchase_cost',
        'replacement_cost',
        'acquired_date',
    ];

    protected $casts = [
        'acquired_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'replacement_cost' => 'decimal:2',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(LibraryBook::class, 'library_book_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(LibraryIssue::class, 'library_book_copy_id');
    }
}
