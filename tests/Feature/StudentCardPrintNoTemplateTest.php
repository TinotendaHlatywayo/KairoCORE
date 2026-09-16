<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Students\Models\CardPrintHistory;
use Modules\Students\Models\CardTemplate;
use Modules\Students\Models\Student;
use Tests\TestCase;

class StudentCardPrintNoTemplateTest extends TestCase
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

    /**
     * When a school has no admin-created card templates, printing should show
     * a popup modal offering "Use Default Template" or "Create a Template",
     * instead of silently persisting a default template.
     */
    public function test_print_cards_shows_popup_when_no_admin_template(): void
    {
        $school = School::where('subdomain', 'chiwariraprimary')->first();
        if (! $school) {
            $this->markTestSkipped('Chiwarira Primary (no card templates) is not present.');
        }

        $user = User::where('school_id', $school->id)->where('requested_role', 'administrator')->first();
        if (! $user) {
            $this->markTestSkipped('No administrator available for the school.');
        }
        PermissionRegistry::ensureAdminHasRole($user, $user->school_id);
        $roleId = CustomRole::where('school_id', $school->id)->where('name', 'Administrator')->value('id');
        if ($roleId) {
            $user->forceFill(['custom_role_id' => $roleId, 'account_status' => 'active'])->save();
        }

        $student = Student::where('school_id', $school->id)->where('status', 'active')->first();
        if (! $student) {
            $this->markTestSkipped('No active student available for the school.');
        }

        // Ensure school has no admin-created templates (only system default may exist)
        CardTemplate::where('school_id', $school->id)
            ->where('is_system_default', false)
            ->delete();

        $this->actingAs($user);

        // Without use_default=1, should show popup modal
        $url = 'http://'.$school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST).'/workspace/students/cards/print?ids='.$student->id.'&layout=pvc';
        $response = $this->get($url);
        $this->assertSame(200, $response->getStatusCode());

        // Should show the "No Active ID Card Template" popup
        $content = (string) $response->getContent();
        $this->assertStringContainsString('No Active ID Card Template', $content);
        $this->assertStringContainsString('Use Default Template', $content);
        $this->assertStringContainsString('Create a Template', $content);

        // No template should have been auto-persisted
        $adminTemplates = CardTemplate::where('school_id', $school->id)
            ->where('is_system_default', false)
            ->get();
        $this->assertCount(0, $adminTemplates);
    }

    /**
     * When user chooses "Use Default Template" (use_default=1), the default
     * template should be persisted and printing should proceed.
     */
    public function test_print_cards_persists_default_when_use_default_chosen(): void
    {
        $school = School::where('subdomain', 'chiwariraprimary')->first();
        if (! $school) {
            $this->markTestSkipped('Chiwarira Primary (no card templates) is not present.');
        }

        $user = User::where('school_id', $school->id)->where('requested_role', 'administrator')->first();
        if (! $user) {
            $this->markTestSkipped('No administrator available for the school.');
        }
        PermissionRegistry::ensureAdminHasRole($user, $user->school_id);
        $roleId = CustomRole::where('school_id', $school->id)->where('name', 'Administrator')->value('id');
        if ($roleId) {
            $user->forceFill(['custom_role_id' => $roleId, 'account_status' => 'active'])->save();
        }

        $student = Student::where('school_id', $school->id)->where('status', 'active')->first();
        if (! $student) {
            $this->markTestSkipped('No active student available for the school.');
        }

        // Clean up any admin templates
        CardTemplate::where('school_id', $school->id)
            ->where('is_system_default', false)
            ->delete();

        $this->actingAs($user);

        // With use_default=1, should persist default template and print
        $url = 'http://'.$school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST).'/workspace/students/cards/print?ids='.$student->id.'&layout=pvc&use_default=1';
        $response = $this->get($url);
        $this->assertSame(200, $response->getStatusCode(), 'Expected HTTP 200 from print-cards. Body: '.substr((string) $response->getContent(), 0, 300));

        // Default template should now be persisted
        $template = CardTemplate::where('school_id', $school->id)
            ->where('name', 'Classic Academic (Default)')
            ->first();
        $this->assertNotNull($template, 'Default card template was not persisted.');
        $this->assertTrue((bool) $template->is_active, 'Persisted default template should be active.');
        $this->assertTrue((bool) $template->is_system_default, 'Persisted default should be marked as system default.');

        // Audit trail rows must reference the real template row
        $history = CardPrintHistory::where('school_id', $school->id)->where('student_id', $student->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame($template->id, $history->card_template_id);

        // Cleanup
        CardPrintHistory::where('school_id', $school->id)->where('student_id', $student->id)->delete();
        $template->delete();
        DB::table('card_templates')->where('id', $template->id)->delete();
    }
}
