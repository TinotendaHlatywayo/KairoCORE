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
 *  - Every config passes through {@see normalize()} first, which repairs the
 *    shape of a hand-built or legacy config (double-qualified filter keys,
 *    dangling dataset references, unresolvable joins, colliding output aliases)
 *    so the SQL builder below can never emit a reference to an alias that is
 *    not present in the FROM/JOIN clause.
 *  - The template's first dataset is the PRIMARY source; every other dataset is
 *    joined through the connection graph. Edges declared in the config win,
 *    anything the config forgot is auto-resolved by walking the graph.
 *  - Table datasets are joined as aliased tables and always tenant-scoped
 *    (`alias.school_id = ?`) when the table actually carries the column.
 *    Derived ("baseSql") datasets are embedded as tenant-scoped subqueries
 *    (the {school_id} placeholder is replaced at build time).
 *  - Field keys in the config are fully qualified as `dataset_key.field_key`;
 *    expressions reference the alias prefixes declared by the provider.
 */
class ReportQueryBuilder
{
    /**
     * Populated by normalize(); maps a qualified field key to the unique SQL
     * output alias actually used in the SELECT list.
     *
     * @var array<string, string>
     */
    protected array $outputAliases = [];

    /**
     * Maps a calculation's qualified field key to the unique SQL alias it was
     * given. Kept apart from $outputAliases because a calculation and a
     * selected field can target the *same* column (a total of `total_amount`
     * alongside the raw `total_amount`), and they must not share one alias.
     *
     * @var array<string, string>
     */
    protected array $calculationAliases = [];

    /**
     * Maps a calculation's requested alias to the final, uniquified alias, so
     * sorting by a total keeps working after collision handling renamed it.
     *
     * @var array<string, string>
     */
    protected array $calculationAliasLookup = [];

    /**
     * Memoised {@see connectionTargets()} results, keyed by the present dataset.
     * The registry is fixed for the process, so this cannot go stale.
     *
     * @var array<string, array<int, string>>
     */
    protected array $connectionTargetCache = [];

    /**
     * Memoised `dataset.field` → `[dataset, field]` resolutions. Every field,
     * filter, grouping and sort lookup goes through split(), and without this the
     * planner rescans all 52 dataset keys for each one.
     *
     * @var array<string, array{0: ?string, 1: string}>
     */
    protected array $splitCache = [];

    /**
     * Human-readable notes produced by the last normalize() call, describing
     * anything that was repaired or dropped. Surfaced by the UI so a repaired
     * config never fails silently.
     *
     * @var array<int, string>
     */
    protected array $warnings = [];

    public function __construct(protected DatasetRegistry $registry) {}

