<?php

namespace Tests\Feature;

use Tests\TestCase;

class HelpGuideDocsLinkTest extends TestCase
{
    public function test_help_guide_modal_links_to_full_documentation(): void
    {
        $html = view('components.global-help-button', [
            'help' => ['title' => 'x', 'summary' => 'y'],
        ])->render();

        $docsHost = parse_url(config('app.url'), PHP_URL_HOST);
        $this->assertStringContainsString('https://'.$docsHost.'/docs', $html);
        $this->assertStringContainsString('Full Documentation', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener"', $html);
    }
}
