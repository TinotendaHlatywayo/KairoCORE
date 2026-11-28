<?php

namespace Tests\Feature;

use App\Livewire\FloatingHelpAssistant;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class HelpGuideDocsLinkTest extends TestCase
{
    public function test_floating_assistant_has_chat_and_help_guide_tabs(): void
    {
        $component = Livewire::actingAs(User::factory()->create())
            ->test(FloatingHelpAssistant::class)
            ->assertSee('Kairo assistant')
            ->assertSee('Assistant')
            ->assertSee('Help guide')
            ->assertSee('Suggested questions for this page')
            ->assertSee('Guides and related pages')
            ->assertSee('Student records')
            ->assertSee('Enrol a student')
            ->assertSee('Record Student Marks')
            ->assertSee('ID Card Designer')
            ->assertSee('Student Invoices')
            ->assertSee('For more detailed instruction')
            ->assertSee('Help Centre')
            ->assertDontSee('Full Documentation')
            ->assertDontSee('Still stuck? Ask the assistant')
            ->assertSeeHtml('role="tablist"')
            ->assertSeeHtml('role="tabpanel"')
            ->assertSeeHtml('id="v-chat"')
            ->assertSeeHtml('id="v-guide"')
            ->assertSeeHtml('wire:click="showGuideFor')
            ->assertSeeHtml('target="_blank"')
            ->assertSeeHtml('rel="noopener"')
            ->assertSeeHtml('id="kh-fab"')
            ->assertSeeHtml('id="kh-panel"')
            ->assertSeeHtml('.kh-row.bot{align-items:flex-start}')
            ->assertSeeHtml('mailto:support@kairocore.co.zw')
            ->assertSeeHtml('/kfaq-engine.js');

        $html = $component->html();

        $this->assertLessThan(strpos($html, '>Help guide<'), strpos($html, '>Assistant<'));
        $this->assertSame([
            'What does System Guidance do?',
            'How do I use System Guidance?',
            'How does System Guidance connect?',
        ], $component->get('suggestions'));
    }

    public function test_guide_links_switch_the_displayed_guide(): void
    {
        $component = Livewire::actingAs(User::factory()->create())
            ->test(FloatingHelpAssistant::class)
            ->call('refreshGuide', '/workspace/students')
            ->assertSee('Student Directory & Profiles')
            ->call('showGuideFor', '/workspace/card-templates', 'App\\Filament\\App\\Resources\\CardTemplateResource')
            ->assertSee('ID Card Designer & Templates')
            ->assertDontSee('Student Directory & Profiles');

        $this->assertSame('/workspace/card-templates', $component->get('currentPath'));
        $this->assertSame('App\\Filament\\App\\Resources\\CardTemplateResource', $component->get('pageClass'));
        $this->assertSame('students', $component->get('pageCategory'));
        $this->assertSame([
            'What does ID Card Designer do?',
            'How do I use ID Card Designer?',
            'How does ID Card Designer connect?',
        ], $component->get('suggestions'));
    }

    public function test_guide_steps_are_not_numbered_twice(): void
    {
        $component = Livewire::actingAs(User::factory()->create())
            ->test(FloatingHelpAssistant::class)
            ->call('refreshGuide', '/workspace/students');

        $steps = $component->instance()->guideSteps();

        $this->assertNotEmpty($steps);

        foreach ($steps as $step) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(?:\d+[.)]|Step\s*\d+\s*[-:]?)\s*/i', $step);
        }
    }

    public function test_guide_context_is_exposed_for_accurate_answers(): void
    {
        $component = Livewire::actingAs(User::factory()->create())
            ->test(FloatingHelpAssistant::class)
            ->call('refreshGuide', '/workspace/card-templates');

        $context = $component->instance()->guideContext();

        $this->assertSame('ID Card Designer & Templates', $context['title']);
        $this->assertSame('Design, layout, preview, and activate official student and staff identification card templates with secure QR verification hashes.', $context['summary']);
        $this->assertCount(4, $context['steps']);
        $this->assertSame('Relationship', $context['details'][0]['label']);
    }

    public function test_assistant_keeps_only_page_suggestions(): void
    {
        $source = file_get_contents(resource_path('views/livewire/floating-help-assistant.blade.php'));

        $this->assertStringContainsString('setChips(state.suggestions)', $source);
        $this->assertStringNotContainsString('renderFollowups', $source);
        $this->assertStringNotContainsString('Ask next', $source);
        $this->assertStringNotContainsString('Related questions', $source);
        $this->assertStringNotContainsString('kh-follow', $source);
    }

    public function test_system_knowledge_is_available_to_assistant(): void
    {
        $modules = json_decode(file_get_contents(public_path('kairo-system-knowledge.json')), true, 512, JSON_THROW_ON_ERROR);
        $entryIds = [];

        foreach ($modules as $module) {
            $entryIds = array_merge($entryIds, $module['entryIds'] ?? []);
        }

        $this->assertGreaterThan(15, count($modules));
        $this->assertContains('report-cards', $entryIds);
        $this->assertContains('student-records', $entryIds);
    }

    public function test_floating_assistant_is_hidden_from_guests(): void
    {
        Livewire::test(FloatingHelpAssistant::class)
            ->assertDontSeeHtml('id="kh-fab"')
            ->assertDontSeeHtml('id="kh-panel"')
            ->assertDontSeeHtml('/kfaq-engine.js');
    }

    public function test_mobile_help_action_opens_floating_assistant(): void
    {
        $html = view('components.topbar-more-menu')->render();

        $this->assertStringContainsString("CustomEvent('kh-open')", $html);
        $this->assertStringNotContainsString('sc-open-help', $html);
    }
}
