<?php

namespace Modules\Reports\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Executes a saved enterprise report configuration against the database.
 *
 * Design:
 *  - The template's first dataset is the PRIMARY source; every other dataset is
 *    joined through the connection graph (BFS from the primary over enabled edges).
 *  - Table datasets are joined as aliased tables and always tenant-scoped
 *    (`alias.school_id = ?`). Derived ("baseSql") datasets are embedded as
 *    tenant-scoped subqueries (the {school_id} placeholder is replaced at build time).
 *  - Field keys in the config are fully qualified as `dataset_key.field_key`;
 *    expressions reference the alias prefixes declared by the provider.
 */
class ReportQueryBuilder
{
    public function __construct(protected DatasetRegistry $registry) {}

    /**
     * @param  array  $config  normalized template config (config_version 2)
     */
    public function build(array $config, array $runtimeFilters = []): Builder
    {
        $datasets = $config['datasets'] ?? [];
        if (empty($datasets)) {
            throw new \InvalidArgumentException('Report config has no datasets.');
        }

        $primaryKey = $datasets[0];
        $primary = $this->requireDataset($primaryKey);

        $schoolId = $this->resolveSchoolId();
        $query = $this->baseQuery($primary, $schoolId);
        $this->applyAutoJoins($query, $primary, $schoolId);

        $joined = [$primaryKey];
        $edges = $config['joins'] ?? [];
        $available = array_flip($datasets);

        // BFS join graph so multi-dataset templates resolve in dependency order.
        for ($pass = 0; $pass <= count($datasets); $pass++) {
            $progress = false;

            foreach ($edges as $edge) {
                $from = $edge['from'] ?? null;
                $to = $edge['to'] ?? null;

                if (in_array($from, $joined) && ! in_array($to, $joined) && isset($available[$to])) {
                    $this->joinDataset($query, $to, $from, $schoolId, $edge['type'] ?? 'left');
                    $joined[] = $to;
                    $progress = true;
                } elseif (in_array($to, $joined) && ! in_array($from, $joined) && isset($available[$from])) {
                    $this->joinDataset($query, $from, $to, $schoolId, $edge['type'] ?? 'left');
                    $joined[] = $from;
                    $progress = true;
                }
            }

            if (! $progress) {
                break;
            }
        }

        $this->applySelect($query, $config);
        $this->applyFilters($query, $config, $runtimeFilters);
        $this->applyGrouping($query, $config);
        $this->applyCalculations($query, $config);
        $this->applySorting($query, $config);

        return $query;
    }

    public function requireDataset(string $key): array
    {
        $def = $this->registry->byKey($key);

        if (! $def) {
            throw new \InvalidArgumentException("Unknown report dataset: [{$key}].");
        }

        return $def;
    }

    protected function baseQuery(array $dataset, int $schoolId): Builder
    {
        if (isset($dataset['baseSql'])) {
            $sql = str_replace('{school_id}', (string) $schoolId, $dataset['baseSql']);

            return DB::query()->from(DB::raw("({$sql}) as {$dataset['alias']}"));
        }

        $table = $dataset['table'] ?? null;
        if (! $table) {
            throw new \InvalidArgumentException("Dataset [{$dataset['key']}] has neither table nor baseSql.");
        }

        return DB::table("{$table} as {$dataset['alias']}")
            ->where("{$dataset['alias']}.school_id", $schoolId);
    }

    protected function applyAutoJoins(Builder $query, array $dataset, int $schoolId): void
    {
        foreach ($dataset['autoJoins'] ?? [] as $join) {
            $this->addTableJoin(
                $query,
                $join['table'],
                $join['alias'],
                $join['type'] ?? 'left',
                $join['on'] ?? [],
                null,
                $join['latest'] ?? false,
                $schoolId
            );
        }
    }

