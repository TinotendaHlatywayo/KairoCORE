<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\Department;
use Modules\HR\Models\Employee;
use Modules\Students\Models\Student;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use BelongsToTenant, HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    const STATUS_ACTIVE = 'active';

    const STATUS_PENDING = 'pending';

    const STATUS_REJECTED = 'rejected';

    const STATUS_SUSPENDED = 'suspended';

    const REGISTRATION_ROLES = [
        'administrator' => 'Administrator',
        'student' => 'Student',
        'teaching_staff' => 'Teaching Staff',
        'non_teaching_staff' => 'Non-Teaching Staff',
    ];

    protected $fillable = [
        'school_id',
        'name',
        'email',
        'google_id',
        'avatar',
        'phone',
        'username',
        'password',
        'custom_role_id',
        'permissions',
        'account_status',
        'requested_role',
        'approved_by',
        'approved_at',
        'activated_at',
        'rejected_reason',
        'do_not_disturb',
        'locale',
        'activation_token',
        'activation_token_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'approved_at' => 'datetime',
            'activated_at' => 'datetime',
            'activation_token_expires_at' => 'datetime',
            'permissions' => 'array',
        ];
    }

    public function school()
    {
        // Disable scope during model relationship resolution to prevent lookup failures
        return $this->belongsTo(School::class, 'school_id')->withoutGlobalScopes();
    }

    public function customRole()
    {
        return $this->belongsTo(CustomRole::class, 'custom_role_id');
    }

    /**
     * The staff (Employee) record linked to this account, when one exists.
     */
    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    /**
     * Departments the user belongs to. Non-teaching staff are assigned one or
     * more departments (clinic, finance, inventory & assets, ...) and inherit
     * each department's default permission bundle.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user')
            ->withPivot('school_id');
    }

    /**
     * Whether this account carries an explicit per-user permission snapshot.
     */
    public function hasCustomPermissions(): bool
    {
        return is_array($this->permissions);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by')->withoutGlobalScopes();
    }

    public function isApproved(): bool
    {
        return $this->account_status === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->account_status === self::STATUS_PENDING;
    }

    public function requestedRoleLabel(): ?string
    {
        if (! $this->requested_role) {
            return null;
        }

        return self::REGISTRATION_ROLES[$this->requested_role] ?? ucwords(str_replace('_', ' ', $this->requested_role));
    }

    public function accountStatusLabel(): string
    {
        return match ($this->account_status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PENDING => 'Pending Approval',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_SUSPENDED => 'Suspended',
            default => ucfirst($this->account_status ?? 'Active'),
        };
    }

    /**
     * Whether this account belongs to a student (as opposed to staff).
     *
     * The linked Student record is the authoritative signal, with a fallback
     * to the self-declared registration role or a Spatie "student" role so the
     * check still works before a profile has been linked.
     */
    public function isStudent(): bool
    {
        if ($this->school_id === null) {
            return false;
        }

        if (Student::where('user_id', $this->id)->exists()) {
            return true;
        }

        return $this->requested_role === 'student' || $this->hasRole('student');
    }

    /**
     * Filament panel access gate.
     *
     * School users may only enter their own workspace when their account has
     * been approved and activated. Pending/rejected accounts are refused at
     * the login gate. Super administrators (school_id = null) are restricted
     * to the platform panel. Students are confined to the dedicated student
     * panel and can never enter the staff workspace.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->school_id === null;
        }

        // The school workspace is tenant-scoped; platform admins have no
        // school context and must stay in the platform panel.
        if ($this->school_id === null || $this->account_status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($panel->getId() === 'student') {
            return $this->isStudent();
        }

        // Students are locked out of the staff workspace (billing, payroll,
        // inventory, settings, ...). Everything a student may see lives in the
        // student panel instead.
        return ! $this->isStudent();
    }

    public function isSuperAdmin(): bool
    {
        return $this->school_id === null;
    }
}
