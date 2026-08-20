<?php

declare(strict_types=1);

namespace Modules\Library\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryBook extends Model
{
    use BelongsToTenant;

    protected $table = 'library_books';

    protected $fillable = [
        'school_id',
        'library_category_id',
        'library_format_id',
        'title',
        'subtitle',
        'publisher',
        'publication_year',
        'isbn',
        'language',
        'subject',
        'grade_level',
        'media_type',
        'external_url',
        'file_path',
        'cover_image_path',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(
            LibraryAuthor::class,
            'library_book_author',
            'library_book_id',
            'library_author_id'
        )->withTimestamps();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LibraryCategory::class, 'library_category_id');
    }

    public function format(): BelongsTo
    {
        return $this->belongsTo(LibraryFormat::class, 'library_format_id');
    }

    public function copies(): HasMany
    {
        return $this->hasMany(LibraryBookCopy::class, 'library_book_id');
    }

    // RESOURCE QUANTITY COUNTERS (DEDUCTIVE FOR DIGITAL)
    public function getTotalCopiesCount(): int
    {
        if ($this->media_type === 'digital') {
            return 1;
        }

        return $this->copies()->count();
    }

    public function getAvailableCopiesCount(): int
    {
        if ($this->media_type === 'digital') {
            return 1;
        }

        return $this->copies()->where('status', 'available')->count();
    }

    public function getDamagedCopiesCount(): int
    {
        if ($this->media_type === 'digital') {
            return 0;
        }

        return $this->copies()->where('condition', 'damaged')->count();
    }

    public function getLostCopiesCount(): int
    {
        if ($this->media_type === 'digital') {
            return 0;
        }

        return $this->copies()->where('status', 'lost')->count();
    }
}
