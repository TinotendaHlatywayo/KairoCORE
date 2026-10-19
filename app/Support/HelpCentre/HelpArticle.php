<?php

namespace App\Support\HelpCentre;

/**
 * One published Help Centre article, loaded from a Markdown file with
 * simple front matter. Only `published: true` articles are visible.
 */
final class HelpArticle
{
    public function __construct(
        public readonly string $slug,
        public readonly string $category,
        public readonly string $title,
        public readonly string $summary,
        public readonly string $markdown,
        public readonly int $order,
        public readonly bool $published,
        public readonly string $filePath,
        public readonly ?string $updatedAt = null,
    ) {}

    public function url(): string
    {
        return route('help.article', ['category' => $this->category, 'slug' => $this->slug]);
    }

    public function categoryUrl(): string
    {
        return route('help.category', ['category' => $this->category]);
    }

    public function renderHtml(): string
    {
        return app(MarkdownRenderer::class)->toHtml($this->markdown);
    }

    /**
     * Plain-text version for search indexing and meta descriptions.
     */
    public function plainText(): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags(app(MarkdownRenderer::class)->toHtml($this->markdown))));
    }
}
