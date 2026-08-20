<?php

namespace Modules\CMS\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsNavigationMenu extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'cms_website_id',
        'name',
        'handle',
        'location',
        'items',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'items' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(CmsWebsite::class, 'cms_website_id');
    }

    public function getItemsForDisplay(): array
    {
        return $this->items ?? [];
    }
}
