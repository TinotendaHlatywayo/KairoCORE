<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTenantNotSuspended
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Only enforce check if user is logged into a tenant context
        if ($user && $user->school_id !== null) {
            $school = app('current_tenant');

            if ($school && $school->status === 'suspended') {
                $currentRouteName = $request->route()?->getName();

                // Exempt billing view, secure download endpoints, and Livewire update handlers from redirection
                $isBillingRoute = $currentRouteName === 'filament.app.pages.saas-billing-overview'
                    || str_contains($request->getRequestUri(), 'saas-billing-overview')
                    || str_contains($request->getRequestUri(), 'livewire/update')
                    || str_contains($request->getRequestUri(), 'saas/invoice')
                    || str_contains($request->getRequestUri(), 'saas/receipt');

                if (! $isBillingRoute) {
                    return redirect()->route('filament.app.pages.saas-billing-overview');
                }
            }
        }

        return $next($request);
    }
}
