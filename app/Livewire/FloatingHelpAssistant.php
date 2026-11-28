<?php

namespace App\Livewire;

use App\Navigation\ModuleNavigationService;
use App\Security\DemoPersona;
use App\Support\HelpContent;
use Filament\Pages\Page;
use Livewire\Component;

class FloatingHelpAssistant extends Component
{
    public bool $visible = false;

    public string $pageClass = '';

    private string $currentTitle = '';

    private array $help = [];

    public array $suggestions = [];

    public string $docsUrl = '';

    public string $helpCentreUrl = '';

    public string $faviconUrl = '';

    public string $currentPath = '';

    public string $pageCategory = '';

    public array $guideLinks = [];

    public bool $isDemo = false;

    public function mount(): void
    {
        $this->visible = auth()->check();

        if ($this->visible) {
            $this->refreshGuide();
        }
    }

    public function refreshGuide(?string $currentPath = null): void
    {
        if (! $this->visible) {
            return;
        }

        $this->currentPath = $this->resolveCurrentPath($currentPath);
        $this->pageClass = $this->resolvePageClass($this->currentPath);

        $this->help = HelpContent::for($this->pageClass);
        $this->currentTitle = (string) ($this->help['title'] ?? 'this page');
        $this->pageCategory = $this->pageCategoryFor($this->currentTitle);
        $this->suggestions = $this->suggestionsFor($this->help);
        $this->guideLinks = $this->guideLinksFor($this->help);

        $this->docsUrl = rtrim((string) config('app.platform_url'), '/').'/docs';
        $this->helpCentreUrl = rtrim((string) config('app.platform_url'), '/').'/help';
        $this->faviconUrl = platform_favicon_url();

        try {
            $this->isDemo = DemoPersona::isDemoUser(auth()->user());
        } catch (\Throwable) {
            $this->isDemo = false;
        }

        $this->syncContext();
    }

    public function showGuideFor(string $path, string $class = ''): void
    {
        if (! $this->visible) {
            return;
        }

        $path = self::workspacePath($path);
        $class = ltrim($class, '\\');

        if ($class === '' && $path !== '') {
            $class = $this->resolvePageClass($path);
        }

        if ($class === '') {
            return;
        }

        $help = HelpContent::for($class);
        $title = (string) ($help['title'] ?? '');

        if ($title === '') {
            return;
        }

        $this->currentPath = $path;
        $this->pageClass = $class;
        $this->help = $help;
        $this->currentTitle = $title;
        $this->pageCategory = $this->pageCategoryFor($title);
        $this->suggestions = $this->suggestionsFor($help);
        $this->guideLinks = $this->guideLinksFor($help);

        $this->syncContext();
    }

    public function guide(): array
    {
        return $this->help;
    }

    public function guideTitle(): string
    {
        return $this->currentTitle;
    }

    public function guideSteps(): array
    {
        $steps = [];

        foreach ($this->help['workflow'] ?? [] as $step) {
            $step = strip_tags((string) $step);
            $step = (string) (preg_replace('/^(?:\d+[.)]\s*|Step\s*\d+\s*[-:]?\s*)+/i', '', $step) ?? $step);
            $step = trim(str_replace('**', '', $step));

            if ($step !== '') {
                $steps[] = $step;
            }
        }

        return $steps;
    }

    public function guideContext(): array
    {
        $details = [];

        foreach ($this->help['details'] ?? [] as $label => $detail) {
            $details[] = [
                'label' => (string) $label,
                'detail' => (string) $detail,
            ];
        }

        return [
            'title' => $this->currentTitle,
            'summary' => (string) ($this->help['summary'] ?? ''),
            'steps' => $this->guideSteps(),
            'details' => $details,
        ];
    }

    public function render()
    {
        return view('livewire.floating-help-assistant');
    }

