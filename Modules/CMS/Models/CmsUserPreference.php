<?php

namespace Modules\CMS\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsUserPreference extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'user_id',
        'editor_settings',
        'preview_settings',
        'block_favorites',
    ];

    protected $casts = [
        'editor_settings' => 'array',
        'preview_settings' => 'array',
        'block_favorites' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getEditorSetting(string $key, $default = null)
    {
        return data_get($this->editor_settings, $key, $default);
    }

    public function setEditorSetting(string $key, $value): void
    {
        $settings = $this->editor_settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['editor_settings' => $settings]);
    }

    public function toggleBlockFavorite(string $blockType): void
    {
        $favorites = $this->block_favorites ?? [];
        $key = array_search($blockType, $favorites);

        if ($key !== false) {
            unset($favorites[$key]);
        } else {
            $favorites[] = $blockType;
        }

        $this->update(['block_favorites' => array_values($favorites)]);
    }

    public function isBlockFavorite(string $blockType): bool
    {
        return in_array($blockType, $this->block_favorites ?? []);
    }
}
