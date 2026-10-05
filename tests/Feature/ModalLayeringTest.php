<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Students\Models\Student;
use Tests\TestCase;

/**
 * A dialog must be painted above every other layer in the app.
 *
 * Filament renders a modal as two siblings inside one Alpine root: the
 * overlay (.fi-modal-close-overlay) and a wrapper (div.fixed.inset-0.z-40)
 * that CONTAINS the dialog window. So the window cannot be raised on its
 * own — it lives inside the wrapper's stacking context — and the wrapper
 * must stay above its own overlay, otherwise the overlay covers the dialog
 * and swallows every click on it while x-trap.noscroll holds the scroll
 * lock. The page then looks frozen until a refresh.
 *
 * These assertions lock in both halves of that: the wrapper outranks the
 * overlay, and the modal outranks every other layer the app uses
 * (teleported dropdowns and Choices menus at 10000, the command-centre
 * overlay at 9000, sticky sidebar at 30, app footer at 20).
 */
class ModalLayeringTest extends TestCase
{
    private const OVERLAY_Z = 99998;

    private const WRAPPER_Z = 99999;

    /** Every z-index the app's own CSS can put on the page. */
    private const HIGHEST_PAGE_Z = 10000;

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

    protected function tearDown(): void
    {
        School::where('subdomain', self::FIXTURE_SUBDOMAIN)->get()->each(function (School $school): void {
            Student::where('school_id', $school->id)->forceDelete();
        });

        parent::tearDown();
    }

    private const FIXTURE_SUBDOMAIN = 'modallayeringfixture';

    private function signIn(): School
    {
        $school = School::where('subdomain', self::FIXTURE_SUBDOMAIN)->first()
            ?? School::create([
                'name' => 'Modal Layering Fixture',
                'subdomain' => self::FIXTURE_SUBDOMAIN,
                'status' => 'active',
            ]);

        $user = User::where('school_id', $school->id)
            ->where('email', 'admin@'.self::FIXTURE_SUBDOMAIN.'.test')
            ->first();

        if (! $user) {
            $user = User::create([
                'school_id' => $school->id,
                'name' => 'Layering Admin',
                'email' => 'admin@'.self::FIXTURE_SUBDOMAIN.'.test',
                'password' => bcrypt('Password@1'),
                'account_status' => 'active',
                'requested_role' => 'administrator',
            ]);

            PermissionRegistry::ensureAdminHasRole($user, $school->id);
        }

        $roleId = CustomRole::where('school_id', $school->id)->where('role_key', 'administrator')->value('id');
        $user->forceFill(['custom_role_id' => $roleId, 'account_status' => 'active'])->save();

        App::instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);
        $this->actingAs($user);

        if (Student::where('school_id', $school->id)->doesntExist()) {
            Student::create([
                'school_id' => $school->id,
                'first_name' => 'Layering',
                'last_name' => 'Probe',
                'gender' => 'male',
                'date_of_birth' => now()->subYears(10)->toDateString(),
                'admission_date' => now()->toDateString(),
                'status' => 'active',
            ]);
        }

        return $school;
    }

    /** The z-index declared for a selector in the shipped theme stylesheet. */
    private function declaredZIndex(string $selector): ?int
    {
        $css = (string) file_get_contents(resource_path('css/filament-custom.css'));

        if (! preg_match('/'.preg_quote($selector, '/').'\s*\{[^}]*?z-index:\s*(\d+)/s', $css, $m)) {
            return null;
        }

        return (int) $m[1];
    }

    public function test_the_dialog_wrapper_is_raised_above_its_own_overlay(): void
    {
        $overlay = $this->declaredZIndex('.fi-modal-close-overlay');
        $wrapper = $this->declaredZIndex('.fi-modal-close-overlay + div');

        $this->assertNotNull($overlay, 'The modal overlay must have an explicit z-index.');
        $this->assertNotNull($wrapper, 'The dialog wrapper (overlay\'s next sibling) must have an explicit z-index.');
        $this->assertSame(self::OVERLAY_Z, $overlay);
        $this->assertGreaterThan(
            $overlay,
            $wrapper,
            'The dialog wrapper must outrank its own overlay. If it does not, the overlay covers the dialog, '
                .'clicks never reach the close button, and x-trap.noscroll leaves the page frozen until a refresh.'
        );
    }

    public function test_the_dialog_sits_above_every_other_layer_the_app_uses(): void
    {
        $wrapper = $this->declaredZIndex('.fi-modal-close-overlay + div');

        $this->assertNotNull($wrapper);
        $this->assertGreaterThan(
            self::HIGHEST_PAGE_Z,
            $wrapper,
            'A dialog must outrank the teleported dropdown layers (10000), otherwise page controls such as '
                .'Export All or Bulk Actions paint on top of the open dialog.'
        );
    }

    public function test_the_dialog_window_is_not_raised_on_its_own(): void
    {
        // Guard against the regression that made the dialog unclickable:
        // a z-index here is clamped by the wrapper's stacking context and
        // implies the window can be lifted on its own, which it cannot.
        $this->assertNull(
            $this->declaredZIndex('.fi-modal-window'),
            '.fi-modal-window must not carry a z-index; it is painted inside the wrapper stacking context.'
        );
    }

    public function test_every_list_page_that_offers_help_still_renders_its_modals(): void
    {
        $school = $this->signIn();

        foreach (['/workspace/students', '/workspace/courses'] as $path) {
            $response = $this->get('http://'.$school->subdomain.'.lvh.me'.$path);

            $response->assertOk();

            $html = (string) $response->getContent();

            $this->assertStringContainsString('fi-modal-close-overlay', $html, "No modal layer rendered on {$path}.");

            // The overlay and its wrapper must be siblings inside the same
            // Alpine root, which is what the CSS relies on.
            $this->assertMatchesRegularExpression(
                '/fi-modal-close-overlay[^>]*>\s*<\/div>\s*<div[^>]*fixed inset-0 z-40/',
                $html,
                "The overlay is not immediately followed by the dialog wrapper on {$path}; the layering CSS would not apply."
            );
        }
    }
}
