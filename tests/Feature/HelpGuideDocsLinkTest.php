<?php

namespace Tests\Feature;

use App\Livewire\FloatingHelpAssistant;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class HelpGuideDocsLinkTest extends TestCase
{
    public function test_floating_assistant_has_two_tabs_docs_link_and_workflow_heading(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(FloatingHelpAssistant::class)
            ->assertSee('Help')
            ->assertSee('Assistant')
            ->assertSee('Step-by-step workflow')
            ->assertSee('Suggested questions for this page')
            ->assertSee('Full Documentation')
            ->assertSeeHtml('target="_blank"')
            ->assertSeeHtml('rel="noopener"')
            ->assertSeeHtml('id="kh-fab"')
            ->assertSeeHtml('id="kh-panel"')
            ->assertSeeHtml('/kairo-assistant-engine.js');
    }

    public function test_floating_assistant_is_hidden_from_guests(): void
    {
        Livewire::test(FloatingHelpAssistant::class)
            ->assertDontSeeHtml('id="kh-fab"')
            ->assertDontSeeHtml('id="kh-panel"')
            ->assertDontSeeHtml('/kairo-assistant-engine.js');
    }

    public function test_workspace_links_are_path_only_and_never_point_to_the_panel_root(): void
    {
        $component = Livewire::actingAs(User::factory()->create())
            ->test(FloatingHelpAssistant::class);

        foreach ($component->get('pages') as $page) {
            $this->assertStringStartsWith('/', $page['url']);
            $this->assertStringNotContainsString('://', $page['url']);
            $this->assertNotSame('/workspace', $page['url']);
        }

        foreach ($component->get('workflows') as $workflow) {
            $this->assertStringStartsWith('/', $workflow['url']);
            $this->assertStringNotContainsString('://', $workflow['url']);
            $this->assertNotSame('/workspace', $workflow['url']);
        }
    }

    public function test_mobile_help_action_opens_floating_assistant(): void
    {
        $html = view('components.topbar-more-menu')->render();

        $this->assertStringContainsString("CustomEvent('kh-open')", $html);
        $this->assertStringNotContainsString('sc-open-help', $html);
    }
}