    /**
     * Join a secondary dataset onto an already-present dataset.
     */
    protected function joinDataset(Builder $query, string $toKey, string $presentKey, int $schoolId, string $type): void
    {
        $toDef = $this->requireDataset($toKey);
        $presentDef = $this->requireDataset($presentKey);

        // Locate the connection edge on either endpoint.
        $edge = $this->findConnection($presentDef, $toKey) ?? $this->findConnection($toDef, $presentKey);

        if (! $edge) {
            throw new \InvalidArgumentException("No join path between [{$presentKey}] and [{$toKey}].");
        }

        if (isset($toDef['baseSql'])) {
            // Derived dataset → embed as tenant-scoped subquery.
            $sql = str_replace('{school_id}', (string) $schoolId, $toDef['baseSql']);

            $query->join(
                DB::raw("({$sql}) as {$toDef['alias']}"),
                fn (JoinClause $join) => $this->attachEdgeOn($join, $edge),
                null,
                null,
                $type
            );
        } else {
            $this->addTableJoin(
                $query,
                $toDef['table'],
                $toDef['alias'],
                $type,
                null,
                $edge,
                false,
                $schoolId
            );
            $this->applyAutoJoins($query, $toDef, $schoolId);
        }
    }

    /**
     * Unified join helper. Either $onPairs (declarative `[left, right]` pairs)
     * or a semantic $edge (connection metadata) drives the join condition;
     * the tenant scope is always appended.
     */
    protected function addTableJoin(Builder $query, string $table, string $alias, string $type, ?array $onPairs, ?array $edge, bool $latest, int $schoolId): void
    {
        $scoped = $this->tableHasTenantColumn($table);

        $callback = function (JoinClause $join) use ($table, $onPairs, $edge, $latest, $alias, $schoolId, $scoped) {
            if ($edge) {
                $this->attachEdgeOn($join, $edge);
            } elseif ($onPairs) {
                foreach ($onPairs as $pair) {
                    $join->on($pair[0], '=', $pair[1]);
                }

                if ($latest && count($onPairs) === 1) {
                    [$child, $parent] = $onPairs[0];
                    [$parentAlias, $parentCol] = explode('.', $parent);
                    $childCol = explode('.', $child)[1];

                    $join->whereRaw(
                        "{$alias}.id = (SELECT MAX(inn.id) FROM {$table} inn WHERE inn.{$childCol} = {$parentAlias}.{$parentCol})"
                    );
                }
            }

            if ($scoped) {
                $join->where("{$alias}.school_id", '=', $schoolId);
            }
        };

        $query->join("{$table} as {$alias}", $callback, null, null, $type);
    }

    /**
     * Pivot/intermediary tables may omit school_id; tenant-scoping is applied
     * only where the column physically exists (cached per process).
     */
    protected array $tenantColumnCache = [];

    protected function tableHasTenantColumn(string $table): bool
    {
        if (! array_key_exists($table, $this->tenantColumnCache)) {
            $this->tenantColumnCache[$table] = Schema::hasColumn($table, 'school_id');
        }

        return $this->tenantColumnCache[$table];
    }

    /**
     * Attach a connection edge's fields onto a JoinClause as an equality.
     *
     * Edge semantics:
     *  - 'from'      → field on the owning dataset's alias
     *  - 'to'        → target dataset key
     *  - 'to_fields' → fields on the target dataset's alias
     */
    protected function attachEdgeOn(JoinClause $join, array $edge): void
    {
        $toDef = $this->registry->byKey($edge['to']);
        $toAlias = $toDef['alias'] ?? $edge['to'];

        $fromField = $edge['from'][0];
        $toField = $edge['to_fields'][0];

        $join->on($fromField, '=', $toAlias.'.'.explode('.', $toField)[1]);
    }

    protected function findConnection(array $dataset, string $targetKey): ?array
    {
        foreach ($dataset['connections'] ?? [] as $connection) {
            if (($connection['to'] ?? null) === $targetKey) {
                return $connection;
            }
        }

        return null;
    }

