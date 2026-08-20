<?php

namespace Modules\Students\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Academics\Models\Course;
use Modules\Admissions\Models\Application;
use Modules\Finance\Models\FeeWaiver;

class Student extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'school_id',
        'user_id',
        'application_id',
        'student_id_number',
        'admission_number',
        'national_id',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'admission_date',
        'status',
        'card_expiry_date',
        'card_status',
        'avatar_path',
        'photo_path',
        'photo_rejected_reason',
        'photo_rejected_by',
        'photo_rejected_at',
        'house',
        'boarding_status',
        'blood_group',
        'medical_notes',
        'emergency_contact_name',
        'emergency_contact_phone',
        'parent_email',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'card_expiry_date' => 'date',
        'photo_rejected_at' => 'datetime',
    ];

    public static array $levelSuffixes = [
        'ECD A' => 'A',
        'ECD B' => 'B',
        'Grade 1' => 'C',
        'Grade 2' => 'D',
        'Grade 3' => 'E',
        'Grade 4' => 'F',
        'Grade 5' => 'G',
        'Grade 6' => 'H',
        'Grade 7' => 'I',
        'Form 1' => 'J',
        'Form 2' => 'K',
        'Form 3' => 'L',
        'Form 4' => 'M',
        'Lower Six' => 'N',
        'Upper Six' => 'O',
    ];

    protected static function booted()
    {
        static::creating(function ($student) {
            $schoolId = $student->school_id ?? app('current_tenant')->id;
            $admDate = Carbon::parse($student->admission_date ?? now());
            $yearYY = $admDate->format('y');
            $monthMM = $admDate->format('m');

            // 1. UNIQUE STUDENT ID GENERATOR (R + YY + XXXXXXX + Level Letter)
            // =========================================================================
            if (empty($student->student_id_number)) {
                $suffix = 'X'; // Fallback letter

                // Read the selected course directly from the active Filament form request
                $formCourseId = request()->input('data.course_id');

                if ($formCourseId) {
                    $course = Course::find($formCourseId);
                    if ($course) {
                        $suffix = self::$levelSuffixes[$course->name] ?? 'X';
                    }
                } elseif ($student->currentEnrollment && $student->currentEnrollment->course) {
                    $levelName = $student->currentEnrollment->course->name;
                    $suffix = self::$levelSuffixes[$levelName] ?? 'X';
                }

                // Loop until a completely unique ID is generated
                do {
                    $randomMiddle = mt_rand(1000000, 9999999);
                    $candidateId = 'R'.$yearYY.$randomMiddle.$suffix;
                } while (self::where('school_id', $schoolId)->where('student_id_number', $candidateId)->exists());

                $student->student_id_number = $candidateId;
            }

            // 2. DYNAMIC MONTHLY ADMISSION NUMBER (YYMM-XXXX-VV)
            if (empty($student->admission_number)) {
                $startOfMonth = $admDate->copy()->startOfMonth();
                $endOfMonth = $admDate->copy()->endOfMonth();

                $sequence = self::where('school_id', $schoolId)
                    ->whereBetween('admission_date', [$startOfMonth, $endOfMonth])
                    ->count() + 1;

                do {
                    $paddedSeq = str_pad($sequence, 4, '0', STR_PAD_LEFT);
                    $checksumDigit = mt_rand(10, 99);
                    $candidateAdm = $yearYY.$monthMM.'-'.$paddedSeq.'-'.$checksumDigit;
                    $sequence++;
                } while (self::where('school_id', $schoolId)->where('admission_number', $candidateAdm)->exists());

                $student->admission_number = $candidateAdm;
            }
        });
    }

    /**
     * FIX: Dynamic Expiration Calculator Accessor. Resolves null dates at print-time
     */
    public function getResolvedCardExpiryAttribute()
    {
        if ($this->card_expiry_date) {
            return $this->card_expiry_date;
        }

        $enrollment = $this->currentEnrollment ?? $this->enrollments()->latest()->first();
        if (! $enrollment || ! $enrollment->course) {
            return Carbon::parse($this->admission_date ?? now())->addYears(3); // Default fallback
        }

        $level = $enrollment->course->name;
        $activeYearValue = Carbon::parse($this->admission_date ?? now())->year;

        if ($level === 'ECD A') {
            return Carbon::create($activeYearValue + 1, 12, 31);
        } elseif ($level === 'ECD B' || preg_match('/Grade\s*[1-7]/i', $level)) {
            $gradeNum = preg_match('/Grade\s*([1-7])/i', $level, $matches) ? intval($matches[1]) : 7;
            $yearsRemaining = max(0, 7 - $gradeNum);

            return Carbon::create($activeYearValue + $yearsRemaining, 12, 31);
        } elseif (preg_match('/Form\s*[1-4]/i', $level)) {
            $formNum = preg_match('/Form\s*([1-4])/i', $level, $matches) ? intval($matches[1]) : 4;
            $yearsRemaining = max(0, 4 - $formNum);

            return Carbon::create($activeYearValue + $yearsRemaining, 12, 31);
        } elseif ($level === 'Lower Six' || $level === 'Upper Six') {
            $offset = ($level === 'Lower Six') ? 1 : 0;

            return Carbon::create($activeYearValue + $offset, 12, 31);
        }

        return Carbon::create($activeYearValue + 3, 12, 31);
    }

    public function currentEnrollment()
    {
        return $this->hasOne(Enrollment::class)->latestOfMany();
    }

    public function guardians()
    {
        return $this->belongsToMany(Guardian::class, 'student_guardian');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function waivers()
    {
        return $this->belongsToMany(
            FeeWaiver::class,
            'student_fee_waiver',
            'student_id',
            'fee_waiver_id'
        );
    }

    public function documents()
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
