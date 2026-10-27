<?php

namespace App\Support\HelpCentre;

use Illuminate\Support\Facades\Cache;

/**
 * Loads Help Centre articles from version-controlled Markdown files under
 * resources/docs/{category}/{slug}.md. Only files with `published: true`
 * in their front matter are ever served — draft files stay on disk.
 *
 * Categories are folders; the category label comes from the front matter
 * of its first published article (or the folder name as fallback).
 */
final class HelpCentreRepository
{
    /**
     * Human labels for the known category folders, in display order.
     * Follows the workspace's module flow: setup → people → money →
     * operations → systems.
     *
     * @var array<string, string>
     */
    public const CATEGORY_LABELS = [
        'getting-started' => 'Getting started',
        'academics' => 'Academic setup',
        'students' => 'Students & enrolment',
        'admissions' => 'Admissions',
        'finance' => 'Finance & fees',
        'exams' => 'Assessments & report cards',
        'hr' => 'HR & payroll',
        'inventory' => 'Inventory & procurement',
        'library' => 'Library',
        'boarding' => 'Boarding & welfare',
        'health' => 'Health & clinic',
        'communication' => 'Announcements & communication',
        'lms' => 'Learning (LMS)',
        'website' => 'School website',
        'reports' => 'Reports & analytics',
        'administration' => 'Administration & permissions',
        'troubleshooting' => 'Troubleshooting',
    ];

    /**
     * One-line description per category for the index page.
     *
     * @var array<string, string>
     */
    public const CATEGORY_DESCRIPTIONS = [
        'getting-started' => 'Register your school, sign in, and take the first steps in the workspace.',
        'academics' => 'Academic years, forms or grades, streams, subjects and classrooms.',
        'students' => 'Enrol students, manage records and print student ID cards.',
        'admissions' => 'Receive and review applications from your school website, then admit students.',
        'finance' => 'Fee structures, invoicing, recording payments, balances, expenses and purchasing.',
        'exams' => 'Assessment types, marking, grading and producing report cards.',
        'hr' => 'Staff records, spreadsheet imports, salary grades, payroll, attendance and leave.',
        'inventory' => 'Stock levels, procurement requests, purchase orders, suppliers and fixed assets.',
        'library' => 'Book catalogue, e-resources, issuing and returns, and knowledge galleries.',
        'boarding' => 'Hostels, rooms, allocations, out passes and welfare checks.',
        'health' => 'Student medical records and clinic visit logs.',
        'communication' => 'Announcements, events, chat and school-wide messaging.',
        'lms' => 'Homework and online learning activities for your classes.',
        'website' => 'Build and publish your school’s public website: pages, templates and content.',
        'reports' => 'Dashboards, the report generator and the analytics explorer.',
        'administration' => 'Staff accounts, custom roles, system settings and granular permissions.',
        'troubleshooting' => 'Common issues and how to resolve them.',
    ];

    public function __construct(private readonly MarkdownRenderer $markdown) {}

    /**
     * All published articles, ordered by category order then article order.
     *
     * @return array<int, HelpArticle>
     */
    public function all(): array
    {
        return $this->allForLocale(app()->getLocale());
    }

    /**
     * All published articles for a locale. Localised Markdown lives under
     * resources/docs/{locale}/{category}/{slug}.md and overrides the English
     * file with the same category + slug; anything not translated falls back
     * to the English article so the Help Centre is never half-empty.
     *
     * @return array<int, HelpArticle>
     */
    private function allForLocale(string $locale): array
    {
        // Cache plain arrays, not objects: HelpArticle uses readonly
        // properties, which PHP cannot unserialize from file/redis stores.
        $rows = Cache::remember('help-centre.articles.'.$locale, 600, function () use ($locale): array {
            $base = resource_path('docs');

            if (! is_dir($base)) {
                return [];
            }

            $localisedDir = $base.'/'.$locale;
            $hasLocalised = $locale !== 'en' && is_dir($localisedDir);

            $byKey = [];

            // English first, then overlay the translated article when present.
            foreach (glob($base.'/*/*.md') ?: [] as $file) {
                $article = $this->loadFile($file);
                if ($article !== null && $article->published) {
                    $byKey[$article->category.'/'.$article->slug] = $article;
                }
            }

            if ($hasLocalised) {
                foreach (glob($localisedDir.'/*/*.md') ?: [] as $file) {
                    $article = $this->loadFile($file);
                    if ($article !== null && $article->published) {
                        $byKey[$article->category.'/'.$article->slug] = $article;
                    }
                }
            }

            $articles = array_values($byKey);

            usort($articles, function (HelpArticle $a, HelpArticle $b): int {
                $cat = $this->categoryOrder($a->category) <=> $this->categoryOrder($b->category);

                return $cat !== 0 ? $cat : $a->order <=> $b->order;
            });

            return array_map(fn (HelpArticle $a) => [
                'slug' => $a->slug,
                'category' => $a->category,
                'title' => $a->title,
                'summary' => $a->summary,
                'markdown' => $a->markdown,
                'order' => $a->order,
                'published' => $a->published,
                'file_path' => $a->filePath,
                'updated_at' => $a->updatedAt,
            ], $articles);
        });

        return array_map(fn (array $row) => new HelpArticle(
            slug: $row['slug'],
            category: $row['category'],
            title: $row['title'],
            summary: $row['summary'],
            markdown: $row['markdown'],
            order: $row['order'],
            published: $row['published'],
            filePath: $row['file_path'],
            updatedAt: $row['updated_at'],
        ), $rows);
    }

