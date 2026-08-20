<?php

namespace Modules\SaaS\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $table = 'platform_settings';

    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    public static function set(string $group, string $key, mixed $value): self
    {
        return self::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value]
        );
    }

    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        $setting = self::where('group', $group)->where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        $decoded = json_decode($setting->value, true);

        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $setting->value;
    }
}
