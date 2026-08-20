<?php

namespace Modules\SaaS\Services;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class TenantImpersonationEngine
{
    /**
     * Generate a secure, one-time signed URL to redirect an administrator [1].
     */
    public static function generateSecureLink(School|int $school): string
    {
        if (is_int($school)) {
            $school = School::findOrFail($school);
        }

        // Fetch the target school's designated master administrator account
        $targetUser = User::where('school_id', $school->id)->first();

        if (! $targetUser) {
            throw new \Exception("Cannot impersonate: No administrator account exists for target school {$school->name}.");
        }

        // Generate custom transient signature valid for exactly 60 seconds
        $token = Str::random(60);
        cache()->put("impersonation_token_{$token}", [
            'user_id' => $targetUser->id,
            'school_id' => $school->id,
        ], now()->addMinutes(1));

        // Compile custom target URL using the school's mapped subdomain config [2]
        $subdomainUrl = "http://{$school->subdomain}.lvh.me:8000/workspace/impersonated-login";

        return $subdomainUrl.'?token='.$token;
    }
}