    protected function applySelect(Builder $query, array $config): void
    {
        $fields = $config['selected_fields'] ?? [];
        if (empty($fields)) {
            throw new \InvalidArgumentException('No output fields selected.');
        }

        $groupExprs = $this->groupExpressions($config);

        foreach ($fields as $qualifiedKey) {
            $expr = $this->resolveFieldExpression($qualifiedKey);

            // Under MySQL ONLY_FULL_GROUP_BY every selected column must be a
            // grouping expression or an aggregate; non-grouped columns are
            // wrapped in MAX() as a representative value.
            if (! empty($groupExprs) && ! $this->isGroupExpression($expr, $groupExprs)) {
                $expr = "MAX({$expr})";
            }

            $query->addSelect(DB::raw("{$expr} as {$this->plainKey($qualifiedKey)}"));
        }
    }

    protected function applyFilters(Builder $query, array $config, array $runtimeFilters = []): void
    {
        foreach (array_merge($config['filters'] ?? [], $runtimeFilters) as $filter) {
            $key = $this->qualify($filter['dataset'] ?? null, $filter['key'] ?? null);
            $op = $filter['op'] ?? 'eq';
            $value = $filter['value'] ?? null;
            $boolean = ($filter['boolean'] ?? 'and') === 'or' ? 'or' : 'and';

            $expr = $this->resolveFieldExpression($key);

            match ($op) {
                'eq' => $query->where(DB::raw($expr), '=', $value, $boolean),
                'neq' => $query->where(DB::raw($expr), '!=', $value, $boolean),
                'gt' => $query->where(DB::raw($expr), '>', $value, $boolean),
                'gte' => $query->where(DB::raw($expr), '>=', $value, $boolean),
                'lt' => $query->where(DB::raw($expr), '<', $value, $boolean),
                'lte' => $query->where(DB::raw($expr), '<=', $value, $boolean),
                'contains' => $query->whereRaw("LOWER({$expr}) LIKE ?", ['%'.mb_strtolower((string) $value).'%'], $boolean),
                'starts' => $query->whereRaw("LOWER({$expr}) LIKE ?", [mb_strtolower((string) $value).'%'], $boolean),
                'ends' => $query->whereRaw("LOWER({$expr}) LIKE ?", ['%'.mb_strtolower((string) $value)], $boolean),
                'in' => $query->whereIn(DB::raw($expr), (array) $value, $boolean),
                'not_in' => $query->whereNotIn(DB::raw($expr), (array) $value, $boolean),
                'is_null' => $query->whereNull(DB::raw($expr), $boolean),
                'is_not_null' => $query->whereNotNull(DB::raw($expr), $boolean),
                'between' => $query->whereBetween(DB::raw($expr), (array) $value, $boolean),
                default => null,
            };
        }
    }

    protected function applyGrouping(Builder $query, array $config): void
    {
        foreach ($this->groupExpressions($config) as $expr) {
            $query->groupBy(DB::raw($expr));
        }
    }

    protected function groupExpressions(array $config): array
    {
        $exprs = [];

        foreach ($config['grouping'] ?? [] as $qualifiedKey) {
            $exprs[] = $this->resolveFieldExpression($qualifiedKey);
        }

        return $exprs;
    }

    protected function isGroupExpression(string $expr, array $groupExprs): bool
    {
        return in_array($expr, $groupExprs, true);
    }

    protected function applyCalculations(Builder $query, array $config): void
    {
        $grouped = ! empty($this->groupExpressions($config));

        foreach ($config['calculations'] ?? [] as $calc) {
            $type = strtoupper($calc['type'] ?? 'sum');
            $fieldKey = $this->qualify($calc['dataset'] ?? null, $calc['field'] ?? null);
            $alias = $calc['alias'] ?? strtolower($type).'_'.$this->plainKey($fieldKey);

            $expr = $this->resolveFieldExpression($fieldKey);

            $fn = match ($type) {
                'COUNT' => 'COUNT',
                'AVG' => 'AVG',
                'MIN' => 'MIN',
                'MAX' => 'MAX',
                default => 'SUM',
            };

            // Detail rows carrying a grand total need a window aggregate;
            // a plain aggregate is only legal alongside GROUP BY.
            $aggregate = $grouped ? "{$fn}({$expr})" : "{$fn}({$expr}) OVER ()";

            $query->addSelect(DB::raw("{$aggregate} as {$alias}"));
        }
    }

