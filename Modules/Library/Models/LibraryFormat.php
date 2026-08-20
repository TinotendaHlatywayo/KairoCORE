<?php

declare(strict_types=1);

namespace Modules\Library\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryFormat extends Model
{
    use BelongsToTenant;

    protected $table = 'library_formats';

    protected $fillable = [
        'school_id',
        'name',
        'media_type',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(LibraryBook::class, 'library_format_id');
    }
}
