<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Each school gets a short memorable login code (e.g. TINWAY-PRI).
 * /school-login lets a visitor enter it; /go/{code} deep-links straight to
 * the school's own workspace login on its subdomain.
 */
class SchoolLoginCodeTest extends TestCase
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

    public function test_new_schools_get_a_login_code_automatically(): void
    {
        DB::beginTransaction();

        try {
            $school = School::create([
                'name' => 'Sunrise College '.uniqid(),
                'subdomain' => 'sunrise-'.substr(uniqid(), -6),
                'status' => 'pending',
                'institution_type' => 'secondary',
            ]);

            $this->assertNotEmpty($school->login_code);
            $this->assertMatchesRegularExpression('/^[A-Z0-9]+-[A-Z0-9]+$/', $school->login_code);
            $this->assertStringStartsWith('SUNRISE', $school->login_code);
            $this->assertStringEndsWith('-SEC', $school->login_code);
        } finally {
            DB::rollBack();
        }
    }

    public function test_duplicate_names_still_produce_unique_codes(): void
    {
        DB::beginTransaction();

        try {
            $a = School::create(['name' => 'Twin School', 'subdomain' => 'twin-a-'.uniqid(), 'status' => 'pending', 'institution_type' => 'primary']);
            $b = School::create(['name' => 'Twin School', 'subdomain' => 'twin-b-'.uniqid(), 'status' => 'pending', 'institution_type' => 'primary']);

            $this->assertNotSame($a->login_code, $b->login_code);
        } finally {
            DB::rollBack();
        }
    }

    public function test_school_login_page_renders_with_two_role_options(): void
    {
        $response = $this->get('/school-login');

        $response->assertOk();
        $response->assertSee('School Login', false);
        $response->assertSee('Platform Login', false);
        $response->assertSee('name="code"', false);
    }

    public function test_valid_code_redirects_to_the_school_workspace_login(): void
    {
        $school = School::whereNotNull('login_code')->whereNotNull('subdomain')->firstOrFail();

        $response = $this->get('/go/'.$school->login_code);

        $response->assertRedirect($school->loginUrl());
        $this->assertStringContainsString($school->subdomain.'.', $school->loginUrl());
    }

    public function test_school_login_form_redirects_on_valid_code(): void
    {
        $school = School::whereNotNull('login_code')->whereNotNull('subdomain')->firstOrFail();

        $response = $this->get('/school-login?code='.strtolower($school->login_code));

        $response->assertRedirect($school->loginUrl());
    }

    public function test_unknown_code_renders_an_error_not_a_crash(): void
    {
        $response = $this->get('/go/NOPE-NOPE');

        $response->assertNotFound();

        $page = $this->get('/school-login?code=NOPE-NOPE');
        $page->assertOk();
        $page->assertSee('No school matches', false);
    }

    public function test_marketing_header_offers_both_login_options(): void
    {
        $home = $this->get('/');

        $home->assertOk();
        // The header Login dropdown (Alpine) with both options.
        $home->assertSee('loginOpen', false);
        $home->assertSee('School Login', false);
        $home->assertSee('Platform Login', false);
        $home->assertSee('/school-login', false);
        $home->assertSee('/platform/login', false);
        // Old single link is gone.
        $home->assertDontSee('>Platform Login</a>', false);
    }
}