    /**
     * @param  array  $config  template config (config_version 2)
     */
    public function build(array $config, array $runtimeFilters = []): Builder
    {
        $config = $this->normalize($config, $runtimeFilters);

        $datasets = $config['datasets'];
        $primary = $this->requireDataset($datasets[0]);

        $schoolId = $this->resolveSchoolId();
        $query = $this->baseQuery($primary, $schoolId);
        $this->applyAutoJoins($query, $primary, $schoolId);

        $joined = [$datasets[0]];

        // The join set is already topologically ordered by normalize().
        foreach ($config['joins'] as $edge) {
            $to = $edge['to'];

            if (in_array($to, $joined, true)) {
                continue;
            }

            $this->joinDataset(
                $query,
                $to,
                $edge['from'],
                $schoolId,
                $edge['type'] ?? 'left',
                $edge['connection'] ?? null
            );

            $joined[] = $to;
        }

        $this->applySelect($query, $config);
        $this->applyFilters($query, $config);
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

    /**
     * Repair a config into one that is guaranteed to be executable.
     *
     * Repairs performed, in order:
     *  1. Drop unknown datasets; de-duplicate the list.
     *  2. Resolve the join graph — explicit edges are honoured, every other
     *     selected dataset is auto-joined by walking the connection graph from
     *     the primary. Unreachable datasets are dropped (and noted) instead of
     *     producing an `Unknown column` SQL error.
     *  3. Default the output columns from the primary dataset when none given.
     *  4. Strip any field/filter/group/sort/calculation reference to a dataset
     *     that is no longer part of the query.
     *  5. De-qualify filter keys that are already `dataset.field`.
     *  6. Guarantee unique output aliases so same-named columns from different
     *     datasets cannot overwrite each other in the result set.
     *  7. Fall back to the primary dataset's declared default ordering.
     *
     * @return array{datasets: array, joins: array, selected_fields: array, filters: array, grouping: array, calculations: array, sorting: array, visualizations: array, output_aliases: array}
     */
    public function normalize(array $config, array $runtimeFilters = []): array
    {
        $this->warnings = [];

        $candidates = $this->normalizeDatasets($config['datasets'] ?? []);
        $plan = $this->normalizeJoins($candidates, $config['joins'] ?? []);

        $datasets = $plan['datasets'];
        $joins = $plan['joins'];

        $fields = $this->normalizeFields($datasets, $config['selected_fields'] ?? []);
        $filters = $this->normalizeFilters($datasets, array_merge($config['filters'] ?? [], $runtimeFilters));
        $grouping = $this->normalizeGrouping($datasets, $config['grouping'] ?? []);
        $calculations = $this->normalizeCalculations($datasets, $config['calculations'] ?? []);
        $sorting = $this->normalizeSorting($datasets, $config['sorting'] ?? [], $datasets[0]);

        $aliases = $this->buildOutputAliases($fields, $calculations);

        // Publish each total's final alias on its own entry so downstream
        // consumers address the column the planner actually emitted.
        foreach ($calculations as $index => $calc) {
            $qualifiedKey = $this->qualify($calc['dataset'] ?? null, $calc['key'] ?? null);

            if (isset($this->calculationAliases[$qualifiedKey])) {
                $calculations[$index]['output_alias'] = $this->calculationAliases[$qualifiedKey];
            }
        }

        return [
            'datasets' => $datasets,
            'joins' => $joins,
            'selected_fields' => $fields,
            'filters' => $filters,
            'grouping' => $grouping,
            'calculations' => $calculations,
            'sorting' => $sorting,
            'visualizations' => $config['visualizations'] ?? [],
            'output_aliases' => $aliases,
        ];
    }

    /**
     * Notes emitted by the most recent normalize() call.
     *
     * @return array<int, string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * @return array<string, string>
     */
    public function outputAliases(): array
    {
        return $this->outputAliases;
    }

    public function aliasFor(string $qualifiedKey): string
    {
        return $this->outputAliases[$qualifiedKey] ?? $this->plainKey($qualifiedKey);
    }

    /**
     * Alias → dataset keys usable in the current config. A dataset is usable
     * when it is the primary source or reachable from it over the join graph.
     *
     * @param  array<int, string>  $datasets
     * @return array<int, string>
     */
    public function resolveJoinPlan(array $datasets): array
    {
        $plan = $this->normalizeJoins($this->normalizeDatasets($datasets), []);

        return array_map(
            fn (array $edge) => ['from' => $edge['from'], 'to' => $edge['to'], 'type' => $edge['type']],
            $plan['joins']
        );
    }

    /**
     * Default output columns for a dataset — used when a config selects none,
     * and by the "one-click" report cards.
     *
     * @return array<int, string>
     */
    public function defaultFieldsFor(string $datasetKey): array
    {
        $def = $this->registry->byKey($datasetKey);

        if (! $def) {
            return [];
        }

        $declared = $def['default_fields'] ?? null;

        if (is_array($declared) && $declared !== []) {
            return array_values(array_filter(
                array_map(fn ($f) => $this->qualify($datasetKey, $f), $declared),
                fn ($key) => $this->fieldInfo($key) !== null
            ));
        }

        return array_map(fn ($f) => $this->qualify($datasetKey, $f['key']), $def['fields']);
    }

    /**
     * @param  array<int, string>  $raw
     * @return array<int, string>
     */
    protected function normalizeDatasets(array $raw): array
    {
        $result = [];

        foreach ($raw as $key) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            if (! $this->registry->byKey($key)) {
                $this->warn(__('Removed unknown data source [:key].', ['key' => $key]));

                continue;
            }

            if (! in_array($key, $result, true)) {
                $result[] = $key;
            }
        }

        if (empty($result)) {
            throw new \InvalidArgumentException('Select at least one valid report data source.');
        }

        return $result;
    }

