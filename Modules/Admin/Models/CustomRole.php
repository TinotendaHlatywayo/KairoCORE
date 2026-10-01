<?php

namespace Modules\Admin\Models;

use App\Models\User;
use App\Security\RoleCatalogue;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'role_key',
        'permissions_customised',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system' => 'boolean',
        'permissions_customised' => 'boolean',
    ];

    /**
     * A role that carries a catalogue key is one of the defaults the platform
     * ships, and its permissions are kept in step with `RoleCatalogue`. A role
     * without one was built by an administrator and is never rewritten.
     */
    public function isCatalogueManaged(): bool
    {
        return $this->role_key !== null && RoleCatalogue::exists($this->role_key);
    }

    /**
     * Has an administrator tailored this role since it was created? The
     * synchroniser refreshes untouched defaults and leaves these alone.
     */
    public function hasCustomisedPermissions(): bool
    {
        return (bool) $this->permissions_customised;
    }

    /**
     * A copy of a catalogue role is a new, administrator-owned role: it has to
     * give up its catalogue identity, or the synchroniser would later rewrite
     * it back to the original's defaults and two roles would share one key.
     */
    public function replicateForClone(?CustomRole $except = null): CustomRole
    {
        $clone = $except ? $except->replicate() : $this->replicate();

        $clone->name = $this->name.' - Copy';
        $clone->is_system = false;
        $clone->role_key = null;
        $clone->permissions_customised = true;

        $clone->save();

        return $clone;
    }

    /**
     * Boot the model and register automatic auditing event observers [1.2].
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($model) {
            // Remember that this role has moved away from its catalogue
            // defaults, so later refreshes leave the administrator's work be.
            // A catalogue-driven write skips events entirely (saveQuietly), and
            // an insert is not a move away from anything, so only a genuine
            // edit of a stored role counts.
            if ($model->exists && $model->isDirty('permissions') && $model->role_key !== null) {
                $model->permissions_customised = true;
            }
        });

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

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'custom_role_id');
    }
}
