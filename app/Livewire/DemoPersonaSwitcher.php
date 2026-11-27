<?php

namespace App\Livewire;

use App\Models\School;
use App\Security\DemoPersona;
use App\Security\RoleCatalogue;
use App\Services\DemoSandboxService;
use App\Support\DemoSandbox;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DemoPersonaSwitcher extends Component
{
    public function switchTo(string $role): mixed
    {
        $user = auth()->user();

        if (! DemoPersona::isDemoUser($user instanceof \App\Models\User ? $user : null) || ! RoleCatalogue::exists($role)) {
            return $this->redirect(request()->header('referer') ?: url('/'), navigate: false);
        }

        DemoPersona::set($role);

        $panel = RoleCatalogue::isPortalOnly($role) ? 'student' : 'app';

        return $this->redirect(Filament::getPanel($panel)->getUrl(), navigate: false);
    }

    public function leaveDemo(): mixed
    {
        return $this->endDemo('demo/ended');
    }

    public function resetDemo(): mixed
    {
        return $this->endDemo('demo-login');
    }

    public function render()
    {
        $user = auth()->user();
        $current = DemoPersona::active($user instanceof \App\Models\User ? $user : null);

        return view('livewire.demo-persona-switcher', [
            'isDemo' => DemoPersona::isDemoUser($user instanceof \App\Models\User ? $user : null),
            'personas' => RoleCatalogue::roles(),
            'current' => $current,
            'currentLabel' => $current ? RoleCatalogue::label($current) : __('Demo'),
        ]);
    }

    private function endDemo(string $path): mixed
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        $schoolId = $tenant instanceof School && $tenant->is_demo_sandbox ? (int) $tenant->id : null;
        $url = DemoSandbox::centralUrl($path);

        DemoPersona::clear();
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        if ($schoolId !== null) {
            app()->terminating(static function () use ($schoolId): void {
                app(DemoSandboxService::class)->delete($schoolId);
            });
        }

        return $this->redirect($url, navigate: false);
    }
}
