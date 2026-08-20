<?php

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CmsPageTemplate extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'handle',
        'description',
        'category',
        'thumbnail',
        'blocks',
        'page_settings',
        'seo_defaults',
        'is_system',
        'is_school_template',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'blocks' => 'array',
        'page_settings' => 'array',
        'seo_defaults' => 'array',
        'is_system' => 'boolean',
        'is_school_template' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('school-or-system', function ($query): void {
            if (app()->bound('current_tenant')) {
                $query->where(function ($query): void {
                    $query->where('school_id', app('current_tenant')->id)->orWhere('is_system', true);
                });
            }
        });

        static::creating(function (self $template): void {
            if (! $template->school_id && ! $template->is_system && app()->bound('current_tenant')) {
                $template->school_id = app('current_tenant')->id;
            }
        });
    }

    public function applyToPage(CmsPage $page): void
    {
        $page->update([
            'draft_blocks' => $this->blocks,
            'page_template' => $this->handle,
            'page_settings' => array_merge($page->page_settings ?? [], $this->page_settings ?? []),
        ]);
    }

    public static function getByCategory(string $category): Collection
    {
        return static::where('category', $category)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
