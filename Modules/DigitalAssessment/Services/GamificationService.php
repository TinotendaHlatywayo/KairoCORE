<?php

namespace Modules\DigitalAssessment\Services;

use Illuminate\Support\Collection;
use Modules\DigitalAssessment\Models\ChallengeParticipant;
use Modules\DigitalAssessment\Models\GamificationAchievement;
use Modules\DigitalAssessment\Models\GamificationBadge;
use Modules\DigitalAssessment\Models\GamificationChallenge;
use Modules\DigitalAssessment\Models\GamificationSettings;
use Modules\DigitalAssessment\Models\LeaderboardSnapshot;
use Modules\DigitalAssessment\Models\LearnerAchievement;
use Modules\DigitalAssessment\Models\LearnerBadge;
use Modules\DigitalAssessment\Models\LearnerStreak;
use Modules\DigitalAssessment\Models\LearnerXp;
use Modules\DigitalAssessment\Models\DigitalAssessmentAttempt;
use Modules\DigitalAssessment\Notifications\BadgeEarnedNotification;

class GamificationService
{
    public function getSettings(?int $schoolId = null): GamificationSettings
    {
        $schoolId = $schoolId ?? current_tenant()?->id ?? \App\Models\School::value('id');

        return GamificationSettings::forSchool((int) $schoolId);
    }

    public function updateSettings(array $data, ?int $schoolId = null): GamificationSettings
    {
        $settings = $this->getSettings($schoolId);
        $settings->update($data);

        return $settings->fresh();
    }

    // ── XP ──

    public function awardXp(int $studentId, int $amount, string $type, ?string $description = null, ?int $schoolId = null): ?LearnerXp
    {
        $settings = $this->getSettings($schoolId);

        if (! $settings->xp_enabled || $amount <= 0) {
            return null;
        }

        $schoolId = $schoolId ?? current_tenant()?->id;
        $xp = LearnerXp::forStudent($schoolId, $studentId);
        $xp->addXp($amount, $type, $description);

        return $xp->fresh();
    }

    public function awardAssessmentCompletionXp(DigitalAssessmentAttempt $attempt): void
    {
        $settings = $this->getSettings();

        if (! $settings->xp_enabled) {
            return;
        }

        $this->awardXp(
            $attempt->student_id,
            $settings->xp_per_assessment_complete,
            'assessment_complete',
            "Completed: {$attempt->assessment->title}",
            $attempt->assessment
        );
    }

    public function awardStreakXp(int $studentId, int $streakDays): void
    {
        $settings = $this->getSettings();

        if (! $settings->xp_enabled || $streakDays <= 0) {
            return;
        }

        $amount = $settings->xp_per_streak_day * min($streakDays, 7);
        $this->awardXp($studentId, $amount, 'streak_bonus', "Streak bonus: {$streakDays} days");
    }

    public function getLearnerXp(int $studentId, ?int $schoolId = null): ?LearnerXp
    {
        $schoolId = $schoolId ?? current_tenant()?->id;

        return LearnerXp::where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->first();
    }

    public function getXpTransactions(int $studentId, int $limit = 20, ?int $schoolId = null): Collection
    {
        $schoolId = $schoolId ?? current_tenant()?->id;

        return LearnerXp::where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->with('transactions')
            ->first()
            ?->transactions
            ?->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ?? collect();
    }

    // ── Badges ──

    public function awardBadge(int $studentId, int $badgeId, ?int $schoolId = null): ?LearnerBadge
    {
        $settings = $this->getSettings($schoolId);

        if (! $settings->badges_enabled) {
            return null;
        }

        $schoolId = $schoolId ?? current_tenant()?->id;
        $badge = GamificationBadge::find($badgeId);

        if (! $badge || $badge->earnedByStudent($studentId)) {
            return null;
        }

        $learnerBadge = LearnerBadge::create([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'gamification_badge_id' => $badgeId,
            'earned_at' => now(),
            'notified' => false,
        ]);

        if ($badge->xp_reward > 0) {
            $this->awardXp($studentId, $badge->xp_reward, 'badge_reward', "Earned badge: {$badge->name}");
        }

        $this->notifyBadgeEarned($learnerBadge, $badge);

        return $learnerBadge;
    }

