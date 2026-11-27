<?php

namespace App\Http\Responses;

use App\Security\DemoPersona;
use App\Services\DemoSandboxService;
use App\Support\DemoSandbox;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as Responsable;
use Illuminate\Http\RedirectResponse;

final class DemoLogoutResponse implements Responsable
{
    public function toResponse($request): RedirectResponse
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        if ($tenant && $tenant->is_demo_sandbox) {
            $schoolId = (int) $tenant->id;

            app()->terminating(static function () use ($schoolId): void {
                app(DemoSandboxService::class)->delete($schoolId);
            });

            return redirect()->to(DemoSandbox::centralUrl('demo/ended'));
        }

        $isDemoSession = filter_var($request->session()->get('is_demo_session'), FILTER_VALIDATE_BOOLEAN);

        if ($isDemoSession || DemoPersona::isDemoUser(auth()->user())) {
            return redirect()->to(DemoSandbox::centralUrl('demo/ended'));
        }

        return redirect()->to(DemoSandbox::centralUrl());
    }
}
