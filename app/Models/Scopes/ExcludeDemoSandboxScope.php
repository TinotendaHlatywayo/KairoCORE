<?php

namespace App\Models\Scopes;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class ExcludeDemoSandboxScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! $model instanceof School || app()->bound('current_tenant')) {
            return;
        }

        $builder->where($model->qualifyColumn('is_demo_sandbox'), false);
    }
}
