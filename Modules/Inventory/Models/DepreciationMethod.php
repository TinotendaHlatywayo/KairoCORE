<?php

namespace Modules\Inventory\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DepreciationMethod extends Model
{
    use BelongsToTenant;

    protected $table = 'depreciation_methods';

    protected $fillable = [
        'school_id',
        'name',
        'key',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Methods the depreciation engine can actually calculate a schedule for.
     * Everything else is a school-defined label the school records for its own
     * bookkeeping but that the engine does not compute.
     */
    public static function supportedKeys(): array
    {
        return ['straight_line', 'double_declining'];
    }

    /**
     * The options every school starts with, so the select is never empty.
     *
     * @return array<string, string>
     */
    public static function systemMethods(): array
    {
        return [
            'straight_line' => __('Straight Line'),
            'double_declining' => __('Double Declining'),
        ];
    }

    /**
     * Ensure the school has the built-in methods available, creating them on
     * first use. Mirrors how inventory categories are auto-provisioned.
     */
    public static function ensureSystemMethods(int $schoolId): void
    {
        foreach (self::systemMethods() as $key => $name) {
            self::firstOrCreate(
                ['school_id' => $schoolId, 'key' => $key],
                ['name' => $name, 'is_system' => true, 'is_active' => true]
            );
        }
    }

    /**
     * Active methods for the school as select options, keyed by the value
     * stored on the asset.
     *
     * @return array<string, string>
     */
    public static function optionsForSchool(int $schoolId): array
    {
        self::ensureSystemMethods($schoolId);

        return self::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->pluck('name', 'key')
            ->all();
    }

    /**
     * Create a school-defined method from free text typed into the select.
     */
    public static function createForSchool(int $schoolId, string $name): self
    {
        $name = trim($name);
        $key = Str::slug($name) ?: 'custom_'.Str::random(6);

        // Keep the unique (school_id, key) index satisfied by suffixing collisions.
        $uniqueKey = $key;
        $suffix = 2;
        while (self::withoutTenantScope()->where('school_id', $schoolId)->where('key', $uniqueKey)->exists()) {
            $uniqueKey = $key.'_'.$suffix;
            $suffix++;
        }

        return self::create([
            'school_id' => $schoolId,
            'name' => $name,
            'key' => $uniqueKey,
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    public function isCalculatedByEngine(): bool
    {
        return in_array($this->key, self::supportedKeys(), true);
    }
}
