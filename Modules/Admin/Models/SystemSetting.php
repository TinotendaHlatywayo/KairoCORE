<?php

namespace Modules\Admin\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use BelongsToTenant;

    /**
     * Per-request in-memory snapshot of every setting row for the active
     * tenant. Keyed by "group.key" so the dozens of SystemSetting::get()
     * calls per page render resolve without hitting the database each time.
     *
     * @var array<int, array<string, mixed>>
     */
    protected static array $requestSnapshot = [];

    protected $fillable = [
        'school_id',
        'group',
        'key',
        'value',
    ];

    /**
     * Any direct write (updateOrCreate/update/delete) must invalidate the
     * per-request snapshot, otherwise a same-request read returns stale data.
     */
    public static function booted(): void
    {
        static::saved(function (self $model) {
            unset(self::$requestSnapshot[(int) $model->school_id]);
        });

        static::deleted(function (self $model) {
            unset(self::$requestSnapshot[(int) $model->school_id]);
        });
    }

    /**
     * Set a single setting value.
     */
    public static function set(string $group, string $key, mixed $value): self
    {
        $setting = self::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value]
        );

        self::forget($group, $key);

        return $setting;
    }

    /**
     * Get a single setting value.
     */
    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        $schoolId = self::activeSchoolId();

        if ($schoolId !== null) {
            self::loadSnapshot($schoolId);
        }

        if ($schoolId !== null && array_key_exists($group.'.'.$key, self::$requestSnapshot[$schoolId])) {
            $val = self::$requestSnapshot[$schoolId][$group.'.'.$key];
        } else {
            $setting = self::query()
                ->where('group', $group)
                ->where('key', $key)
                ->first();

            if (! $setting) {
                return $default;
            }

            $val = $setting->value;
        }

        $decoded = json_decode((string) $val, true);

        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $val;
    }

    /**
     * Drop the cached value for a specific key (after a write).
     */
    public static function forget(string $group, string $key): void
    {
        $schoolId = self::activeSchoolId();

        if ($schoolId !== null && isset(self::$requestSnapshot[$schoolId])) {
            unset(self::$requestSnapshot[$schoolId][$group.'.'.$key]);
        }
    }

    /**
     * Clear the per-request snapshot entirely.
     */
    public static function flushSnapshot(): void
    {
        self::$requestSnapshot = [];
    }

    /**
     * The tenant bound to the current request/context, or null in global
     * super-admin context where tenant scoping is disabled.
     */
    protected static function activeSchoolId(): ?int
    {
        return app()->has('current_tenant') ? app('current_tenant')->id : null;
    }

    /**
     * Load all settings for the active tenant into the per-request snapshot.
     */
    protected static function loadSnapshot(int $schoolId): void
    {
        if (isset(self::$requestSnapshot[$schoolId])) {
            return;
        }

        $snapshot = [];
        foreach (self::query()->where('school_id', $schoolId)->get(['group', 'key', 'value']) as $setting) {
            $snapshot[$setting->group.'.'.$setting->key] = $setting->value;
        }

        self::$requestSnapshot[$schoolId] = $snapshot;
    }
}