    private function pageCategoryFor(string $title): string
    {
        $title = mb_strtolower($title);

        return match (true) {
            str_contains($title, 'student'), str_contains($title, 'learner'), str_contains($title, 'card') => 'students',
            str_contains($title, 'admission'), str_contains($title, 'application') => 'admissions',
            str_contains($title, 'exam'), str_contains($title, 'assessment'), str_contains($title, 'mark') => 'exams',
            str_contains($title, 'fee'), str_contains($title, 'invoice'), str_contains($title, 'payment'), str_contains($title, 'finance') => 'finance',
            str_contains($title, 'report') => 'reports',
            str_contains($title, 'library'), str_contains($title, 'book') => 'library',
            str_contains($title, 'boarding'), str_contains($title, 'hostel'), str_contains($title, 'welfare') => 'boarding',
            str_contains($title, 'health'), str_contains($title, 'clinic') => 'health',
            str_contains($title, 'staff'), str_contains($title, 'employee'), str_contains($title, 'payroll') => 'hr',
            str_contains($title, 'homework'), str_contains($title, 'lesson') => 'lms',
            str_contains($title, 'announcement'), str_contains($title, 'message') => 'communication',
            str_contains($title, 'inventory'), str_contains($title, 'stock') => 'inventory',
            str_contains($title, 'website'), str_contains($title, 'cms') => 'website',
            str_contains($title, 'permission'), str_contains($title, 'subscription'), str_contains($title, 'setting') => 'administration',
            default => '',
        };
    }

    private function suggestionsFor(array $help): array
    {
        $title = (string) ($help['title'] ?? 'this page');
        $shortTitle = trim(explode('&', $title, 2)[0]);

        if (mb_strlen($shortTitle) > 26) {
            $shortTitle = rtrim(mb_substr($shortTitle, 0, 26)).'…';
        }

        return array_values(array_unique([
            "What does {$shortTitle} do?",
            "How do I use {$shortTitle}?",
            "How does {$shortTitle} connect?",
        ]));
    }

    private function syncContext(): void
    {
        $this->dispatch('kh-guide-updated', [
            'suggestions' => $this->suggestions,
            'faviconUrl' => $this->faviconUrl,
            'docsUrl' => $this->docsUrl,
            'helpCentreUrl' => $this->helpCentreUrl,
            'pageCategory' => $this->pageCategory,
            'guide' => $this->guideContext(),
        ]);
    }

    private function guideLinksFor(array $help): array
    {
        $targets = $this->guideTargets();
        $category = $this->pageCategoryFor((string) ($help['title'] ?? ''));
        $student = 'App\\Filament\\App\\Resources\\StudentResource';
        $marks = 'App\\Filament\\App\\Resources\\AssessmentMarkResource';
        $cardTemplate = 'App\\Filament\\App\\Resources\\CardTemplateResource';
        $invoice = 'App\\Filament\\App\\Resources\\InvoiceResource';

        $preferred = [
            'students' => [
                ['label' => 'Student records', 'class' => $student],
                ['label' => 'Enrol a student', 'class' => $student, 'suffix' => '/create'],
                ['label' => 'Record Student Marks', 'class' => $marks],
                ['label' => 'ID Card Designer', 'class' => $cardTemplate],
                ['label' => 'Student Invoices', 'class' => $invoice],
            ],
        ];

        $links = [];

        foreach ($preferred[$category] ?? [] as $preferredLink) {
            $class = (string) $preferredLink['class'];
            $target = $targets[$class] ?? null;

            if ($target === null) {
                continue;
            }

            $links[] = [
                'label' => (string) $preferredLink['label'],
                'path' => $target['path'].(string) ($preferredLink['suffix'] ?? ''),
                'class' => $class,
            ];
        }

        if ($links === []) {
            try {
                $service = app(ModuleNavigationService::class);

                foreach ($service->modules() as $module) {
                    if (($module['slug'] ?? '') !== $category) {
                        continue;
                    }

                    foreach (array_merge($service->moduleTabs($module, false), $service->moduleMoreTabs($module, false)) as $tab) {
                        $class = (string) ($tab['class'] ?? '');
                        $path = self::workspacePath((string) ($tab['url'] ?? ''));

                        if ($class !== '' && $path !== '') {
                            $links[] = [
                                'label' => (string) ($tab['label'] ?? $class),
                                'path' => $path,
                                'class' => $class,
                            ];
                        }
                    }
                }
            } catch (\Throwable) {
            }
        }

        if ($links === []) {
            foreach ([
                ['label' => 'Student records', 'class' => $student],
                ['label' => 'Enrol a student', 'class' => $student, 'suffix' => '/create'],
                ['label' => 'Record Student Marks', 'class' => $marks],
                ['label' => 'ID Card Designer', 'class' => $cardTemplate],
                ['label' => 'Student Invoices', 'class' => $invoice],
            ] as $commonLink) {
                $class = (string) $commonLink['class'];
                $target = $targets[$class] ?? null;

                if ($target !== null) {
                    $links[] = [
                        'label' => (string) $commonLink['label'],
                        'path' => $target['path'].(string) ($commonLink['suffix'] ?? ''),
                        'class' => $class,
                    ];
                }
            }
        }

        $seen = [];
        $unique = [];

        foreach ($links as $link) {
            $key = $link['path'].'|'.$link['class'];

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $link;

            if (count($unique) >= 5) {
                break;
            }
        }

        return $unique;
    }

