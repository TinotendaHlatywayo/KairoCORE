<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model) {
            // Check if a tenant is resolved in the container and automatically set school_id
            if (app()->bound('current_tenant') && ! $model->getAttribute('school_id')) {
                $model->setAttribute('school_id', app('current_tenant')->id);
            }
        });
    }

    public static function withoutTenantScope()
    {
        return (new static)->withoutGlobalScope(TenantScope::class);
    }
}
