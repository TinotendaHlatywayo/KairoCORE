<?php

declare(strict_types=1);

namespace Modules\Library\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LibraryAuthor extends Model
{
    use BelongsToTenant;

    protected $table = 'library_authors';

    protected $fillable = [
        'school_id',
        'name',
        'bio',
    ];

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(
            LibraryBook::class,
            'library_book_author',
            'library_author_id',
            'library_book_id'
        )->withTimestamps();
    }
}