    /**
     * Resolve every selected dataset onto the query.
     *
     * Explicit config edges are honoured first (they define the join type and
     * the exact parent). Any dataset the config forgot is attached by walking
     * the provider-declared connection graph outward from the primary, so a
     * user who simply ticks two sources never produces an `Unknown column`
     * error. Datasets with no path to the primary are dropped and reported.
     *
     * Edges are oriented in build order (`from` is already present, `to` is the
     * dataset being attached) and carry the resolved connection, because the
     * ON clause must be built from whichever endpoint *declares* the
     * relationship — the other direction would reference an enrichment table
     * that has not been introduced yet.
     *
     * @param  array<int, string>  $datasets
     * @return array{joins: array<int, array<string, mixed>>, datasets: array<int, string>}
     */
    protected function normalizeJoins(array $datasets, array $rawEdges): array
    {
        $primary = $datasets[0];
        $edges = [];
        $attached = [$primary => true];

        $add = function (string $presentKey, string $toKey, string $type) use (&$edges, &$attached, $datasets): bool {
            if (isset($attached[$toKey]) || ! in_array($toKey, $datasets, true) || ! in_array($presentKey, $datasets, true)) {
                return false;
            }

            $connection = $this->findConnection($presentKey, $toKey);

            if (! $connection) {
                return false;
            }

            $edges[] = [
                'from' => $presentKey,
                'to' => $toKey,
                'type' => $type,
                'connection' => $connection,
            ];

            $attached[$toKey] = true;

            return true;
        };

        // Pass 1 — explicit edges (the config's intent wins).
        foreach ($rawEdges as $edge) {
            if (! is_array($edge)) {
                continue;
            }

            $from = $edge['from'] ?? null;
            $to = $edge['to'] ?? null;

            if (! is_string($from) || ! is_string($to)) {
                continue;
            }

            $type = $this->edgeType($edge);

            if (isset($attached[$from]) && ! isset($attached[$to])) {
                $add($from, $to, $type);
            } elseif (isset($attached[$to]) && ! isset($attached[$from])) {
                $add($to, $from, $type);
            }
        }

        // Pass 2 — BFS over the connection graph for anything still unattached.
        $frontier = [$primary];

        while ($frontier) {
            $next = [];

            foreach ($frontier as $presentKey) {
                foreach ($this->connectionTargets($presentKey) as $targetKey) {
                    if (! in_array($targetKey, $datasets, true) || isset($attached[$targetKey])) {
                        continue;
                    }

                    if ($add($presentKey, $targetKey, 'left')) {
                        $next[] = $targetKey;
                    }
                }
            }

            $frontier = $next;
        }

        $kept = array_values(array_filter($datasets, fn ($key) => isset($attached[$key])));

        foreach ($datasets as $key) {
            if (! isset($attached[$key])) {
                $this->warn(__('Dropped [:label] — it has no direct relationship to the main data source.', [
                    'label' => $this->registry->byKey($key)['label'] ?? $key,
                ]));
            }
        }

        return ['joins' => $edges, 'datasets' => $kept];
    }

    /**
     * The connection that joins $toKey onto an already-present $presentKey.
     *
     * A connection may be declared on either endpoint, so both candidates are
     * considered — but only one of them is actually expressible as a single
     * equality at the point the join is emitted (see {@see edgeIsResolvable}).
     * Returns the resolved connection, or null when neither direction works.
     */
    public function findConnection(string $presentKey, string $toKey): ?array
    {
        $presentDef = $this->registry->byKey($presentKey);
        $toDef = $this->registry->byKey($toKey);

        if (! $presentDef || ! $toDef) {
            return null;
        }

        foreach ($presentDef['connections'] ?? [] as $connection) {
            if (($connection['to'] ?? null) === $toKey && $this->edgeIsResolvable($connection, $presentKey, $toKey)) {
                return $connection;
            }
        }

        foreach ($toDef['connections'] ?? [] as $connection) {
            if (($connection['to'] ?? null) === $presentKey && $this->edgeIsResolvable($connection, $presentKey, $toKey)) {
                return $connection;
            }
        }

        return null;
    }

