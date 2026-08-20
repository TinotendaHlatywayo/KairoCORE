<?php

namespace Modules\CMS\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $school_id
 * @property int $cms_page_id
 * @property int $cms_website_id
 * @property int $version_number
 * @property string $title
 * @property string $slug
 * @property array $blocks
 * @property string|null $change_summary
 * @property int|null $created_by
 */
class CmsPageVersion extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'cms_page_id',
        'cms_website_id',
        'version_number',
        'title',
        'slug',
        'blocks',
        'page_settings',
        'seo_data',
        'change_summary',
        'created_by',
        'created_by_type',
        'is_autosave',
    ];

    protected $casts = [
        'blocks' => 'array',
        'page_settings' => 'array',
        'seo_data' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'cms_page_id');
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(CmsWebsite::class, 'cms_website_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
