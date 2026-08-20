<?php

namespace Modules\CMS\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A named, reusable website bundle (e.g. "school1"). Each template wraps a
 * shadow CmsWebsite holding the template's global theme, structure and
 * per-page design. The school may create many templates but only one is
 * active on the public live website at a time.
 */
class CmsSiteTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'thumbnail',
        'cms_website_id',
    ];

    protected $casts = [];

    public function website(): BelongsTo
    {
        return $this->belongsTo(CmsWebsite::class, 'cms_website_id');
    }

    /** Resolve the live (non-template) website for the current tenant. */
    public static function liveWebsite(): ?CmsWebsite
    {
        $schoolId = app('current_tenant')->id ?? null;
        if (! $schoolId) {
            return null;
        }

        return CmsWebsite::query()
            ->where('school_id', $schoolId)
            ->where('is_template_site', false)
            ->orderBy('id')
            ->first();
    }

    /** The currently active template for the tenant's live website, if any. */
    public static function active(): ?self
    {
        $live = self::liveWebsite();

        return $live && $live->active_site_template_id
            ? self::query()->find($live->active_site_template_id)
            : null;
    }
}
