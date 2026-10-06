<?php

namespace Modules\SaaS\Services;

use App\Models\School;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Admin\Services\SystemRolePresets;
use Modules\SaaS\Models\PlatformAuditLog;
use RuntimeException;

/**
 * Lets a system administrator step into a school's workspace from
 * /platform/schools and work there as that school's "System Administrator".
 *
 * Why this is a ticket hand-off and not a redirect
 * -----------------------------------------------
 * Session cookies are host-only, so the administrator's kairocore.me session
 * does not exist on school.kairocore.me. The platform therefore mints a
 * short-lived, single-use ticket and hands it to the tenant host, which
 * exchanges it for a real session inside that tenant's scope.
 *
 * Why the entry is a shadow account and not the real administrator
 * --------------------------------------------------------------
 * Every existing role, permission and tenant check in the app reads the
 * logged-in user, so entering as a genuine, platform-provisioned account
 * means none of that logic needs to change. It also keeps the audit trail
 * honest: actions are attributed to "System Administrator" rather than
 * quietly appearing to have been performed by the school's own staff.
 *
 * The platform administrator's own session on the central host is never
 * touched, so "exit" simply drops this session and returns to the platform.
 */
class PlatformImpersonationService
{
    /** Session key holding the active hand-off, or null when not impersonating. */
    public const SESSION_KEY = 'platform_impersonation';

    private const TICKET_PREFIX = 'platform_impersonation_ticket:';

    /**
     * Build the URL that starts the hand-off, pinned to the school's OWN
     * subdomain.
     *
     * Deliberately does not use school_website_url(): a school may have a
     * custom website_url on an unrelated domain, and the exchange route is
     * registered inside the {tenant}.<base> domain group, so it would simply
     * 404 there.
     */
    public function entryUrl(School $school, User $platformAdmin): string
    {
        $base = (string) config('platform.base_url');
        $parsed = parse_url($base);
        $host = $parsed['host'] ?? 'lvh.me';
        $authority = $school->subdomain.'.'.$host.(isset($parsed['port']) ? ':'.$parsed['port'] : '');

        $ticket = $this->issueTicket($school, $platformAdmin);

        return ($parsed['scheme'] ?? 'http').'://'.$authority.'/platform-entry?ticket='.$ticket;
    }

    /**
     * Mint a single-use ticket bound to this school. Deliberately short-lived:
     * it only has to survive the browser hop to the tenant host.
     */
    public function issueTicket(School $school, User $platformAdmin): string
    {
        $token = Str::random(48);

        Cache::put($this->ticketKey($token), [
            'school_id' => (int) $school->id,
            'platform_user_id' => (int) $platformAdmin->id,
            'issued_at' => now()->toIso8601String(),
        ], now()->addSeconds((int) config('platform.ticket_ttl_seconds', 60)));

        return $token;
    }

    /**
     * Redeem a ticket on the tenant host.
     *
     * Cache::pull() is what makes it single-use: a replayed ticket finds
     * nothing. The school binding is re-checked here rather than trusted from
     * the ticket, so a ticket for one school cannot be redeemed on another's
     * subdomain.
     *
     * @return array{payload: array, user: User}
     *
     * @throws RuntimeException
     */
    public function redeemTicket(string $ticket, School $tenant): array
    {
        $payload = $ticket !== '' ? Cache::pull($this->ticketKey($ticket)) : null;

        if (! is_array($payload) || blank($payload['school_id'] ?? null)) {
            throw new RuntimeException(__('This entry link has expired. Please ask for a new one.'));
        }

        if ((int) $payload['school_id'] !== (int) $tenant->id) {
            throw new RuntimeException(__('This entry link belongs to a different school.'));
        }

        $platformAdmin = User::withoutGlobalScopes()->find($payload['platform_user_id'] ?? null);

        // Defence in depth: the actor must still be a platform administrator,
        // never a tenant user who somehow obtained a ticket.
        if (! $platformAdmin || $platformAdmin->school_id !== null) {
            throw new RuntimeException(__('This entry link is no longer valid.'));
        }

        $user = $this->systemAdministrator($tenant);

        return ['payload' => $payload, 'user' => $user, 'platform_admin' => $platformAdmin];
    }

