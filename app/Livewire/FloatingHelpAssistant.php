<?php

namespace App\Livewire;

use App\Security\DemoPersona;
use App\Support\HelpContent;
use App\Support\WorkspaceSearchIndex;
use Livewire\Component;

class FloatingHelpAssistant extends Component
{
    public string $pageClass = '';

    public array $help = [];

    public array $pages = [];

    public array $suggestions = [];

    public string $docsUrl = '';

    public string $helpCentreUrl = '';

    public string $faviconUrl = '';

    public bool $isDemo = false;

    public function mount(): void
    {
        $this->refreshGuide();
    }

    public function refreshGuide(): void
    {
        try {
            $page = filament()->getCurrentPage();
            $this->pageClass = $page ? get_class($page) : '';
        } catch (\Throwable $e) {
            $this->pageClass = '';
        }

        $this->help = HelpContent::for($this->pageClass);
        $this->pages = WorkspaceSearchIndex::items();

        $titles = array_values(array_filter(array_map(
            fn (array $item): string => (string) ($item['label'] ?? ''),
            $this->pages
        )));

        $this->suggestions = array_values(array_unique(array_merge([
            'How do I use this page?',
            'What does this page connect to?',
            'Where can I find related pages?',
        ], array_slice($titles, 0, 3))));

        $this->docsUrl = rtrim((string) config('app.platform_url'), '/').'/docs';
        $this->helpCentreUrl = rtrim((string) config('app.platform_url'), '/').'/help';
        $this->faviconUrl = platform_favicon_url();

        try {
            $this->isDemo = DemoPersona::isDemoUser(auth()->user());
        } catch (\Throwable $e) {
            $this->isDemo = false;
        }
    }

    public function render()
    {
        return view('livewire.floating-help-assistant');
    }
}
