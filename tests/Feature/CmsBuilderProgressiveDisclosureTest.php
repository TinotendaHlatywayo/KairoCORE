<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The builder defaults to simple mode. Advanced tools (mix & match, page
 * structures, position nudging, block swap/importer, template creation)
 * must be gated behind editingMode === 'advanced' so the default surface
 * stays pages · blocks · theme presets · publish.
 */
class CmsBuilderProgressiveDisclosureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    public function test_advanced_panels_are_gated_behind_editing_mode(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/cms/visual-cms-builder.blade.php'));

        $gated = substr_count($blade, "@if(\$editingMode === 'advanced')");
        $closed = substr_count($blade, '@endif');

        // Mix & Match, Page Structures, Position Nudging, Swap, Create Template.
        $this->assertSame(5, $gated);

        // Every advanced @if must be closed (structural sanity: @endif count
        // must at least cover the five gates plus pre-existing ones).
        $this->assertGreaterThanOrEqual(5, $closed);

        // Insert Blocks tab is Alpine-gated, not Blade-gated.
        $this->assertStringContainsString('x-show="editingMode === \'advanced\'"', $blade);
    }

    public function test_builder_defaults_to_simple_mode(): void
    {
        $source = file_get_contents(app_path('Filament/App/Pages/VisualCmsBuilder.php'));

        $this->assertStringContainsString("public string \$editingMode = 'simple';", $source);
        $this->assertStringContainsString("'editor_mode'", $source);
    }
}
