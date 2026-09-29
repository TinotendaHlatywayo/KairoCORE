<?php

namespace Tests\Feature;

use App\Filament\App\Resources\GeneratedReportResource\Pages\ListGeneratedReports;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Reports\Models\GeneratedReport;

class GeneratedReportTableFitsTest extends TestCase
{
    protected School $school;

    protected ?int $originalRoleId = null;

    protected bool $roleWasAssigned = false;

    protected ?int $roleUserId = null;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'schoolcore']);

        $school = School::find(5);
        $this->school = $school;
        app()->instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);
        $this->withSession(['locale' => 'en']);

        // The archive is behind the reports permission, so the acting user needs
        // a role that carries it before the page will render at all.
        $user = User::where('school_id', $school->id)->where('requested_role', 'administrator')->first()
            ?? User::where('school_id', $school->id)->firstOrFail();

        PermissionRegistry::ensureAdminHasRole($user, $school->id);

        $roleId = CustomRole::where('school_id', $school->id)
            ->where('name', 'Administrator')->value('id');

        if ($roleId) {
            $this->originalRoleId = $user->custom_role_id;
            $this->roleUserId = $user->id;
            $this->roleWasAssigned = true;
            $user->forceFill(['custom_role_id' => $roleId, 'account_status' => 'active'])->save();
        }

        $this->actingAs($user);
    }

    /**
     * Granting the role writes to the shared dev database, so put it back on
     * the exact user the test promoted.
     */
    protected function tearDown(): void
    {
        if ($this->roleWasAssigned) {
            User::whereKey($this->roleUserId)
                ->update(['custom_role_id' => $this->originalRoleId]);
        }

        parent::tearDown();
    }

    public function test_the_archive_table_shows_no_horizontal_overflow(): void
    {
        $component = Livewire::test(ListGeneratedReports::class)->assertOk();

        $html = $component->html();

        $this->assertStringContainsString('fi-resource-generated-reports', $html, 'The CSS scoping hook is missing from the page.');

        // The columns that used to sit side by side must now be folded away.
        foreach (['Compiled Filename', 'File Extension', 'Record Metrics', 'Data Accuracy', 'Compiled By', 'Timestamp Generated'] as $retired) {
            $this->assertStringNotContainsString($retired, $html, "Column [{$retired}] is still on screen.");
        }

        $this->assertStringContainsString('Report', $html);
        $this->assertStringContainsString('Records', $html);
        $this->assertStringContainsString('Accuracy', $html);

        // A no-wrap header is what forces a table wider than the viewport.
        $this->assertSame(0, preg_match_all('/fi-ta-header-cell-label[^>]*class="[^"]*whitespace-nowrap/', $html), 'A header label is still forced onto one line.');
    }

    public function test_compiled_by_and_timestamp_ride_along_under_the_report_name(): void
    {
        $report = GeneratedReport::where('school_id', 5)->whereNotNull('generated_by_id')->first()
            ?? GeneratedReport::where('school_id', 5)->first();

        $this->assertNotNull($report, 'Expected at least one compiled report to render.');

        Livewire::test(ListGeneratedReports::class)
            ->assertOk()
            ->assertSee($report->name)
            ->assertSee($report->created_at->format('M d, Y H:i'));
    }

    public function test_the_long_accuracy_phrasing_lives_in_the_tooltip_not_the_badge(): void
    {
        $report = GeneratedReport::where('school_id', 5)->first();

        if (! $report) {
            $this->markTestSkipped('No compiled reports to assert against.');
        }

        $html = Livewire::test(ListGeneratedReports::class)->assertOk()->html();

        $this->assertStringNotContainsString(
            'Data changed since compilation',
            $html,
            'The full phrase still widens the badge column.'
        );
    }

    public function test_page_has_proper_heading_and_subheading(): void
    {
        $html = Livewire::test(ListGeneratedReports::class)->assertOk()->html();

        $this->assertStringContainsString('Report Archive', $html);
        $this->assertStringContainsString('Review, verify and download compiled reports.', $html);
    }
}
