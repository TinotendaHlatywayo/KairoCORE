<?php

namespace App\Http\Controllers;

use App\Support\HelpCentre\HelpArticle;
use App\Support\HelpCentre\HelpCentreRepository;
use Illuminate\Http\Request;

/**
 * Public, unauthenticated Help Centre served under /docs.
 *
 * Reads version-controlled Markdown under resources/docs — no database,
 * no tenant data, no secrets. Private areas (/platform, /workspace,
 * /student) remain behind auth and are never linked from here.
 */
class HelpCentreController extends Controller
{
    public function __construct(private readonly HelpCentreRepository $repository) {}

    public function index()
    {
        $articles = $this->repository->all();

        return view('help-centre.index', [
            'categories' => $this->repository->categories(),
            'articles' => $articles,
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Help Centre'), 'url' => route('help.index')],
                ]),
            ],
        ]);
    }

    public function search(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $results = $query !== '' ? $this->repository->search($query) : [];

        return view('help-centre.search', [
            'query' => $query,
            'results' => $results,
            'categories' => $this->repository->categories(),
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Help Centre'), 'url' => route('help.index')],
                    ['name' => __('Search'), 'url' => route('help.search')],
                ]),
            ],
        ]);
    }

    public function category(string $category)
    {
        $articles = $this->repository->inCategory($category);
        abort_if($articles === [], 404);

        $label = HelpCentreRepository::labelFor($category);

        return view('help-centre.category', [
            'category' => $category,
            'label' => $label,
            'description' => HelpCentreRepository::descriptionFor($category),
            'articles' => $articles,
            'categories' => $this->repository->categories(),
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Help Centre'), 'url' => route('help.index')],
                    ['name' => $label, 'url' => route('help.category', $category)],
                ]),
            ],
        ]);
    }

    public function article(string $category, string $slug)
    {
        /** @var HelpArticle|null $article */
        $article = $this->repository->find($category, $slug);
        abort_if($article === null, 404);

        $label = HelpCentreRepository::labelFor($category);

        $html = $article->renderHtml();

        return view('help-centre.article', [
            'article' => $article,
            'category' => $category,
            'categoryLabel' => $label,
            'html' => $html,
            'toc' => HelpArticle::tocFromHtml($html),
            'readMinutes' => $article->readMinutes(),
            'related' => $this->repository->related($article),
            'categories' => $this->repository->categories(),
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Help Centre'), 'url' => route('help.index')],
                    ['name' => $label, 'url' => route('help.category', $category)],
                    ['name' => $article->title, 'url' => $article->url()],
                ]),
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'TechArticle',
                    'headline' => $article->title,
                    'description' => $article->summary,
                    'url' => $article->url(),
                    'author' => [
                        '@type' => 'Organization',
                        'name' => platform_name(),
                        'url' => url('/'),
                    ],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => platform_name(),
                        'logo' => [
                            '@type' => 'ImageObject',
                            'url' => platform_logo_url(),
                        ],
                    ],
                ],
            ],
        ]);
    }
}
