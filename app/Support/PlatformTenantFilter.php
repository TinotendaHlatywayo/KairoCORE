<?php

namespace App\Support;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class PlatformTenantFilter
{
    /** @return array<int, int> */
    public static function excludedSchoolIds(): array
    {
        return School::withoutGlobalScopes()
            ->withTrashed()
            ->where(function ($query): void {
                $query->where('is_demo_sandbox', true)
                    ->orWhere('subdomain', mb_strtolower((string) config('demo.school.subdomain')))
                    ->orWhere('subdomain', 'platform-site');
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public static function isExcluded(?int $schoolId): bool
    {
        return $schoolId !== null && in_array($schoolId, self::excludedSchoolIds(), true);
    }

    public static function excludeSchoolColumn(EloquentBuilder|QueryBuilder $query, string $column = 'school_id'): void
    {
        $ids = self::excludedSchoolIds();

        if ($ids !== []) {
            $query->whereNotIn($column, $ids);
        }
    }

    public static function excludeSchoolsTable(QueryBuilder $query, string $alias = 'schools'): void
    {
        $ids = self::excludedSchoolIds();

        if ($ids !== []) {
            $query->whereNotIn($alias.'.id', $ids);
        }
    }
}