    protected function applySorting(Builder $query, array $config): void
    {
        $groupExprs = $this->groupExpressions($config);

        foreach ($config['sorting'] ?? [] as $sort) {
            $direction = strtolower($sort['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

            if (! empty($sort['calculation'])) {
                $query->orderBy($sort['calculation'], $direction);

                continue;
            }

            $fieldKey = $this->qualify($sort['dataset'] ?? null, $sort['field'] ?? null);
            $expr = $this->resolveFieldExpression($fieldKey);

            // Mirror the SELECT wrapping so ORDER BY stays group-compatible.
            if (! empty($groupExprs) && ! $this->isGroupExpression($expr, $groupExprs)) {
                $expr = "MAX({$expr})";
            }

            $query->orderBy(DB::raw($expr), $direction);
        }
    }

    /**
     * Resolve a qualified `dataset.field` key into a SQL expression, honoring
     * provider-declared expressions, or defaulting to `alias.column`.
     */
    public function resolveFieldExpression(?string $qualifiedKey): string
    {
        if (! $qualifiedKey) {
            return '*';
        }

        [$datasetKey, $fieldKey] = $this->split($qualifiedKey);

        if (! $datasetKey) {
            return $qualifiedKey;
        }

        $field = $this->fieldInfo($qualifiedKey);

        if (! $field) {
            return "{$this->registry->byKey($datasetKey)['alias']}.{$fieldKey}";
        }

        return $field['expression'] ?? "{$this->registry->byKey($datasetKey)['alias']}.{$fieldKey}";
    }

    /**
     * Return the provider field definition for a qualified key, if known.
     */
    public function fieldInfo(?string $qualifiedKey): ?array
    {
        if (! $qualifiedKey) {
            return null;
        }

        [$datasetKey, $fieldKey] = $this->split($qualifiedKey);

        if (! $datasetKey) {
            return null;
        }

        $dataset = $this->registry->byKey($datasetKey);

        if (! $dataset) {
            return null;
        }

        foreach ($dataset['fields'] as $field) {
            if (($field['key'] ?? null) === $fieldKey) {
                return $field;
            }
        }

        return null;
    }

    protected function qualify(?string $datasetKey, ?string $fieldKey): string
    {
        if (! $fieldKey) {
            return (string) $datasetKey;
        }

        return $datasetKey ? "{$datasetKey}.{$fieldKey}" : $fieldKey;
    }

    /**
     * Split a qualified `dataset.field` key. Dataset keys themselves contain a
     * dot (e.g. `finance.balance`), so the longest known dataset prefix wins.
     */
    protected function split(string $qualifiedKey): array
    {
        $best = null;

        foreach ($this->registry->keys() as $datasetKey) {
            if (str_starts_with($qualifiedKey, $datasetKey.'.')) {
                $best = $datasetKey;
            }
        }

        if ($best) {
            return [$best, substr($qualifiedKey, strlen($best) + 1)];
        }

        return [null, $qualifiedKey];
    }

    protected function plainKey(string $qualifiedKey): string
    {
        [, $fieldKey] = $this->split($qualifiedKey);

        return $fieldKey ?: $qualifiedKey;
    }

    public function qualifiedFieldKey(string $qualifiedKey): string
    {
        return $this->plainKey($qualifiedKey);
    }

    public function datasetOf(string $qualifiedKey): ?string
    {
        [$datasetKey] = $this->split($qualifiedKey);

        return $datasetKey;
    }

    protected function resolveSchoolId(): int
    {
        if (app()->bound('current_tenant') && app('current_tenant')) {
            return (int) app('current_tenant')->id;
        }

        $tenant = session('current_tenant');

        if ($tenant) {
            return (int) $tenant->id;
        }

        /** @var User|null $user */
        $user = Auth::user();

        if ($user && $user->school_id) {
            return (int) $user->school_id;
        }

        throw new \RuntimeException('Could not resolve tenant scope for report execution.');
    }
}