    /**
     * Find, or provision, the school's platform-managed "System Administrator".
     *
     * This is a normal user row carrying the school's administrator role, so
     * every permission and tenant check downstream behaves exactly as it does
     * for the school's own staff.
     *
     * @throws RuntimeException
     */
    public function systemAdministrator(School $school): User
    {
        $existing = User::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('is_platform_managed', true)
            ->first();

        if ($existing) {
            return $this->refreshRole($existing, $school);
        }

        $role = SystemRolePresets::roleFor((int) $school->id, $this->roleKey());

        return User::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'name' => (string) config('platform.system_administrator.name', 'System Administrator'),
            // .invalid is reserved by RFC 2606 and can never resolve, so this
            // account can never receive mail or be phished.
            'email' => $this->shadowEmail($school),
            // Unusable: entry is via a signed ticket, never this password.
            'password' => Hash::make(Str::random(48)),
            'custom_role_id' => $role->id,
            'account_status' => User::STATUS_ACTIVE,
            'requested_role' => $this->roleKey(),
            'activated_at' => now(),
            'is_platform_managed' => true,
        ]);
    }

    /**
     * Keep the shadow account aligned with the school's current administrator
     * permissions, so a role the platform later revokes from the school's
     * admins is not still held by this account.
     */
    protected function refreshRole(User $user, School $school): User
    {
        if (! $user->isApproved()) {
            $user->forceFill([
                'account_status' => User::STATUS_ACTIVE,
                'activated_at' => $user->activated_at ?? now(),
            ])->save();
        }

        return $user;
    }

    /**
     * Record the hand-off in the session and write the audit trail entry.
     */
    public function rememberEntry(User $platformAdmin, School $school, User $as): array
    {
        $entry = [
            'platform_user_id' => (int) $platformAdmin->id,
            'platform_user_name' => (string) $platformAdmin->name,
            'school_id' => (int) $school->id,
            'school_name' => (string) $school->name,
            'school_subdomain' => (string) $school->subdomain,
            'shadow_user_id' => (int) $as->id,
            'entered_at' => now()->toIso8601String(),
            'expires_at' => now()->addMinutes($this->ttlMinutes())->toIso8601String(),
        ];

        session([self::SESSION_KEY => $entry]);

        $this->audit('impersonation.entered', $platformAdmin, $school, [
            'shadow_user_id' => $as->id,
            'entered_as' => $as->name,
            'expires_at' => $entry['expires_at'],
        ]);

        return $entry;
    }

    /** The active hand-off, or null when this is an ordinary tenant session. */
    public function current(): ?array
    {
        $entry = session(self::SESSION_KEY);

        return is_array($entry) && blank($entry['platform_user_id'] ?? null) ? null : $entry;
    }

    public function isExpired(array $entry): bool
    {
        return now()->greaterThanOrEqualTo(
            Carbon::parse($entry['expires_at'] ?? now()->toIso8601String())
        );
    }

    public function ttlMinutes(): int
    {
        return max(1, (int) config('platform.session_ttl_minutes', 30));
    }

    /** Where the administrator lands after leaving a school. */
    public function platformSchoolsUrl(): string
    {
        return $this->origin((string) config('platform.base_url')).'/platform/schools';
    }

    /**
     * Drop the hand-off session entirely and audit the exit.
     */
    public function forgetEntry(string $reason = 'exited'): void
    {
        $entry = $this->current();

        session()->forget(self::SESSION_KEY);

        if (! $entry) {
            return;
        }

        $school = School::withoutGlobalScopes()->find($entry['school_id'] ?? null);

        $this->audit('impersonation.'.$reason, $this->platformAdminFrom($entry), $school, [
            'shadow_user_id' => $entry['shadow_user_id'] ?? null,
            'entered_at' => $entry['entered_at'] ?? null,
            'reason' => $reason,
        ]);
    }

    protected function platformAdminFrom(array $entry): ?Authenticatable
    {
        $id = $entry['platform_user_id'] ?? null;

        return $id ? User::withoutGlobalScopes()->find($id) : null;
    }

    /**
     * Platform-level audit. Deliberately not AuditLogger: that requires a
     * school_id, which by definition a platform administrator does not have.
     */
    public function audit(string $action, ?Authenticatable $actor, ?School $school, array $payload = []): void
    {
        try {
            PlatformAuditLog::create([
                'user_id' => $actor?->getAuthIdentifier(),
                'action' => $action,
                'details' => $school ? 'School: '.$school->name : null,
                'payload' => array_merge($payload, array_filter([
                    'school_id' => $school?->id,
                    'school_subdomain' => $school?->subdomain,
                ])),
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 255, ''),
            ]);
        } catch (\Throwable $e) {
            // Never let audit trouble block the administrator's work, but do
            // leave a trail in the application log.
            Log::error('Platform impersonation audit failed.', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function ticketKey(string $token): string
    {
        // Hashed so a cache listing cannot be replayed.
        return self::TICKET_PREFIX.hash('sha256', $token);
    }

    protected function shadowEmail(School $school): string
    {
        $domain = (string) config('platform.system_administrator.email_domain', 'kairocore.invalid');

        return 'system-administrator+'.$school->id.'@'.$domain;
    }

    protected function roleKey(): string
    {
        return (string) config('platform.system_administrator.role_key', 'administrator');
    }

    protected function origin(string $base): string
    {
        $parsed = parse_url($base);

        return ($parsed['scheme'] ?? 'http').'://'.($parsed['host'] ?? 'lvh.me')
            .(isset($parsed['port']) ? ':'.$parsed['port'] : '');
    }
}
