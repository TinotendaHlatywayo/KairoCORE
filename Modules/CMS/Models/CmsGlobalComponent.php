<?php

namespace Modules\CMS\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsGlobalComponent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'cms_website_id',
        'name',
        'type',
        'handle',
        'content',
        'settings',
        'conditions',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'content' => 'array',
        'settings' => 'array',
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(CmsWebsite::class, 'cms_website_id');
    }

    public function shouldDisplay(array $context = []): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (empty($this->conditions)) {
            return true;
        }

        // Check page conditions
        if (isset($this->conditions['pages'])) {
            $currentPage = $context['page'] ?? null;
            if ($currentPage && ! in_array($currentPage->slug, $this->conditions['pages'])) {
                return false;
            }
        }

        // Check user role conditions
        if (isset($this->conditions['roles'])) {
            $user = $context['user'] ?? auth()->user();
            if ($user && ! $user->hasAnyRole($this->conditions['roles'])) {
                return false;
            }
        }

        // Check device conditions
        if (isset($this->conditions['devices'])) {
            // Would need device detection logic
        }

        return true;
    }
}
