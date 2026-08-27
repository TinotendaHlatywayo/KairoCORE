<?php

namespace App\Livewire;

use Livewire\Component;

class AdminLanguageSwitcher extends Component
{
    public function switchLocale(string $locale): void
    {
        $supported = ['en', 'sn', 'sw', 'fr', 'pt', 'es'];

        if (! in_array($locale, $supported, true)) {
            $locale = 'en';
        }

        session(['locale_admin' => $locale]);
        app()->setLocale($locale);

        // During a Livewire action request()->url() IS the update endpoint
        // (/livewire/update) — navigating there would 405. Always go back to
        // the page the user was actually on.
        $target = url()->previous();

        if (! $target || str_contains($target, '/livewire/update')) {
            $target = url('/');
        }

        $this->redirect($target, navigate: true);
    }

    public function render()
    {
        return view('components.admin-language-switcher');
    }
}
