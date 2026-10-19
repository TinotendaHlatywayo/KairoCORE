<?php

namespace App\Support\HelpCentre;

use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;

/**
 * Thin wrapper over CommonMark with GitHub-flavoured extras the docs use
 * (fenced code blocks, tables).
 */
final class MarkdownRenderer
{
    public function toHtml(string $markdown): string
    {
        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
        ]);

        return (string) $converter->convertToHtml($markdown);
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
