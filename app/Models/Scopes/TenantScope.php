<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\App;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Skip scoping if we are in super-admin/platform mode or running via console seeds without a tenant context
        if (App::has('current_tenant')) {
            $builder->where($model->getTable().'.school_id', App::make('current_tenant')->id);
        }
    }
}
