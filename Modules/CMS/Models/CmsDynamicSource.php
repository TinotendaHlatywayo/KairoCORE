<?php

namespace Modules\CMS\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CmsDynamicSource extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'handle',
        'name',
        'module',
        'model_class',
        'query_config',
        'field_mapping',
        'template_config',
        'cache_ttl',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'query_config' => 'array',
        'field_mapping' => 'array',
        'template_config' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function resolve(int $limit = 10): array
    {
        if (! $this->is_active || ! class_exists($this->model_class)) {
            return [];
        }

        $schoolId = $this->school_id ?? app('current_tenant')->id;
        $cacheKey = "cms_dynamic_source_{$schoolId}_{$this->handle}_limit_{$limit}";

        return cache()->remember($cacheKey, $this->cache_ttl, function () use ($limit) {
            $query = $this->model_class::query();

            // Apply school scope if model uses BelongsToTenant
            if (in_array(BelongsToTenant::class, class_uses_recursive($this->model_class), true)) {
                $query->where('school_id', $this->school_id);
            }

            // Apply custom query config
            if (! empty($this->query_config)) {
                foreach ($this->query_config as $key => $value) {
                    switch ($key) {
                        case 'where':
                            foreach ($value as $where) {
                                $query->where(...$where);
                            }
                            break;
                        case 'whereIn':
                            foreach ($value as $where) {
                                $query->whereIn(...$where);
                            }
                            break;
                        case 'orderBy':
                            foreach ($value as $order) {
                                $query->orderBy(...$order);
                            }
                            break;
                        case 'with':
                            $query->with($value);
                            break;
                    }
                }
            }

            return $query->limit($limit)->get()->map(function ($item) {
                return $this->mapItem($item);
            })->toArray();
        });
    }

    protected function mapItem($item): array
    {
        if (empty($this->field_mapping)) {
            return $item->toArray();
        }

        $result = [];
        foreach ($this->field_mapping as $targetField => $sourceField) {
            if (is_callable($sourceField)) {
                $result[$targetField] = $sourceField($item);
            } else {
                $result[$targetField] = data_get($item, $sourceField);
            }
        }

        return $result;
    }

    public function getTemplateConfig(): array
    {
        return $this->template_config ?? [
            'item_view' => 'modules.cms.dynamic.default-item',
            'container_class' => '',
            'item_class' => '',
        ];
    }
}
