<?php

namespace Tests\Feature;

use App\Livewire\FloatingHelpAssistant;
use Livewire\Livewire;
use Tests\TestCase;

class HelpGuideDocsLinkTest extends TestCase
{
    public function test_floating_assistant_has_two_tabs_and_docs_link(): void
    {
        Livewire::test(FloatingHelpAssistant::class)
            ->assertSee('Help')
            ->assertSee('Assistant')
            ->assertSee('Full Documentation')
            ->assertSeeHtml('target="_blank"')
            ->assertSeeHtml('rel="noopener"')
            ->assertSeeHtml('id="kh-fab"')
            ->assertSeeHtml('id="kh-panel"');
    }

    public function test_mobile_help_action_opens_floating_assistant(): void
    {
        $html = view('components.topbar-more-menu')->render();

        $this->assertStringContainsString("CustomEvent('kh-open')", $html);
        $this->assertStringNotContainsString('sc-open-help', $html);
    }
}
