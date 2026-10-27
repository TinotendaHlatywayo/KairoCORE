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
     * The article's on-page table of contents, derived from the rendered
     * HTML so the anchor ids match the heading ids exactly. Built from
     * h2/h3 headings (h1 is the article title itself).
     *
     * @return array<int, array{level: int, id: string, text: string}>
     */
    public function toc(): array
    {
        return self::tocFromHtml($this->renderHtml());
    }

    /**
     * @return array<int, array{level: int, id: string, text: string}>
     */
    public static function tocFromHtml(string $html): array
    {
        if (! preg_match_all('/<h([23]) id="([^"]+)">(.*?)<\/h\1>/s', $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        return array_map(fn (array $match): array => [
            'level' => (int) $match[1],
            'id' => $match[2],
            'text' => trim(html_entity_decode(strip_tags($match[3]))),
        ], $matches);
    }

    /**
     * Rough reading time at 200 words per minute, minimum one minute.
     */
    public function readMinutes(): int
    {
        return max(1, (int) ceil(str_word_count($this->plainText()) / 200));
    }

    /**
     * Plain-text version for search indexing and meta descriptions.
     */
    public function plainText(): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags(app(MarkdownRenderer::class)->toHtml($this->markdown))));
    }
}
