<?php

namespace Tests\Feature;

use App\Support\HelpCentre\HelpCentreRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Docs experience: Markdown tables, callouts, heading anchors, the
 * "On this page" rail, read-time chips, and the expanded category set.
 */
class HelpDocsEnhancementTest extends TestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', env('TEST_DB_NAME', 'schoolcore'));
        Config::set('database.connections.mysql.host', env('TEST_DB_HOST', '127.0.0.1'));
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');

        $this->fixturePath = resource_path('docs/troubleshooting/zz-docs-enhancement-check.md');

        file_put_contents($this->fixturePath, <<<'MD'
---
title: Docs enhancement fixture
summary: Fixture article exercising tables, callouts and heading anchors.
category: troubleshooting
slug: zz-docs-enhancement-check
order: 99
published: true
updated: 2026-10-27
---

# Docs enhancement fixture

Intro paragraph.

## First section

| Column A | Column B |
| --- | --- |
| Alpha | 1 |
| Beta | 2 |

:::tip Helpful hint
Callout body text.
:::

:::warning Be careful
This one carries a headline.
:::

### Nested detail

More text.
MD);

        app(HelpCentreRepository::class)->flush();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->fixturePath)) {
            unlink($this->fixturePath);
        }

        app(HelpCentreRepository::class)->flush();

        parent::tearDown();
    }

    public function test_markdown_tables_render_and_are_wrapped_for_scrolling(): void
    {
        $html = $this->get('/docs/troubleshooting/zz-docs-enhancement-check')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<div class="table-wrap">', $html);
        $this->assertStringContainsString('<th>Column A</th>', $html);
        $this->assertStringContainsString('<td>Alpha</td>', $html);
    }

    public function test_callouts_render_as_icon_panels(): void
    {
        $html = $this->get('/docs/troubleshooting/zz-docs-enhancement-check')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('help-callout help-callout--tip', $html);
        $this->assertStringContainsString('help-callout help-callout--warning', $html);
        $this->assertStringContainsString('Callout body text.', $html);
        $this->assertStringContainsString('Helpful hint', $html);
        // The raw ::: syntax must never render as a paragraph (the layout's
        // stylesheet mentions :::tip in a CSS comment, so scope the check).
        $this->assertStringNotContainsString('<p>:::tip', $html);
        $this->assertStringNotContainsString('<p>:::warning', $html);
        $this->assertStringNotContainsString('@@CALLOUT_', $html);
    }

    public function test_headings_get_anchor_ids_and_the_toc_rail(): void
    {
        $html = $this->get('/docs/troubleshooting/zz-docs-enhancement-check')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="first-section"', $html);
        $this->assertStringContainsString('id="nested-detail"', $html);
        $this->assertStringContainsString('On this page', $html);
        $this->assertStringContainsString('href="#first-section"', $html);
    }

    public function test_article_pages_show_read_time_and_related_guides(): void
    {
        $html = $this->get('/docs/hr/importing-staff-from-spreadsheet')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('min read', $html);
        $this->assertStringContainsString('Related guides', $html);
        $this->assertStringContainsString('Updated', $html);
    }

    public function test_index_lists_the_new_module_categories(): void
    {
        $response = $this->get('/docs')->assertOk();

        foreach ([
            'HR & payroll',
            'Inventory & procurement',
            'Library',
            'Boarding & welfare',
            'Health & clinic',
            'Learning (LMS)',
            'School website',
            'Reports & analytics',
        ] as $label) {
            // assertSee escapes the expected value by default, matching the
            // &amp; entities in the rendered HTML.
            $response->assertSee($label);
        }
    }

    public function test_getting_started_sorts_first_on_the_index(): void
    {
        $html = $this->get('/docs')->assertOk()->getContent();

        $gettingStarted = strpos($html, 'Getting started');
        $troubleshooting = strpos($html, 'Troubleshooting');

        $this->assertNotFalse($gettingStarted);
        $this->assertNotFalse($troubleshooting);
        $this->assertLessThan($troubleshooting, $gettingStarted);
    }

    public function test_every_new_category_page_renders_its_articles(): void
    {
        $expected = [
            '/docs/hr' => 'Add and manage staff records',
            '/docs/inventory' => 'Stock, procurement and fixed assets',
            '/docs/library' => 'Run the library catalogue and circulation',
            '/docs/boarding' => 'Manage boarding and student welfare',
            '/docs/health' => 'Keep student health records and clinic visits',
            '/docs/lms' => 'Set homework and run online lessons',
            '/docs/website' => 'Build and publish your school website',
            '/docs/reports' => 'Build reports and explore analytics',
        ];

        foreach ($expected as $path => $articleTitle) {
            $this->get($path)->assertOk()->assertSee($articleTitle, false);
        }
    }

    public function test_staff_import_guide_documents_upsert_and_required_columns(): void
    {
        $response = $this->get('/docs/hr/importing-staff-from-spreadsheet')->assertOk();

        $response->assertSee('National ID', false);
        $response->assertSee('Download Excel Template', false);
        $response->assertSee('Contract End Date', false);
        $response->assertSee('updates existing', false);
    }

    public function test_article_navigation_paths_use_real_workspace_groups(): void
    {
        $html = $this->get('/docs/finance/recording-payments')->assertOk()->getContent();
        $this->assertStringContainsString('Student Billing &amp; Revenue', $html);

        $admin = $this->get('/docs/administration/staff-permissions')->assertOk()->getContent();
        $this->assertStringContainsString('User Management', $admin);
    }
}