    /**
     * Published articles in one category folder.
     *
     * @return array<int, HelpArticle>
     */
    public function inCategory(string $category): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (HelpArticle $a) => $a->category === $category
        ));
    }

    public function find(string $category, string $slug): ?HelpArticle
    {
        foreach ($this->all() as $article) {
            if ($article->category === $category && $article->slug === $slug) {
                return $article;
            }
        }

        return null;
    }

    /**
     * Category folders that have at least one published article,
     * in display order.
     *
     * @return array<int, array{key: string, label: string, description: string, count: int}>
     */
    public function categories(): array
    {
        $byCategory = [];
        foreach ($this->all() as $article) {
            $byCategory[$article->category] = ($byCategory[$article->category] ?? 0) + 1;
        }

        $categories = [];
        foreach ($byCategory as $key => $count) {
            $categories[] = [
                'key' => $key,
                'label' => self::labelFor($key),
                'description' => self::descriptionFor($key),
                'count' => $count,
            ];
        }

        usort($categories, fn (array $a, array $b) => $this->categoryOrder($a['key']) <=> $this->categoryOrder($b['key']));

        return $categories;
    }

    /**
     * Simple relevance-ranked search over titles, summaries and body text.
     *
     * @return array<int, array{article: HelpArticle, score: int}>
     */
    public function search(string $query): array
    {
        $terms = array_values(array_filter(preg_split('/\s+/', mb_strtolower(trim($query)))));
        if ($terms === []) {
            return [];
        }

        $results = [];
        foreach ($this->all() as $article) {
            $title = mb_strtolower($article->title);
            $summary = mb_strtolower($article->summary);
            $body = mb_strtolower($article->plainText());

            $score = 0;
            foreach ($terms as $term) {
                if (str_contains($title, $term)) {
                    $score += 10;
                }
                if (str_contains($summary, $term)) {
                    $score += 5;
                }
                $score += substr_count($body, $term);
            }

            if ($score > 0) {
                $results[] = ['article' => $article, 'score' => $score];
            }
        }

        usort($results, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return $results;
    }

    /**
     * Up to 4 other published articles in the same category,
     * falling back to the newest articles overall.
     *
     * @return array<int, HelpArticle>
     */
    public function related(HelpArticle $current, int $limit = 4): array
    {
        $related = array_values(array_filter(
            $this->inCategory($current->category),
            fn (HelpArticle $a) => $a->slug !== $current->slug
        ));

        if (count($related) < $limit) {
            foreach ($this->all() as $article) {
                if ($article->slug === $current->slug || in_array($article, $related, true)) {
                    continue;
                }
                $related[] = $article;
                if (count($related) >= $limit) {
                    break;
                }
            }
        }

        return array_slice($related, 0, $limit);
    }

    /**
     * Translated display label for a category folder.
     */
    public static function labelFor(string $category): string
    {
        $key = self::CATEGORY_LABELS[$category] ?? str($category)->replace('-', ' ')->title();

        return __($key);
    }

    /**
     * Translated one-line description for a category folder.
     */
    public static function descriptionFor(string $category): string
    {
        return __(self::CATEGORY_DESCRIPTIONS[$category] ?? '');
    }

    /**
     * Clears every locale's cache — call after editing Markdown in a deployment.
     */
    public function flush(): void
    {
        foreach (array_keys(supported_locales()) as $locale) {
            Cache::forget('help-centre.articles.'.$locale);
        }
    }

    private function loadFile(string $file): ?HelpArticle
    {
        $raw = file_get_contents($file);
        if ($raw === false) {
            return null;
        }

        ['meta' => $meta, 'body' => $body] = $this->markdown->splitFrontMatter($raw);

        $category = basename(dirname($file));
        $slug = basename($file, '.md');

        return new HelpArticle(
            slug: $meta['slug'] ?? $slug,
            category: $meta['category'] ?? $category,
            title: $meta['title'] ?? str($slug)->replace('-', ' ')->title(),
            summary: $meta['summary'] ?? '',
            markdown: $body,
            order: (int) ($meta['order'] ?? 99),
            published: filter_var($meta['published'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
            filePath: $file,
            updatedAt: $meta['updated'] ?? null,
        );
    }

    private function categoryOrder(string $category): int
    {
        $position = array_search($category, array_keys(self::CATEGORY_LABELS), true);

        // array_search returns 0 for the first label ("Getting started"),
        // which `?:` treated as missing and pushed to the end of the list.
        return $position === false ? count(self::CATEGORY_LABELS) : $position;
    }
}
