<?php

namespace Modules\HR\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Employee extends Model
{
    use BelongsToTenant; // REMOVED SoftDeletes completely

    protected $fillable = [
        'school_id',
        'user_id',
        'employee_number',
        'national_id',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'marital_status',
        'phone_number',
        'email',
        'physical_address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'department',
        'designation',
        'role',
        'employment_type',
        'contract_end_date',
        'date_joined',
        'current_grade_id',
        'spouse_details',
        'dependents',
        'next_of_kin',
        'medical_conditions',
        'allergies',
        'emergency_medical_notes',
        'status',
        'suspension_reason',
        'avatar_path',
        'photo_rejected_reason',
        'photo_rejected_by',
        'photo_rejected_at',
        'document_contract',
        'document_academic',
        'document_professional',
    ];

    protected $casts = [
        'spouse_details' => 'array',
        'dependents' => 'array',
        'next_of_kin' => 'array',
        'date_of_birth' => 'date',
        'contract_end_date' => 'date',
        'date_joined' => 'date',
        'photo_rejected_at' => 'datetime',
    ];

    public function currentGrade(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class, 'current_grade_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }

    public function gradeHistory(): HasMany
    {
        return $this->hasMany(SalaryGradeHistory::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(StaffLoan::class);
    }

    public function disciplinaryCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(EmployeeAsset::class);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            DB::transaction(function () use ($employee) {
                if (empty($employee->avatar_path)) {
                    $employee->avatar_path = 'images/employee_profile.jpeg';
                }

                $year = date('Y');
                $count = self::where('school_id', $employee->school_id)
                    ->whereYear('created_at', $year)
                    ->count() + 1;

                do {
                    $candidate = 'EMP-'.$year.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
                    // Updated: Removed withTrashed() since soft deletes are deleted
                    $exists = self::where('school_id', $employee->school_id)
                        ->where('employee_number', $candidate)
                        ->exists();
                    if ($exists) {
                        $count++;
                    }
                } while ($exists);

                $employee->employee_number = $candidate;

                if (empty($employee->user_id)) {
                    $user = User::withTrashed()
                        ->where('school_id', $employee->school_id)
                        ->where('email', $employee->email)
                        ->first();

                    if ($user && $user->trashed()) {
                        // A previously deleted account still occupies the
                        // unique school+email index; restore it so re-adding
                        // the employee never hits a duplicate key.
                        $user->restore();
                    }

                    if (! $user) {
                        $passwordRaw = Str::random(10);
                        $user = User::create([
                            'school_id' => $employee->school_id,
                            'name' => "{$employee->first_name} {$employee->last_name}",
                            'email' => $employee->email,
                            'password' => Hash::make($passwordRaw),
                        ]);
                    }

                    $employee->user_id = $user->id;
                }
            });
        });

        // CASCADE DELETE OBSERVER: Deleting an employee permanently deletes their User portal login account
        static::deleted(function ($employee) {
            if ($employee->user_id) {
                User::where('id', $employee->user_id)->delete();
            }
        });
    }
}
