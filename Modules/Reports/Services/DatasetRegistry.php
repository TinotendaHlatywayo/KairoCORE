<?php

namespace Modules\Reports\Services;

use Illuminate\Support\Facades\File;
use Modules\Reports\Contracts\ReportDatasetProvider;

/**
 * Auto-discovers every dataset provider under Modules/Reports/DataSources/Providers
 * and indexes their datasets by key so the whole report engine can resolve any
 * dataset declaratively (sources, joins, fields, filters) without hardcoded maps.
 */
class DatasetRegistry
{
    /** @var array<string, array<string, mixed>> dataset key => definition */
    protected array $datasets = [];

    /** @var array<string, string> dataset key => module label */
    protected array $modules = [];

    protected bool $loaded = false;

    public function all(): array
    {
        $this->load();

        return $this->datasets;
    }

    public function byKey(string $key): ?array
    {
        $this->load();

        return $this->datasets[$key] ?? null;
    }

    public function keys(): array
    {
        $this->load();

        return array_keys($this->datasets);
    }

    public function modules(): array
    {
        $this->load();

        return $this->modules;
    }

    /**
     * Datasets grouped by module, each with label + fields for pickers.
     */
    public function groupedForPicker(): array
    {
        $this->load();

        $grouped = [];
        foreach ($this->datasets as $key => $def) {
            $grouped[$def['module']][] = [
                'key' => $key,
                'label' => $def['label'],
                'description' => $def['description'] ?? '',
                'fields' => $def['fields'],
            ];
        }

        return $grouped;
    }

    protected function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $path = base_path('Modules/Reports/DataSources/Providers');

        foreach (File::files($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $class = $this->resolveClass($file);

            if (! $class || ! class_exists($class) || ! is_subclass_of($class, ReportDatasetProvider::class)) {
                continue;
            }

            /** @var ReportDatasetProvider $provider */
            $provider = app($class);

            foreach ($provider->datasets() as $dataset) {
                if (empty($dataset['key']) || empty($dataset['fields'])) {
                    continue;
                }

                $this->datasets[$dataset['key']] = $dataset;
                $this->modules[$dataset['key']] = $dataset['module'];
            }
        }

        $this->loaded = true;
    }

    /**
     * Derive the FQCN from the file path (PSR-4: Modules/Reports ↔ Modules/Reports).
     */
    protected function resolveClass($file): ?string
    {
        $relative = str_replace(
            [base_path('Modules'), '.php', DIRECTORY_SEPARATOR],
            ['Modules', '', '\\'],
            $file->getPathname()
        );

        return ltrim($relative, '\\');
    }
}
