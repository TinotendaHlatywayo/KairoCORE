<?php

namespace Modules\Admin\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Admin\Services\AuditLogger;

class CustomRole extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'permissions',
        'is_system',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system' => 'boolean',
    ];

    /**
     * Boot the model and register automatic auditing event observers [1.2].
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($model) {
            AuditLogger::log(
                "Created Custom Role: {$model->name}",
                'System Administration',
                null,
                $model->toArray()
            );
        });

        static::updated(function ($model) {
            AuditLogger::log(
                "Updated Custom Role: {$model->name}",
                'System Administration',
                $model->getOriginal(),
                $model->getChanges()
            );
        });

        static::deleted(function ($model) {
            AuditLogger::log(
                "Deleted Custom Role: {$model->name}",
                'System Administration',
                $model->toArray(),
                null
            );
        });
    }

    public function users()
    {
        return $this->hasMany(User::class, 'custom_role_id');
    }
}
