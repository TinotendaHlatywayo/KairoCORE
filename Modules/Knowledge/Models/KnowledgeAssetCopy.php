<?php

declare(strict_types=1);

namespace Modules\Knowledge\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeAssetCopy extends Model
{
    use BelongsToTenant;

    protected $table = 'knowledge_asset_copies';

    protected $fillable = [
        'school_id',
        'knowledge_asset_id',
        'barcode',
        'qr_code',
        'shelf',
        'rack',
        'position',
        'condition',
        'status',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(KnowledgeAsset::class, 'knowledge_asset_id');
    }
}