    /**
     * Every dataset that can be joined onto $presentKey.
     *
     * The relationship is discovered through {@see findConnection} rather than
     * by reading $presentKey's own `connections` list, because a relationship
     * declared only by the *other* endpoint is equally usable — an equality is
     * symmetric. For example `system.department → hr.employee` lets
     * `hr.employee` be joined onto `system.department`, and the planner must see
     * that in either direction or a two-source report silently loses a source.
     *
     * @return array<int, string>
     */
    protected function connectionTargets(string $presentKey): array
    {
        if (isset($this->connectionTargetCache[$presentKey])) {
            return $this->connectionTargetCache[$presentKey];
        }

        if (! $this->registry->byKey($presentKey)) {
            return $this->connectionTargetCache[$presentKey] = [];
        }

        $targets = [];

        foreach ($this->registry->keys() as $candidateKey) {
            if ($candidateKey !== $presentKey && $this->findConnection($presentKey, $candidateKey)) {
                $targets[] = $candidateKey;
            }
        }

        return $this->connectionTargetCache[$presentKey] = $targets;
    }

    /**
     * Whether a declared connection can be emitted as a single equality at the
     * moment $toKey is joined onto the already-present $presentKey.
     *
     * An equality is symmetric, so it does not matter which endpoint declared
     * it — what matters is only whether both referenced tables exist in the
     * query at the moment the JOIN is emitted:
     *
     *  - $toKey contributes just its own table. Its autoJoins are attached
     *    immediately *after* this JOIN, so a side that reaches through one of
     *    them is a two-hop relationship the engine cannot express, and is
     *    refused;
     *  - $presentKey contributes its own table plus its enrichment tables.
     *
     * At least one side must be $toKey's own table, otherwise the join would
     * connect only tables that were already there and $toKey would contribute
     * nothing to the result.
     */
    protected function edgeIsResolvable(array $connection, string $presentKey, string $toKey): bool
    {
        $fromField = $connection['from'][0] ?? null;
        $toField = $connection['to_fields'][0] ?? null;

        if (! is_string($fromField) || ! is_string($toField)) {
            return false;
        }

        $presentDef = $this->registry->byKey($presentKey);
        $toDef = $this->registry->byKey($toKey);

        if (! $presentDef || ! $toDef) {
            return false;
        }

        [$fromPrefix] = array_pad(explode('.', $fromField, 2), 2, null);
        [$toPrefix] = array_pad(explode('.', $toField, 2), 2, null);

        $inScope = function (?string $prefix) use ($presentDef, $toDef): bool {
            if ($prefix === null || $prefix === '') {
                return false;
            }

            if ($prefix === ($toDef['alias'] ?? null)) {
                return true;
            }

            return $this->aliasAvailable($presentDef, $prefix);
        };

        if (! $inScope($fromPrefix) || ! $inScope($toPrefix)) {
            return false;
        }

        return $fromPrefix === ($toDef['alias'] ?? null)
            || $toPrefix === ($toDef['alias'] ?? null);
    }

    /**
     * Whether a dataset brings the given alias into the query — either as its
     * own table or through one of its declared autoJoins.
     */
    protected function aliasAvailable(array $dataset, ?string $prefix): bool
    {
        if ($prefix === null || $prefix === '') {
            return false;
        }

        if (($dataset['alias'] ?? null) === $prefix) {
            return true;
        }

        foreach ($dataset['autoJoins'] ?? [] as $autoJoin) {
            if (($autoJoin['alias'] ?? null) === $prefix) {
                return true;
            }
        }

        return false;
    }

    protected function edgeType(array $edge): string
    {
        $type = strtolower((string) ($edge['type'] ?? 'left'));

        return in_array($type, ['inner', 'left', 'right', 'cross'], true) ? $type : 'left';
    }

