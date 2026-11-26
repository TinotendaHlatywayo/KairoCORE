<?php

namespace App\Livewire;

use App\Security\DemoPersona;
use App\Security\RoleCatalogue;
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
        DemoPersona::clear();
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return $this->redirect(route('demo.login'), navigate: false);
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
}