    private function guideTargets(): array
    {
        $targets = [];

        try {
            $service = app(ModuleNavigationService::class);

            foreach ($service->modules() as $module) {
                foreach (array_merge($service->moduleTabs($module, false), $service->moduleMoreTabs($module, false)) as $tab) {
                    $class = (string) ($tab['class'] ?? '');
                    $path = self::workspacePath((string) ($tab['url'] ?? ''));

                    if ($class !== '' && $path !== '' && ! isset($targets[$class])) {
                        $targets[$class] = [
                            'label' => (string) ($tab['label'] ?? $class),
                            'path' => $path,
                            'class' => $class,
                        ];
                    }
                }
            }
        } catch (\Throwable) {
        }

        return $targets;
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

    private function resolveCurrentPath(?string $currentPath = null): string
    {
        $explicit = self::workspacePath((string) ($currentPath ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        try {
            return self::workspacePath(app(ModuleNavigationService::class)->currentPath());
        } catch (\Throwable) {
            return self::currentPath();
        }
    }

    private function resolvePageClass(?string $currentPath = null): string
    {
        try {
            $requested = self::workspacePath((string) ($currentPath ?? ''));
            $paths = $requested !== '' ? self::basePathCandidates($requested) : [];

            if ($paths === []) {
                $current = $this->currentPath;
                $paths = $current !== '' ? self::basePathCandidates($current) : [];
            }

            if ($paths !== []) {
                $service = app(ModuleNavigationService::class);

                foreach ($paths as $path) {
                    foreach ($service->modules() as $module) {
                        foreach (array_merge($service->moduleTabs($module, false), $service->moduleMoreTabs($module, false)) as $tab) {
                            $class = (string) ($tab['class'] ?? '');
                            $tabPath = self::workspacePath((string) ($tab['url'] ?? ''));

                            if ($class !== '' && $tabPath === $path) {
                                return $class;
                            }
                        }
                    }
                }
            }

            $route = request()->route();
            $action = $route ? (string) $route->getActionName() : '';
            $action = ltrim(explode('@', $action, 2)[0], '\\');

            if ($action !== '' && is_subclass_of($action, Page::class)) {
                $genericTitle = (string) (HelpContent::for('__kairo_no_page__')['title'] ?? '');
                $actionTitle = (string) (HelpContent::for($action)['title'] ?? '');

                if ($actionTitle !== '' && $actionTitle !== $genericTitle) {
                    return $action;
                }
            }
        } catch (\Throwable) {
        }

        return '';
    }

    private static function basePathCandidates(string $path): array
    {
        $candidates = [$path];

        if (str_ends_with($path, '/create')) {
            $candidates[] = substr($path, 0, -strlen('/create'));
        }

        if (preg_match('#/[^/]+/(edit|view)$#', $path)) {
            $candidates[] = (string) preg_replace('#/[^/]+/(edit|view)$#', '', $path);
        }

        if (substr_count($path, '/') >= 3 && preg_match('#/[^/]+$#', $path)) {
            $candidates[] = dirname($path);
        }

        return array_values(array_unique($candidates));
    }
}
