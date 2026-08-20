<?php

declare(strict_types=1);

namespace Modules\Knowledge\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeAssetVersion extends Model
{
    use BelongsToTenant;

    protected $table = 'knowledge_asset_versions';

    protected $fillable = [
        'school_id',
        'knowledge_asset_id',
        'version_number',
        'file_path',
        'file_size',
        'mime_type',
        'change_log',
        'uploaded_by_id',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(KnowledgeAsset::class, 'knowledge_asset_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