    protected function notifyBadgeEarned(LearnerBadge $learnerBadge, GamificationBadge $badge): void
    {
        try {
            $student = \Modules\Students\Models\Student::find($learnerBadge->student_id);

            if (! $student || ! $student->user) {
                return;
            }

            $student->user->notify(new BadgeEarnedNotification($learnerBadge, $badge));

            $learnerBadge->update(['notified' => true]);
        } catch (\Exception $e) {
            // Notification failure should not block badge awarding
        }
    }

    public function checkAndAwardBadges(int $studentId, ?int $schoolId = null): array
    {
        $schoolId = $schoolId ?? current_tenant()?->id;
        $awardedBadges = [];

        $badges = GamificationBadge::where('school_id', $schoolId)->active()->get();

        foreach ($badges as $badge) {
            if ($badge->earnedByStudent($studentId)) {
                continue;
            }

            $criteria = $badge->criteria ?? [];
            $qualified = true;

            foreach ($criteria as $key => $value) {
                $met = match ($key) {
                    'min_xp' => ($this->getLearnerXp($studentId, $schoolId)?->total_xp ?? 0) >= $value,
                    'min_assessments_completed' => $this->countCompletedAssessments($studentId, $schoolId) >= $value,
                    'min_streak_days' => LearnerStreak::where('student_id', $studentId)->where('school_id', $schoolId)->value('current_streak') >= $value,
                    'min_mastery_score' => $this->hasMasteryAbove($studentId, $value, $schoolId),
                    'min_level' => ($this->getLearnerXp($studentId, $schoolId)?->current_level ?? 1) >= $value,
                    default => true,
                };

                if (! $met) {
                    $qualified = false;
                    break;
                }
            }

            if ($qualified) {
                $awarded = $this->awardBadge($studentId, $badge->id, $schoolId);
                if ($awarded) {
                    $awardedBadges[] = $badge;
                }
            }
        }

        return $awardedBadges;
    }

    public function getLearnerBadges(int $studentId, ?int $schoolId = null): Collection
    {
        $schoolId = $schoolId ?? current_tenant()?->id;

        return LearnerBadge::where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->with('badge')
            ->orderByDesc('earned_at')
            ->get();
    }

    // ── Streaks ──

    public function recordStreakActivity(int $studentId, ?string $activityType = null, ?int $schoolId = null): bool
    {
        $settings = $this->getSettings($schoolId);

        if (! $settings->streaks_enabled) {
            return false;
        }

        $schoolId = $schoolId ?? current_tenant()?->id;
        $streak = LearnerStreak::forStudent($schoolId, $studentId);
        $extended = $streak->recordActivity($activityType);

        if ($extended && $streak->current_streak > 0) {
            $this->awardStreakXp($studentId, $streak->current_streak);
        }

        return $extended;
    }

    public function getLearnerStreak(int $studentId, ?int $schoolId = null): ?LearnerStreak
    {
        $schoolId = $schoolId ?? current_tenant()?->id;

        return LearnerStreak::where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->first();
    }

    // ── Challenges ──

    public function joinChallenge(int $challengeId, int $studentId, ?int $schoolId = null): ChallengeParticipant
    {
        $schoolId = $schoolId ?? current_tenant()?->id;

        return ChallengeParticipant::firstOrCreate(
            [
                'school_id' => $schoolId,
                'gamification_challenge_id' => $challengeId,
                'student_id' => $studentId,
            ],
            [
                'progress' => 0,
                'completed' => false,
            ]
        );
    }

    public function updateChallengeProgress(int $challengeId, int $studentId, ?int $schoolId = null): ?ChallengeParticipant
    {
        $schoolId = $schoolId ?? current_tenant()?->id;

        $participant = ChallengeParticipant::where('school_id', $schoolId)
            ->where('gamification_challenge_id', $challengeId)
            ->where('student_id', $studentId)
            ->first();

        if (! $participant || $participant->completed) {
            return $participant;
        }

        $participant->incrementProgress();

        if ($participant->fresh()->completed) {
            $this->awardXp($studentId, $participant->xp_earned, 'challenge_complete', "Completed challenge");
            $challenge = GamificationChallenge::find($challengeId);
            if ($challenge && $challenge->reward_badge_id) {
                $this->awardBadge($studentId, $challenge->reward_badge_id, $schoolId);
            }
        }

        return $participant->fresh();
    }

    public function getActiveChallenges(?int $schoolId = null): Collection
    {
        $schoolId = $schoolId ?? current_tenant()?->id;

        return GamificationChallenge::where('school_id', $schoolId)
            ->active()
            ->with(['targetSubject', 'rewardBadge'])
            ->get();
    }

