<?php

namespace Modules\Admin\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Admin\Services\AuditLogger;
use Modules\HR\Models\Employee;

class Department extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'type',
        'head_user_id',
        'budget_code',
        'permissions',
        'status',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Boot the model and register automatic auditing event observers [1.2].
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($model) {
            AuditLogger::log(
                "Created Department: {$model->name} ({$model->code})",
                'System Administration',
                null,
                $model->toArray()
            );
        });

        static::updated(function ($model) {
            AuditLogger::log(
                "Updated Department: {$model->name} ({$model->code})",
                'System Administration',
                $model->getOriginal(),
                $model->getChanges()
            );
        });

        static::deleted(function ($model) {
            AuditLogger::log(
                "Deleted Department: {$model->name} ({$model->code})",
                'System Administration',
                $model->toArray(),
                null
            );
        });
    }

    public function head()
    {
        // Links to the Employees ledger table directly
        return $this->belongsTo(Employee::class, 'head_user_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'department_user')
            ->withPivot('school_id');
    }

    /**
     * Default permission bundle for every member of this department.
     *
     * @return array<int, string>
     */
    public function permissionKeys(): array
    {
        return is_array($this->permissions) ? $this->permissions : [];
    }
}