    /**
     * @param  array<int, string>  $datasets
     * @return array<int, string>
     */
    protected function normalizeFields(array $datasets, array $raw): array
    {
        $fields = [];

        foreach ($raw as $key) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            $datasetKey = $this->datasetOf($key);

            if (! $datasetKey || ! in_array($datasetKey, $datasets, true)) {
                continue;
            }

            if (! $this->fieldInfo($key)) {
                $this->warn(__('Removed unknown column [:key].', ['key' => $key]));

                continue;
            }

            if (! in_array($key, $fields, true)) {
                $fields[] = $key;
            }
        }

        if (empty($fields)) {
            $fields = $this->defaultFieldsFor($datasets[0]);
        }

        if (empty($fields)) {
            throw new \InvalidArgumentException('This data source has no output columns available.');
        }

        return $fields;
    }

    /**
     * @param  array<int, string>  $datasets
     * @return array<int, array{dataset: string, key: string, op: string, value: mixed, boolean: string}>
     */
    protected function normalizeFilters(array $datasets, array $raw): array
    {
        $filters = [];

        foreach ($raw as $filter) {
            if (! is_array($filter)) {
                continue;
            }

            // A key may arrive bare (`amount`) or already qualified
            // (`finance.invoice.amount`); re-qualifying the latter produced
            // `finance.invoice.finance.invoice.amount` and a SQL syntax error.
            $datasetKey = is_string($filter['dataset'] ?? null) ? $filter['dataset'] : null;
            $key = $filter['key'] ?? null;

            if (! is_string($key) || $key === '') {
                continue;
            }

            $keyDataset = $this->datasetOf($key);
            $datasetKey = $datasetKey ?: $keyDataset;

            if ($keyDataset && $datasetKey && $keyDataset !== $datasetKey) {
                // Contradictory dataset/key pair — trust the qualified key.
                $datasetKey = $keyDataset;
            }

            if (! $datasetKey || ! in_array($datasetKey, $datasets, true)) {
                continue;
            }

            $fieldKey = $keyDataset ? $this->plainKey($key) : $key;

            if (! $this->fieldInfo("{$datasetKey}.{$fieldKey}")) {
                $this->warn(__('Removed a filter on unknown column [:key].', ['key' => $key]));

                continue;
            }

            $op = (string) ($filter['op'] ?? 'eq');

            $filters[] = [
                'dataset' => $datasetKey,
                'key' => $fieldKey,
                'op' => $op,
                'value' => $filter['value'] ?? null,
                'boolean' => ($filter['boolean'] ?? 'and') === 'or' ? 'or' : 'and',
            ];
        }

        return $filters;
    }

    /**
     * @param  array<int, string>  $datasets
     * @return array<int, string>
     */
    protected function normalizeGrouping(array $datasets, array $raw): array
    {
        $grouping = [];

        foreach ($raw as $key) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            $datasetKey = $this->datasetOf($key);

            if ($datasetKey && in_array($datasetKey, $datasets, true) && $this->fieldInfo($key)) {
                $grouping[] = $key;
            }
        }

        return $grouping;
    }

    /**
     * @param  array<int, string>  $datasets
     * @return array<int, array>
     */
    protected function normalizeCalculations(array $datasets, array $raw): array
    {
        $calculations = [];

        foreach ($raw as $calc) {
            if (! is_array($calc)) {
                continue;
            }

            $datasetKey = $calc['dataset'] ?? null;

            // The stored form is {dataset, key}; the form produced by the older
            // form builder is {dataset, field}, and either may already carry a
            // qualified key. Normalise to `dataset.field` before resolving, or a
            // bare `key` silently resolves to no dataset and the total is lost.
            $rawKey = $calc['key'] ?? $calc['field'] ?? null;
            $key = $rawKey === null
                ? ''
                : $this->qualify(is_string($datasetKey) ? $datasetKey : null, (string) $rawKey);

            if ($key === '') {
                continue;
            }

            $resolved = $this->datasetOf($key);

            if (! $resolved || ! in_array($resolved, $datasets, true)) {
                continue;
            }

            $fieldKey = $this->plainKey($key);
            $type = strtoupper((string) ($calc['type'] ?? 'sum'));

            // Only aggregate functions can be wrapped around a bare column;
            // anything else needs the provider's own expression to stay valid.
            if ($type !== 'COUNT' && ! $this->fieldInfo($key)) {
                $this->warn(__('Removed a total for unknown column [:key].', ['key' => $key]));

                continue;
            }

            $calculations[] = [
                'type' => $type,
                'dataset' => $resolved,
                'key' => $fieldKey,
                'alias' => (string) ($calc['alias'] ?? strtolower($type).'_'.$fieldKey),
            ];
        }

        return $calculations;
    }

    /**
     * @param  array<int, string>  $datasets
     * @param  array<int, array>  $raw
     * @return array<int, array>
     */
    protected function normalizeSorting(array $datasets, array $raw, string $primaryKey): array
    {
        $sorting = [];

        foreach ($raw as $sort) {
            if (! is_array($sort)) {
                continue;
            }

            $direction = strtolower((string) ($sort['direction'] ?? 'asc'));
            $direction = $direction === 'desc' ? 'desc' : 'asc';

            // Sorting by a total references the total's alias, not a field, so
            // it must be handled before field resolution — otherwise it is
            // dropped for having no resolvable key and the report is unordered.
            if (! empty($sort['calculation'])) {
                $sorting[] = [
                    'calculation' => (string) $sort['calculation'],
                    'direction' => $direction,
                ];

                continue;
            }

            $datasetKey = $sort['dataset'] ?? null;
            $rawKey = $sort['key'] ?? $sort['field'] ?? null;
            $key = $rawKey === null
                ? ''
                : $this->qualify(is_string($datasetKey) ? $datasetKey : null, (string) $rawKey);

            if ($key === '') {
                continue;
            }

            $resolved = $this->datasetOf($key);

            if (! $resolved || ! in_array($resolved, $datasets, true)) {
                continue;
            }

            if (! $this->fieldInfo($key)) {
                continue;
            }

            $sorting[] = [
                'dataset' => $resolved,
                'key' => $this->plainKey($key),
                'direction' => $direction === 'desc' ? 'desc' : 'asc',
            ];
        }

        if (empty($sorting)) {
            $fallback = $this->defaultSorting($primaryKey);

            if ($fallback) {
                $sorting[] = $fallback;
            }
        }

        return $sorting;
    }

    /**
     * Honour a provider's `default_order` (`field|direction`) so a report with
     * no explicit sort still reads sensibly (months ascending, newest first).
     *
     * @return array{dataset: string, key: string, direction: string}|null
     */
    protected function defaultSorting(string $datasetKey): ?array
    {
        $def = $this->registry->byKey($datasetKey);
        $order = $def['default_order'] ?? null;

        if (! is_string($order) || $order === '') {
            return null;
        }

        [$field, $direction] = array_pad(explode('|', $order, 2), 2, 'asc');

        $key = $this->qualify($datasetKey, $field);

        if (! $this->fieldInfo($key)) {
            return null;
        }

        return [
            'dataset' => $datasetKey,
            'key' => $field,
            'direction' => strtolower($direction) === 'desc' ? 'desc' : 'asc',
        ];
    }

    /**
     * Assign each selected column a unique SQL alias.
     *
     * Two datasets can both expose a `status` column; without disambiguation the
     * second silently overwrote the first in the result set, so exports and
     * totals read the wrong value.
     *
     * Selected fields are aliased first and keep the plain column name, because
     * that is the stable row key the UI, exports and visualizations resolve
     * against. Totals are then uniquified against them — otherwise
     * `SUM(total_amount)` would overwrite the selected `total_amount` column
     * and the report would quietly show the total twice with one value.
     *
     * @param  array<int, string>  $fields
     * @param  array<int, array>  $calculations
     * @return array<string, string>
     */
    protected function buildOutputAliases(array $fields, array $calculations): array
    {
        $aliases = [];
        $taken = [];

        $claim = function (string $base) use (&$taken): string {
            $candidate = $base;
            $suffix = 2;

            while (isset($taken[$candidate])) {
                $candidate = $base.'_'.$suffix++;
            }

            $taken[$candidate] = true;

            return $candidate;
        };

        foreach ($fields as $key) {
            $aliases[$key] = $claim($this->plainKey($key));
        }

        $this->calculationAliases = [];
        $this->calculationAliasLookup = [];

        foreach ($calculations as $calc) {
            $qualifiedKey = $this->qualify($calc['dataset'] ?? null, $calc['key'] ?? null);
            $requested = $this->sanitizeAlias($calc['alias'] ?? strtolower($calc['type'] ?? 'sum').'_'.($calc['key'] ?? 'value'));

            $alias = $claim($requested);

            $this->calculationAliases[$qualifiedKey] = $alias;
            $this->calculationAliasLookup[$requested] = $alias;

            // Expose the final alias as its own row key so the UI, exports and
            // visualizations can resolve a total by name.
            $aliases[$alias] = $alias;
        }

        $this->outputAliases = $aliases;

        return $aliases;
    }

    /**
     * Reduce an alias to a bare SQL identifier, so a hand-written or
     * locale-derived label can never break the SELECT list.
     */
    protected function sanitizeAlias(string $alias): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_]+/', '_', trim($alias)) ?? '';
        $clean = trim($clean, '_');

        if ($clean === '' || preg_match('/^[0-9]/', $clean) === 1) {
            $clean = 'col_'.$clean;
        }

        return strtolower($clean);
    }

    protected function warn(string $message): void
    {
        $this->warnings[] = $message;
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

        $query = DB::table("{$table} as {$dataset['alias']}");

        // Pivot/intermediary tables (e.g. procurement_order_items) carry no
        // school_id; scoping them unconditionally was a hard SQL error.
        if ($this->tableHasTenantColumn($table)) {
            $query->where("{$dataset['alias']}.school_id", '=', $schoolId);
        }

        return $query;
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
     *
     * $connection is the resolved edge (from normalize()) and is the only
     * trustworthy source of the ON clause: it always comes from the endpoint
     * that declares the relationship, so the ON references a table that is
     * already in the query.
     */
    protected function joinDataset(Builder $query, string $toKey, string $presentKey, int $schoolId, string $type, ?array $connection = null): void
    {
        $toDef = $this->requireDataset($toKey);

        $edge = $connection ?? $this->findConnection($presentKey, $toKey);

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
            // MySQL resolves JOIN operands left to right and has no forward
            // references, so a dataset's own autoJoins can only land *after* the
            // join that introduces its alias. Providers declare them in
            // dependency order, so a single pass in declaration order is
            // sufficient; edge validation guarantees no connection points at an
            // enrichment table that does not exist yet.
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
     *  - 'from'      → a column on the declaring dataset's alias or one of its
     *                   enrichment tables
     *  - 'to_fields' → a column on the other dataset's alias or its enrichment
     *                   tables
     *
     * Both prefixes are used verbatim: {@see edgeIsResolvable} has already
     * established that each names a table already in the query or introduced by
     * this very JOIN. An equality is symmetric, so which side is written first
     * does not matter.
     */
    protected function attachEdgeOn(JoinClause $join, array $edge): void
    {
        $fromField = $edge['from'][0];
        $toField = $edge['to_fields'][0];

        [$prefix, $column] = array_pad(explode('.', $toField, 2), 2, null);

        $join->on($fromField, '=', $prefix.'.'.($column ?? $toField));
    }

    protected function applySelect(Builder $query, array $config): void
    {
        $groupExprs = $this->groupExpressions($config);

        foreach ($config['selected_fields'] as $qualifiedKey) {
            $expr = $this->resolveFieldExpression($qualifiedKey);

            // Under MySQL ONLY_FULL_GROUP_BY every selected column must be a
            // grouping expression or an aggregate; non-grouped columns are
            // wrapped in MAX() as a representative value.
            if (! empty($groupExprs) && ! $this->isGroupExpression($expr, $groupExprs)) {
                $expr = "MAX({$expr})";
            }

            $query->addSelect(DB::raw("{$expr} as {$this->aliasFor($qualifiedKey)}"));
        }
    }

    protected function applyFilters(Builder $query, array $config): void
    {
        foreach ($config['filters'] as $filter) {
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
                'ends' => $query->whereRaw("LOWER({$expr}) LIKE ?", [mb_strtolower((string) $value).'%'], $boolean),
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

        foreach ($config['calculations'] as $calc) {
            $type = strtoupper($calc['type'] ?? 'sum');
            $qualifiedKey = $this->qualify($calc['dataset'] ?? null, $calc['key'] ?? null);
            $alias = $this->calculationAliases[$qualifiedKey]
                ?? ($this->aliasFor($qualifiedKey) ?: ($calc['alias'] ?? strtolower($type).'_'.($calc['key'] ?? 'value')));

            $fn = match ($type) {
                'COUNT' => 'COUNT',
                'AVG' => 'AVG',
                'MIN' => 'MIN',
                'MAX' => 'MAX',
                default => 'SUM',
            };

            // COUNT(*) is always legal; other aggregates need the column.
            $operand = $fn === 'COUNT' && ! $this->fieldInfo($qualifiedKey)
                ? '*'
                : $this->resolveFieldExpression($qualifiedKey);

            // Detail rows carrying a grand total need a window aggregate;
            // a plain aggregate is only legal alongside GROUP BY.
            $aggregate = $grouped ? "{$fn}({$operand})" : "{$fn}({$operand}) OVER ()";

            $query->addSelect(DB::raw("{$aggregate} as {$alias}"));
        }
    }

    protected function applySorting(Builder $query, array $config): void
    {
        $groupExprs = $this->groupExpressions($config);

        foreach ($config['sorting'] ?? [] as $sort) {
            $direction = ($sort['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

            if (! empty($sort['calculation'])) {
                // Sorting references a total by the alias it was configured
                // with; collision handling may have renamed the real column.
                $calculation = (string) $sort['calculation'];
                $query->orderBy($this->calculationAliasLookup[$calculation] ?? $calculation, $direction);

                continue;
            }

            $fieldKey = $this->qualify($sort['dataset'] ?? null, $sort['key'] ?? null);
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

        $dataset = $this->registry->byKey($datasetKey);

        if (! $dataset) {
            return $qualifiedKey;
        }

        $field = $this->fieldInfo($qualifiedKey);

        if (! $field) {
            return "{$dataset['alias']}.{$fieldKey}";
        }

        return $field['expression'] ?? "{$dataset['alias']}.{$fieldKey}";
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

        // An already-qualified key must not be prefixed a second time.
        if ($datasetKey && $this->datasetOf($fieldKey) === $datasetKey) {
            return $fieldKey;
        }

        return $datasetKey ? "{$datasetKey}.{$fieldKey}" : $fieldKey;
    }

    /**
     * Split a qualified `dataset.field` key. Dataset keys themselves contain a
     * dot (e.g. `finance.balance`), so the longest known dataset prefix wins.
     */
    protected function split(string $qualifiedKey): array
    {
        if (isset($this->splitCache[$qualifiedKey])) {
            return $this->splitCache[$qualifiedKey];
        }

        // Dataset keys themselves contain a dot (e.g. `finance.balance`), so the
        // longest known dataset prefix wins.
        $best = null;

        foreach ($this->registry->keys() as $datasetKey) {
            if (! str_starts_with($qualifiedKey, $datasetKey.'.')) {
                continue;
            }

            if ($best === null || strlen($datasetKey) > strlen($best)) {
                $best = $datasetKey;
            }
        }

        return $this->splitCache[$qualifiedKey] = $best
            ? [$best, substr($qualifiedKey, strlen($best) + 1)]
            : [null, $qualifiedKey];
    }

    protected function plainKey(string $qualifiedKey): string
    {
        [, $fieldKey] = $this->split($qualifiedKey);

        return $fieldKey ?: $qualifiedKey;
    }

    public function qualifiedFieldKey(string $qualifiedKey): string
    {
        return $this->aliasFor($qualifiedKey);
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
