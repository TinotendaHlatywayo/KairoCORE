<?php

declare(strict_types=1);

namespace Modules\Knowledge\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeFormat extends Model
{
    use BelongsToTenant;

    protected $table = 'knowledge_formats';

    protected $fillable = [
        'school_id',
        'name',
        'media_type',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(KnowledgeAsset::class, 'knowledge_format_id');
    }
}
