<?php

namespace Tests\Feature;

use App\Http\Controllers\StudentCardPrintController;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Students\Models\CardTemplate;
use Modules\Students\Models\Student;
use Tests\TestCase;

class IdCardPrintRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.env', 'local');
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    public function test_print_cards_route_is_not_captured_by_view_record(): void
    {
        $school = School::where('subdomain', 'rujeko')->first();
        if (! $school) {
            $school = School::create(['name' => 'Rujeko High', 'subdomain' => 'rujeko', 'status' => 'active']);
        }

        $user = User::where('school_id', $school->id)->first();
        if (! $user) {
            $user = User::create([
                'school_id' => $school->id,
                'name' => 'Admin',
                'email' => 'admin@rujeko.test',
                'password' => bcrypt('Password@1'),
                'account_status' => 'active',
                'requested_role' => 'administrator',
            ]);
        }
        PermissionRegistry::ensureAdminHasRole($user, $user->school_id);
        $roleId = CustomRole::where('school_id', $school->id)->where('role_key', 'administrator')->value('id');
        $user->forceFill(['custom_role_id' => $roleId, 'account_status' => 'active'])->save();

        $student = Student::where('school_id', $school->id)->where('status', 'active')->first();
        if (! $student) {
            $student = Student::create([
                'school_id' => $school->id,
                'first_name' => 'Card',
                'last_name' => 'Fixture',
                'gender' => 'other',
                'date_of_birth' => now()->subYears(10)->toDateString(),
                'admission_date' => now()->toDateString(),
                'status' => 'active',
            ]);
        }

        $this->actingAs($user);

        $response = $this->get('http://rujeko.lvh.me/workspace/students/cards/print?ids='.$student->id.'&layout=pvc');

        $this->assertNotEquals(404, $response->getStatusCode(), $response->getStatusCode().' - check Filament view route collision');
    }

    // ── Bulk PNG download ───────────────────────────────────────────────────
    //
    // The export rasterises the rendered cards with an external tool. These
    // tests drive the flow a school without any card template actually walks:
    // ask for a template, choose the built-in default, receive the files. Every
    // case runs against a throwaway school so no real tenant's templates or
    // students are touched.

    private const FIXTURE_SUBDOMAIN = 'idcardpngfixture';

    protected function tearDown(): void
    {
        StudentCardPrintController::$rasteriserCandidates = ['pdftoppm', 'gs'];

        foreach (glob(storage_path('app/public/id-cards-temp/*')) ?: [] as $file) {
            @unlink($file);
        }

        // Only the rows this fixture creates are removed; the fixture school
        // itself is reused so no tenant rows pile up in the database.
        School::where('subdomain', self::FIXTURE_SUBDOMAIN)->get()->each(function (School $school): void {
            Student::where('school_id', $school->id)->forceDelete();
            CardTemplate::where('school_id', $school->id)->delete();
        });

        parent::tearDown();
    }

    /**
     * A school with no card templates, an administrator and three active
     * students, already signed in.
     */
    private function schoolWithoutTemplates(): School
    {
        $existing = School::where('subdomain', self::FIXTURE_SUBDOMAIN)->first();

        if ($existing) {
            Student::where('school_id', $existing->id)->forceDelete();
            CardTemplate::where('school_id', $existing->id)->delete();
        }

        $school = $existing ?? School::create([
            'name' => 'ID Card PNG Fixture',
            'subdomain' => self::FIXTURE_SUBDOMAIN,
            'status' => 'active',
        ]);

        $user = User::where('school_id', $school->id)
            ->where('email', 'admin@'.self::FIXTURE_SUBDOMAIN.'.test')
            ->first();

        if (! $user) {
            $user = User::create([
                'school_id' => $school->id,
                'name' => 'Card Admin',
                'email' => 'admin@'.self::FIXTURE_SUBDOMAIN.'.test',
                'password' => bcrypt('Password@1'),
                'account_status' => 'active',
                'requested_role' => 'administrator',
            ]);

            PermissionRegistry::ensureAdminHasRole($user, $school->id);
        }

        $roleId = CustomRole::where('school_id', $school->id)->where('role_key', 'administrator')->value('id');
        $user->forceFill(['custom_role_id' => $roleId, 'account_status' => 'active'])->save();

        foreach (['Ada', 'Bore', 'Chen'] as $index => $firstName) {
            Student::create([
                'school_id' => $school->id,
                'first_name' => $firstName,
                'last_name' => 'Card'.$index,
                'gender' => 'other',
                'date_of_birth' => now()->subYears(10)->toDateString(),
                'admission_date' => now()->toDateString(),
                'status' => 'active',
            ]);
        }

        $this->actingAs($user);

        return $school;
    }

    private function studentIds(School $school): string
    {
        return Student::where('school_id', $school->id)
            ->where('status', 'active')
            ->pluck('id')
            ->implode(',');
    }

    private function downloadPng(School $school, string $query)
    {
        return $this->get('http://'.$school->subdomain.'.lvh.me/workspace/students/cards/download-png?'.$query);
    }

    public function test_png_export_asks_for_a_template_when_the_school_has_none(): void
    {
        $school = $this->schoolWithoutTemplates();

        $response = $this->downloadPng($school, 'ids='.$this->studentIds($school));

        $response->assertOk();
        $this->assertStringContainsString(
            'Use Default Template',
            (string) $response->getContent(),
            'A school with no card template must be offered the built-in default.'
        );
        $this->assertDatabaseMissing('card_templates', ['school_id' => $school->id]);
    }

    public function test_png_export_delivers_the_cards_after_the_default_template_is_chosen(): void
    {
        $school = $this->schoolWithoutTemplates();
        $ids = $this->studentIds($school);

        $response = $this->downloadPng($school, 'ids='.$ids.'&use_default=1');

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/filename=(ID_Cards_[^"]+\.zip|card_[^"]+\.png)/',
            (string) $response->headers->get('content-disposition'),
            'Choosing the default template must produce the card files, not a redirect back to the student list.'
        );
        $this->assertDatabaseHas('card_templates', [
            'school_id' => $school->id,
            'is_system_default' => true,
        ]);
    }

    public function test_png_export_stops_asking_for_a_template_once_the_default_is_saved(): void
    {
        $school = $this->schoolWithoutTemplates();
        $ids = $this->studentIds($school);

        $this->downloadPng($school, 'ids='.$ids.'&use_default=1')->assertOk();

        // Second run, no use_default: the persisted default is now the school's
        // template, so the chooser must not reappear on every later export.
        $response = $this->downloadPng($school, 'ids='.$ids);

        $response->assertOk();
        $this->assertStringNotContainsString(
            'Use Default Template',
            (string) $response->getContent(),
            'Once the built-in default exists it should be used instead of asking again.'
        );
    }

    public function test_png_export_falls_back_to_ghostscript_when_poppler_is_missing(): void
    {
        if (trim((string) @exec('command -v gs 2>/dev/null')) === '') {
            $this->markTestSkipped('Ghostscript is not installed on this host.');
        }

        $school = $this->schoolWithoutTemplates();

        StudentCardPrintController::$rasteriserCandidates = ['gs'];

        $response = $this->downloadPng($school, 'ids='.$this->studentIds($school).'&use_default=1');

        $response->assertOk();
        $this->assertStringContainsString(
            '.zip',
            (string) $response->headers->get('content-disposition'),
            'A host with Ghostscript but no poppler-utils must still export PNGs.'
        );
    }

    public function test_png_export_succeeds_in_pure_php_without_external_rasteriser(): void
    {
        $school = $this->schoolWithoutTemplates();

        $response = $this->downloadPng($school, 'ids='.$this->studentIds($school).'&use_default=1');

        $response->assertOk();
        $this->assertStringContainsString(
            '.zip',
            (string) $response->headers->get('content-disposition'),
            'Pure PHP GD rendering must successfully export PNG cards as a ZIP without external binaries.'
        );
    }

    public function test_the_bulk_png_action_is_labelled_download_as_png(): void
    {
        $school = $this->schoolWithoutTemplates();

        $response = $this->get('http://'.$school->subdomain.'.lvh.me/workspace/students');

        $response->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringContainsString('Download as PNG', $html);
        $this->assertStringNotContainsString('Download ID Cards as PNG', $html);
    }
}