    public function getChallengeParticipants(int $challengeId): Collection
    {
        return ChallengeParticipant::where('gamification_challenge_id', $challengeId)
            ->with('student')
            ->orderByDesc('progress')
            ->get();
    }

    // ── Achievements ──

    public function checkAndAwardAchievements(int $studentId, ?int $schoolId = null): array
    {
        $schoolId = $schoolId ?? current_tenant()?->id;
        $settings = $this->getSettings($schoolId);

        if (! $settings->achievements_enabled) {
            return [];
        }

        $awarded = [];
        $achievements = GamificationAchievement::where('school_id', $schoolId)->active()->get();

        foreach ($achievements as $achievement) {
            if ($achievement->earnedByStudent($studentId)) {
                continue;
            }

            $criteria = $achievement->criteria ?? [];
            $qualified = true;

            foreach ($criteria as $key => $value) {
                $met = match ($key) {
                    'total_assessments_completed' => $this->countCompletedAssessments($studentId, $schoolId) >= $value,
                    'total_xp' => ($this->getLearnerXp($studentId, $schoolId)?->total_xp ?? 0) >= $value,
                    'perfect_scores' => $this->countPerfectScores($studentId, $schoolId) >= $value,
                    'longest_streak' => LearnerStreak::where('student_id', $studentId)->where('school_id', $schoolId)->value('longest_streak') >= $value,
                    'badges_earned' => LearnerBadge::where('student_id', $studentId)->where('school_id', $schoolId)->count() >= $value,
                    'level_reached' => ($this->getLearnerXp($studentId, $schoolId)?->current_level ?? 1) >= $value,
                    default => true,
                };

                if (! $met) {
                    $qualified = false;
                    break;
                }
            }

            if ($qualified) {
                $learnerAchievement = LearnerAchievement::create([
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'gamification_achievement_id' => $achievement->id,
                    'earned_at' => now(),
                    'notified' => false,
                ]);

                if ($achievement->xp_reward > 0) {
                    $this->awardXp($studentId, $achievement->xp_reward, 'achievement_reward', "Achievement: {$achievement->name}");
                }

                $awarded[] = $achievement;
            }
        }

        return $awarded;
    }

    public function getLearnerAchievements(int $studentId, ?int $schoolId = null): Collection
    {
        $schoolId = $schoolId ?? current_tenant()?->id;

        return LearnerAchievement::where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->with('achievement')
            ->orderByDesc('earned_at')
            ->get();
    }

    // ── Leaderboard ──

    public function generateLeaderboard(string $scopeType, ?int $scopeId = null, ?int $schoolId = null): array
    {
        $schoolId = $schoolId ?? current_tenant()?->id;
        $date = now()->toDateString();

        LeaderboardSnapshot::where('school_id', $schoolId)
            ->where('snapshot_date', $date)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->delete();

        $xpQuery = LearnerXp::where('school_id', $schoolId);

        if ($scopeType === 'class' && $scopeId) {
            $studentIds = \Modules\Students\Models\Student::whereHas('enrollments', fn ($q) => $q->where('section_id', $scopeId))->pluck('id');
            $xpQuery->whereIn('student_id', $studentIds);
        } elseif ($scopeType === 'subject' && $scopeId) {
            $studentIds = \Modules\DigitalAssessment\Models\DigitalAssessmentAttempt::whereHas('assessment', fn ($q) => $q->where('subject_id', $scopeId))
                ->where('status', 'graded')
                ->pluck('student_id')
                ->unique();
            $xpQuery->whereIn('student_id', $studentIds);
        }

        $rankings = $xpQuery->orderByDesc('total_xp')->get();

        foreach ($rankings as $rank => $entry) {
            LeaderboardSnapshot::create([
                'school_id' => $schoolId,
                'snapshot_date' => $date,
                'snapshot_type' => 'xp_total',
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'student_id' => $entry->student_id,
                'score' => $entry->total_xp,
                'rank_position' => $rank + 1,
                'metadata' => [
                    'level' => $entry->current_level,
                    'level_name' => $entry->current_level_name,
                ],
            ]);
        }

        return $rankings->map(fn ($entry, $rank) => [
            'rank' => $rank + 1,
            'student_id' => $entry->student_id,
            'student_name' => $entry->student?->full_name ?? 'N/A',
            'total_xp' => $entry->total_xp,
            'level' => $entry->current_level,
            'level_name' => $entry->current_level_name,
        ])->toArray();
    }

