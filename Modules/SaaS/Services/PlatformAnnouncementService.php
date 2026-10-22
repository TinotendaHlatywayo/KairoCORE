<?php

namespace Modules\SaaS\Services;

use App\Models\User;
use App\Services\AudienceTargeting;
use Illuminate\Support\Collection;
use Modules\SaaS\Models\PlatformAnnouncement;
use Modules\SaaS\Models\SaaSSubscription;

class PlatformAnnouncementService
{
    /**
     * The announcements that may be shown to the given account.
     *
     * @return Collection<int, PlatformAnnouncement>
     */
    public function forUser(User $user)
    {
        return PlatformAnnouncement::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderByDesc('id')
            ->get()
            ->filter(fn (PlatformAnnouncement $announcement): bool => $this->visibleTo($announcement, $user))
            ->values();
    }

    /**
     * The announcement to show as the tenant's top bar banner, if any.
     *
     * The newest announcement wins, so a fresh bulletin replaces the one it
     * supersedes instead of stacking underneath it.
     */
    public function bannerFor(User $user): ?PlatformAnnouncement
    {
        return PlatformAnnouncement::query()
            ->where('show_in_banner', true)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderByDesc('id')
            ->get()
            ->first(fn (PlatformAnnouncement $announcement): bool => $this->visibleTo($announcement, $user));
    }

    /**
     * Whether the announcement is addressed to the given account.
     */
    public function visibleTo(PlatformAnnouncement $announcement, User $user): bool
    {
        if (! $announcement->isWithinSchedule()) {
            return false;
        }

        // Platform accounts are not tenants; they are never addressed by a
        // tenant announcement.
        if ($user->school_id === null) {
            return false;
        }

        if (! $this->schoolMatchesPlan($announcement, (int) $user->school_id)) {
            return false;
        }

        $audience = $announcement->audience ?: PlatformAnnouncement::AUDIENCE_ALL;
        $schoolIds = array_map('intval', (array) ($announcement->target_school_ids ?? []));
        $userIds = array_map('intval', (array) ($announcement->target_user_ids ?? []));
        $roles = array_values(array_filter(array_map('strval', (array) ($announcement->target_roles ?? []))));

        switch ($audience) {
            case PlatformAnnouncement::AUDIENCE_USERS:
                if (! in_array((int) $user->id, $userIds, true)) {
                    return false;
                }

                break;

            case PlatformAnnouncement::AUDIENCE_ADMINS:
                if (! AudienceTargeting::isSystemAdministrator($user)) {
                    return false;
                }

                if ($schoolIds !== [] && ! in_array((int) $user->school_id, $schoolIds, true)) {
                    return false;
                }

                break;

            case PlatformAnnouncement::AUDIENCE_TENANTS:
                if ($schoolIds === [] || ! in_array((int) $user->school_id, $schoolIds, true)) {
                    return false;
                }

                break;

            default:
                if ($schoolIds !== [] && ! in_array((int) $user->school_id, $schoolIds, true)) {
                    return false;
                }

                break;
        }

        if ($roles !== [] && $audience !== PlatformAnnouncement::AUDIENCE_USERS) {
            if (! $this->holdsRole($user, $roles)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the account holds one of the given roles.
     *
     * @param  array<int, string>  $roles
     */
    protected function holdsRole(User $user, array $roles): bool
    {
        $query = User::withoutTenantScope()->whereKey($user->getKey());

        AudienceTargeting::scopeToRoles($query, $roles);

        return $query->exists();
    }

    /**
     * Whether the tenant's plan is one of the plans the announcement was
     * written for. An announcement without a plan reaches every plan.
     */
    protected function schoolMatchesPlan(PlatformAnnouncement $announcement, int $schoolId): bool
    {
        $plans = array_values(array_filter(array_map('strval', (array) ($announcement->target_plans ?? []))));

        if ($plans === []) {
            return true;
        }

        $subscription = SaaSSubscription::query()
            ->where('school_id', $schoolId)
            ->latest('id')
            ->first();

        $plan = $subscription?->plan;

        if ($plan === null) {
            return false;
        }

        return in_array((string) $plan->getKey(), $plans, true)
            || in_array((string) $plan->slug, $plans, true)
            || in_array((string) $plan->name, $plans, true);
    }
}
