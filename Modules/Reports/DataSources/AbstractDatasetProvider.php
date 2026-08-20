<?php

namespace Modules\Reports\DataSources;

use Modules\Reports\Contracts\ReportDatasetProvider;

/**
 * Compact helpers for declaring dataset definitions. Datasets are plain arrays
 * so the definition language stays declarative and database-agnostic.
 *
 * Field types: string|integer|decimal|boolean|date|datetime|percent|currency
 */
abstract class AbstractDatasetProvider implements ReportDatasetProvider
{
    /**
     * Compose a full dataset definition.
     *
     * $source is either a table name or raw SQL (a "derived" aggregate dataset).
     * The SQL may contain the {school_id} placeholder which is replaced at run
     * time with the resolved tenant id — guarantees cross-tenant isolation even
     * inside aggregate subqueries.
     */
    protected function d(string $key, string $label, string $source, array $fields, array $opts = []): array
    {
        $def = [
            'key' => $key,
            'label' => $label,
            'module' => $this->module(),
            'alias' => str_replace('.', '_', $key),
            'fields' => $fields,
        ];

        if (str_starts_with(strtoupper(ltrim($source)), 'SELECT')) {
            $def['baseSql'] = $source;
        } else {
            $def['table'] = $source;
        }

        foreach (['description', 'autoJoins', 'connections', 'filters', 'default_order'] as $opt) {
            if (isset($opts[$opt])) {
                $def[$opt] = $opts[$opt];
            }
        }

        return $def;
    }

    protected function f(string $key, string $label, string $type = 'string', ?string $expression = null, ?string $format = null): array
    {
        $def = ['key' => $key, 'label' => $label, 'type' => $type];
        if ($expression !== null) {
            $def['expression'] = $expression;
        }
        if ($format !== null) {
            $def['format'] = $format;
        }

        return $def;
    }

    protected function money(string $key, string $label, ?string $expression = null): array
    {
        return $this->f($key, $label, 'currency', $expression, 'currency');
    }

    protected function pct(string $key, string $label, ?string $expression = null): array
    {
        return $this->f($key, $label, 'percent', $expression, 'percent');
    }

    protected function date(string $key, string $label): array
    {
        return $this->f($key, $label, 'date');
    }

    protected function datetime(string $key, string $label): array
    {
        return $this->f($key, $label, 'datetime');
    }

    /**
     * Standard connection helper between this dataset's alias and another dataset.
     */
    protected function connect(string $toDatasetKey, string $fromField, string $toField, string $type = 'left'): array
    {
        return [
            'to' => $toDatasetKey,
            'type' => $type,
            'from' => [$fromField],
            'to_fields' => [$toField],
        ];
    }
}
