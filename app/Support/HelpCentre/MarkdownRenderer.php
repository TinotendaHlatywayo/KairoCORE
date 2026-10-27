<?php

namespace App\Support\HelpCentre;

use Illuminate\Support\Str;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Markdown → HTML for the Help Centre.
 *
 * Uses GitHub-flavoured Markdown (tables, strikethrough, task lists) with
 * two documented extras on top:
 *
 *  - **Callouts** — `:::tip Optional title … :::` blocks become styled
 *    `.help-callout` panels (tip / note / warning / danger / success). Raw
 *    HTML is stripped (`html_input`), so the panel markup is injected from a
 *    placeholder after rendering rather than passed through.
 *  - **Heading anchors** — every h2–h4 gets a slug `id` so the article TOC
 *    and deep links have stable targets.
 */
final class MarkdownRenderer
{
    /**
     * Canonical callout kinds and the label shown when the block carries no
     * explicit title.
     *
     * @var array<string, string>
     */
    private const CALLOUT_LABELS = [
        'tip' => 'Tip',
        'note' => 'Note',
        'info' => 'Good to know',
        'warning' => 'Warning',
        'caution' => 'Warning',
        'danger' => 'Careful',
        'error' => 'Careful',
        'success' => 'Done',
        'important' => 'Important',
    ];

    public function toHtml(string $markdown): string
    {
        $callouts = [];
        $markdown = $this->extractCallouts($markdown, $callouts);

        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
        ]);

        $html = (string) $converter->convertToHtml($markdown);
        $html = $this->attachHeadingIds($html);
        $html = $this->wrapTables($html);
        $html = $this->injectCallouts($html, $callouts);

        return $html;
    }

    /**
     * Wrap each GFM table in a scroll container so wide reference tables
     * stay readable on phones instead of overflowing the page.
     */
    private function wrapTables(string $html): string
    {
        if (! str_contains($html, '<table>')) {
            return $html;
        }

        $result = preg_replace(
            '/<table>(.*?)<\/table>/s',
            '<div class="table-wrap"><table>$1</table></div>',
            $html,
        );

        return $result ?? $html;
    }

    /**
     * Pull `:::type Title … :::` blocks out of the Markdown before rendering,
     * leaving a single-token placeholder paragraph in their place. Each
     * callout's body is rendered recursively (so nested Markdown, links and
     * even nested callouts all work), then the panel markup replaces the
     * placeholder once the main document has its heading ids.
     */
    private function extractCallouts(string $markdown, array &$callouts): string
    {
        $result = preg_replace_callback(
            '/^:::([a-z]+)(?:[ \t]+([^\n]*))?[ \t]*\n(.*?)^:::[ \t]*$/ms',
            function (array $match) use (&$callouts): string {
                $type = strtolower($match[1]);

                if (! array_key_exists($type, self::CALLOUT_LABELS)) {
                    // Unknown kind: leave the block untouched so it renders
                    // as plain text the author can spot and fix.
                    return $match[0];
                }

                $index = count($callouts);
                $callouts[$index] = [
                    'type' => $type,
                    'title' => trim($match[2] ?? ''),
                    'body' => trim($match[3]),
                ];

                return '@@CALLOUT_'.$index.'@@';
            },
            $markdown,
        );

        return $result ?? $markdown;
    }

    /**
     * Give every h2–h4 an id derived from its visible text.
     */
    private function attachHeadingIds(string $html): string
    {
        $result = preg_replace_callback(
            '/<h([2-4])>(.*?)<\/h\1>/s',
            function (array $match): string {
                $id = Str::slug(trim(html_entity_decode(strip_tags($match[2]))));

                if ($id === '') {
                    return $match[0];
                }

                return '<h'.$match[1].' id="'.$id.'">'.$match[2].'</h'.$match[1].'>';
            },
            $html,
        );

        return $result ?? $html;
    }

    /**
     * Replace the placeholder paragraphs with the finished callout panels.
     */
    private function injectCallouts(string $html, array $callouts): string
    {
        if ($callouts === []) {
            return $html;
        }

        $result = preg_replace_callback(
            '/<p>\s*@@CALLOUT_(\d+)@@\s*<\/p>/',
            function (array $match) use ($callouts): string {
                $callout = $callouts[(int) $match[1]] ?? null;

                if ($callout === null) {
                    return '';
                }

                return $this->calloutHtml(
                    $callout['type'],
                    $callout['title'],
                    $callout['body'] === '' ? '' : $this->toHtml($callout['body']),
                );
            },
            $html,
        );

        return $result ?? $html;
    }

    private function calloutHtml(string $type, string $title, string $bodyHtml): string
    {
        $label = self::CALLOUT_LABELS[$type] ?? 'Note';
        $headline = $title !== '' ? $title : $label;
        $icon = $this->calloutIcon($type);

        $titleHtml = $title !== ''
            ? '<p class="help-callout__label">'.e($label).'</p>'
                .'<p class="help-callout__title">'.e($title).'</p>'
            : '<p class="help-callout__title">'.e($headline).'</p>';

        return '<div class="help-callout help-callout--'.$type.'" role="note">'
            .'<div class="help-callout__head">'.$icon.$titleHtml.'</div>'
            .($bodyHtml !== '' ? '<div class="help-callout__body">'.$bodyHtml.'</div>' : '')
            .'</div>';
    }

    private function calloutIcon(string $type): string
    {
        $path = match ($type) {
            'tip', 'important' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.36 6.36-.7-.7M6.34 6.34l-.7-.7m12.72 0-.7.7M6.34 17.66l-.7.7M12 8a4 4 0 100 8 4 4 0 000-8z"/>',
            'warning', 'caution' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>',
            'danger', 'error' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636L5.636 18.364m12.728 0L5.636 5.636M12 8v4m0 4h.01"/>',
            'success' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            default => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        };

        return '<svg class="help-callout__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">'.$path.'</svg>';
    }

    /**
     * Extracts "key: value" front matter from the top of a Markdown file.
     *
     * @return array{meta: array<string, string>, body: string}
     */
    public function splitFrontMatter(string $raw): array
    {
        if (! str_starts_with($raw, "---\n")) {
            return ['meta' => [], 'body' => $raw];
        }

        $end = strpos($raw, "\n---", 4);
        if ($end === false) {
            return ['meta' => [], 'body' => $raw];
        }

        $frontMatter = substr($raw, 4, $end - 4);
        $body = ltrim(substr($raw, $end + 4), "\n");

        $meta = [];
        foreach (preg_split('/\r?\n/', $frontMatter) as $line) {
            if (trim($line) === '' || ! str_contains($line, ':')) {
                continue;
            }
            [$key, $value] = explode(':', $line, 2);
            $meta[Str::trim($key)] = trim($value);
        }

        return ['meta' => $meta, 'body' => $body];
    }
}