    public function getLeaderboard(string $scopeType, ?int $scopeId = null, ?int $schoolId = null, ?string $date = null): array
    {
        $schoolId = $schoolId ?? current_tenant()?->id;
        $date = $date ?? now()->toDateString();

        return LeaderboardSnapshot::where('school_id', $schoolId)
            ->where('snapshot_date', $date)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->with('student')
            ->orderBy('rank_position')
            ->get()
            ->map(fn ($s) => [
                'rank' => $s->rank_position,
                'student_id' => $s->student_id,
                'student_name' => $s->student?->full_name ?? 'N/A',
                'score' => $s->score,
                'metadata' => $s->metadata ?? [],
            ])
            ->toArray();
    }

    public function getStudentRank(int $studentId, string $scopeType, ?int $scopeId = null, ?int $schoolId = null): ?array
    {
        $schoolId = $schoolId ?? current_tenant()?->id;

        $snapshot = LeaderboardSnapshot::where('school_id', $schoolId)
            ->where('snapshot_date', now()->toDateString())
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('student_id', $studentId)
            ->first();

        return $snapshot ? [
            'rank' => $snapshot->rank_position,
            'score' => $snapshot->score,
            'metadata' => $snapshot->metadata ?? [],
        ] : null;
    }

    // ── Process Attempt (main entry point) ──

    public function processAttemptCompletion(DigitalAssessmentAttempt $attempt): array
    {
        $results = [
            'xp_awarded' => null,
            'badges_awarded' => [],
            'achievements_awarded' => [],
            'streak_extended' => false,
        ];

        $settings = $this->getSettings();

        if (! $settings->isAnyGamificationEnabled()) {
            return $results;
        }

        if ($settings->streaks_enabled) {
            $results['streak_extended'] = $this->recordStreakActivity(
                $attempt->student_id,
                'assessment_complete'
            );
        }

        if ($settings->xp_enabled) {
            $results['xp_awarded'] = $this->awardAssessmentCompletionXp($attempt);
        }

        if ($settings->badges_enabled) {
            $results['badges_awarded'] = $this->checkAndAwardBadges($attempt->student_id);
        }

        if ($settings->achievements_enabled) {
            $results['achievements_awarded'] = $this->checkAndAwardAchievements($attempt->student_id);
        }

        if ($settings->challenges_enabled) {
            $activeChallenges = $this->getActiveChallenges()->filter(
                fn ($c) => ! $c->target_subject_id || $c->target_subject_id === $attempt->assessment->subject_id
            );

            foreach ($activeChallenges as $challenge) {
                $this->joinChallenge($challenge->id, $attempt->student_id);
                $this->updateChallengeProgress($challenge->id, $attempt->student_id);
            }
        }

        return $results;
    }

    // ── Dashboard Stats ──

    public function getStudentStats(int $studentId, ?int $schoolId = null): array
    {
        $schoolId = $schoolId ?? current_tenant()?->id;

        return [
            'xp' => $this->getLearnerXp($studentId, $schoolId),
            'streak' => $this->getLearnerStreak($studentId, $schoolId),
            'badges' => $this->getLearnerBadges($studentId, $schoolId),
            'achievements' => $this->getLearnerAchievements($studentId, $schoolId),
            'completed_assessments' => $this->countCompletedAssessments($studentId, $schoolId),
            'perfect_scores' => $this->countPerfectScores($studentId, $schoolId),
        ];
    }

    // ── Helpers ──

    protected function countCompletedAssessments(int $studentId, ?int $schoolId = null): int
    {
        return DigitalAssessmentAttempt::where('student_id', $studentId)
            ->where('status', 'graded')
            ->when($schoolId, fn ($q) => $q->whereHas('assessment', fn ($aq) => $aq->where('school_id', $schoolId)))
            ->count();
    }

    protected function countPerfectScores(int $studentId, ?int $schoolId = null): int
    {
        return DigitalAssessmentAttempt::where('student_id', $studentId)
            ->where('status', 'graded')
            ->where('percentage', '>=', 100)
            ->when($schoolId, fn ($q) => $q->whereHas('assessment', fn ($aq) => $aq->where('school_id', $schoolId)))
            ->count();
    }

    protected function hasMasteryAbove(int $studentId, float $threshold, ?int $schoolId = null): bool
    {
        return \Modules\DigitalAssessment\Models\LearnerMastery::where('student_id', $studentId)
            ->where('mastery_score', '>=', $threshold)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->exists();
    }
}
