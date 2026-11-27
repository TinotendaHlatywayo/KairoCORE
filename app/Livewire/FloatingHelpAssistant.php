<?php

namespace App\Livewire;

use App\Navigation\ModuleNavigationService;
use App\Security\DemoPersona;
use App\Support\HelpContent;
use App\Support\WorkspaceSearchIndex;
use Livewire\Component;

class FloatingHelpAssistant extends Component
{
    public bool $visible = false;

    public string $pageClass = '';

    public string $currentTitle = '';

    public array $help = [];

    public array $pages = [];

    public array $workflows = [];

    public array $suggestions = [];

    public string $docsUrl = '';

    public string $helpCentreUrl = '';

    public string $faviconUrl = '';

    public bool $isDemo = false;

    public function mount(): void
    {
        $this->visible = auth()->check();

        if ($this->visible) {
            $this->refreshGuide();
        }
    }

    public function refreshGuide(): void
    {
        if (! $this->visible) {
            return;
        }

        try {
            $page = filament()->getCurrentPage();
            $this->pageClass = $page ? get_class($page) : '';
        } catch (\Throwable) {
            $this->pageClass = '';
        }

        $this->help = HelpContent::for($this->pageClass);
        $this->currentTitle = (string) ($this->help['title'] ?? 'this page');
        $this->pages = $this->workspaceLinks();
        $this->workflows = $this->workflowIndex();
        $this->suggestions = $this->suggestionsFor($this->help);

        $this->docsUrl = rtrim((string) config('app.platform_url'), '/').'/docs';
        $this->helpCentreUrl = rtrim((string) config('app.platform_url'), '/').'/help';
        $this->faviconUrl = platform_favicon_url();

        try {
            $this->isDemo = DemoPersona::isDemoUser(auth()->user());
        } catch (\Throwable) {
            $this->isDemo = false;
        }
    }

    public function render()
    {
        return view('livewire.floating-help-assistant');
    }

    private function workspaceLinks(): array
    {
        $links = [];

        foreach (WorkspaceSearchIndex::items() as $item) {
            $path = self::workspacePath((string) ($item['url'] ?? ''));

            if ($path === '' || $path === '/workspace') {
                continue;
            }

            $links[] = [
                'label' => (string) ($item['label'] ?? ''),
                'group' => (string) ($item['group'] ?? ''),
                'url' => $path,
            ];
        }

        return $links;
    }

    private function workflowIndex(): array
    {
        $fallback = HelpContent::for('__no_such_page__');
        $workflows = [];

        try {
            $service = app(ModuleNavigationService::class);

            foreach ($service->modules() as $module) {
                foreach (array_merge($service->moduleTabs($module), $service->moduleMoreTabs($module)) as $tab) {
                    $class = (string) ($tab['class'] ?? '');
                    $path = self::workspacePath((string) ($tab['url'] ?? ''));

                    if ($class === '' || $path === '' || $path === '/workspace') {
                        continue;
                    }

                    $help = HelpContent::for($class);

                    if ($help === $fallback) {
                        continue;
                    }

                    $workflows[$path] = [
                        'id' => $class,
                        'title' => (string) ($help['title'] ?? ''),
                        'summary' => (string) ($help['summary'] ?? ''),
                        'workflow' => array_values($help['workflow'] ?? []),
                        'details' => $help['details'] ?? [],
                        'label' => (string) ($tab['label'] ?? $help['title'] ?? ''),
                        'group' => (string) ($tab['group'] ?? ''),
                        'module' => (string) ($module['label'] ?? ''),
                        'url' => $path,
                        'current' => $path === self::currentPath(),
                    ];
                }
            }
        } catch (\Throwable) {
        }

        $currentPath = self::currentPath();
        if ($currentPath !== '' && ! isset($workflows[$currentPath])) {
            $workflows[$currentPath] = [
                'id' => $this->pageClass,
                'title' => $this->currentTitle,
                'summary' => (string) ($this->help['summary'] ?? ''),
                'workflow' => array_values($this->help['workflow'] ?? []),
                'details' => $this->help['details'] ?? [],
                'label' => $this->currentTitle,
                'group' => '',
                'module' => '',
                'url' => $currentPath,
                'current' => true,
            ];
        }

        return array_values($workflows);
    }

    private function suggestionsFor(array $help): array
    {
        $title = (string) ($help['title'] ?? 'this page');
        $suggestions = [
            "How do I use {$title}?",
            "What should I do first on {$title}?",
            "Show me every step for {$title}",
        ];

        foreach ($help['workflow'] ?? [] as $step) {
            $name = trim((string) preg_replace('/^\s*\d+\.\s*(?:Step\s*\d+\s*-\s*)?/', '', (string) $step));
            $name = trim(explode(':', $name, 2)[0]);

            if ($name !== '') {
                $suggestions[] = 'How do I '.lcfirst($name).'?';
            }

            if (count($suggestions) >= 6) {
                break;
            }
        }

        $suggestions[] = "How does {$title} connect to other modules?";

        return array_values(array_unique($suggestions));
    }

    private static function workspacePath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        if (! is_string($path) || trim($path, '/') === '') {
            return '';
        }

        return '/'.ltrim($path, '/');
    }

    private static function currentPath(): string
    {
        try {
            return self::workspacePath(request()->path());
        } catch (\Throwable) {
            return '';
        }
    }
}
