<?php

namespace Modules\Academics\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

// ADDED: Fixes VS Code warnings

class AcademicReport extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'student_id',
        'section_id',
        'term_id',
        'unhu_competencies',
        'subject_teacher_remarks',
        'overall_score',
        'strength',
        'needs_improvement',
        'status',
        'teacher_id',
        'teacher_comment',
        'outstanding_achievements',
        'headmaster_comment',
        'integrity_hash',
    ];

    protected $casts = [
        'unhu_competencies' => 'array',
        'subject_teacher_remarks' => 'array',
        'overall_score' => 'decimal:2',
    ];

    // Competency Scale Weights out of 10.0
    public static array $scalePoints = [
        'outstanding' => 10.0,
        'excellent' => 8.5,
        'very_good' => 7.0,
        'good' => 5.5,
        'satisfactory' => 4.0,
        'needs_improvement' => 2.0,
    ];

    // UPDATED: Standardized bilingual array containing both groups of 10
    public static array $competencyLabels = [
        'respect' => 'Respect (Kuremekedza)',
        'honesty' => 'Integrity / Honesty (Kuvimbika)',
        'responsibility' => 'Responsibility (Kuzvidavirira)',
        'discipline' => 'Discipline (Kuzvibata)',
        'patriotism' => 'Patriotism (Kuda Nyika)',
        'cooperation' => 'Cooperation / Teamwork (Kushandira Pamwe)',
        'leadership' => 'Leadership (Hutungamiri)',
        'critical_thinking' => 'Critical Thinking (Kufunga Zvakadzama)',
        'creativity' => 'Creativity & Innovation (Hunyanzvi Hwekugadzira)',
        'environment' => 'Environmental Stewardship (Kuchengetedza Zvakatipoteredza)',

        'communication' => 'Communication Skills (Unyanzvi hweKutaurirana)',
        'digital_literacy' => 'Digital Literacy (Unyanzvi hweTekinoroji yeRuzivo neKutaurirana)',
        'entrepreneurship' => 'Entrepreneurship (Hunyanzvi hweKuita Mabhizimisi)',
        'cultural_appreciation' => 'Cultural Appreciation (Kukoshesa Tsika neNhaka)',
        'community_service' => 'Community Service (Kushandira Nharaunda)',
        'perseverance' => 'Perseverance (Kutsungirira)',
        'compassion' => 'Compassion (Tsitsi)',
        'time_management' => 'Time Management (Kutonga Nguva Zvakanaka)',
        'self_confidence' => 'Self-Confidence (Kuzvivimba)',
        'adaptability' => 'Adaptability (Kugona Kujairira Shanduko)',
    ];

    protected static function booted()
    {
        static::saving(function ($report) {
            if (is_array($report->unhu_competencies)) {
                $scores = [];
                foreach ($report->unhu_competencies as $key => $rating) {
                    if (is_string($rating) && $rating && isset(self::$scalePoints[$rating])) {
                        $scores[$key] = self::$scalePoints[$rating];
                    }
                }

                $scoreCount = count($scores);
                if ($scoreCount > 0) {
                    $report->overall_score = round(array_sum($scores) / $scoreCount, 2);

                    $minScore = min($scores);
                    $maxScore = max($scores);

                    $lowestKeys = array_keys($scores, $minScore);
                    $highestKeys = array_keys($scores, $maxScore);

                    if ($minScore === $maxScore) {
                        $report->strength = '• Balanced Profile';
                        $report->needs_improvement = '• None (Consistent Performance)';
                    } else {
                        $strengthsList = array_map(function ($key) {
                            return '• '.(self::$competencyLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)));
                        }, $highestKeys);

                        $improvementsList = array_map(function ($key) {
                            return '• '.(self::$competencyLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)));
                        }, $lowestKeys);

                        $report->strength = implode("\n", $strengthsList);
                        $report->needs_improvement = implode("\n", $improvementsList);
                    }
                }
            }

            if (! $report->integrity_hash) {
                $report->integrity_hash = hash_hmac(
                    'sha256',
                    $report->student_id.$report->term_id.now()->toIso8601String(),
                    config('app.key')
                );
            }

            // FIX: Uses strongly typed static Facade class
            if (! $report->teacher_id && Auth::check()) {
                $report->teacher_id = Auth::id();
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * The enrollment that backs this report's term.
     *
     * Promotion / stream changes are append-only: the previous row is archived
     * (status 'promoted', 'transferred_out', ...) and a NEW row is created for
     * the student's current placement. When a promotion has already moved the
     * learner into a later academic year, no active row exists for the report's
     * own year any more - so fall back to the student's current placement (the
     * same rule the student directory uses) to keep the class, template and
     * marks aligned with where the learner actually is.
     *
     * Resolution order: active enrollment in the report's academic year, then
     * the current (latest) enrollment across years, then any enrollment in the
     * report's academic year.
     */
    public function resolveTermEnrollment(): ?Enrollment
    {
        if (! $this->student_id) {
            return null;
        }

        $yearId = $this->term?->academic_year_id;

        if ($yearId) {
            $active = Enrollment::withoutGlobalScopes()
                ->with(['section.course'])
                ->where('student_id', $this->student_id)
                ->where('academic_year_id', $yearId)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->orderByDesc('id')
                ->first();

            if ($active) {
                return $active;
            }
        }

        $latest = Enrollment::withoutGlobalScopes()
            ->with(['section.course'])
            ->where('student_id', $this->student_id)
            ->orderByDesc('id')
            ->first();

        if ($latest) {
            return $latest;
        }

        if ($yearId) {
            return Enrollment::withoutGlobalScopes()
                ->with(['section.course'])
                ->where('student_id', $this->student_id)
                ->where('academic_year_id', $yearId)
                ->orderByDesc('id')
                ->first();
        }

        return null;
    }

    /**
     * The class stream this report should display, resolved from the term's
     * enrollment (live placement) with the stored section as a fallback.
     */
    public function resolveTermSection(): ?Section
    {
        return $this->resolveTermEnrollment()?->section ?? $this->section;
    }
}
