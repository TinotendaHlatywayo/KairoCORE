<?php

namespace Tests\Feature;

use App\Livewire\RegistrationWizard;
use App\Models\School;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Modules\Admin\Models\SystemSetting;
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

    private function insertSchoolForLoginSearch(string $name, string $status, string $institutionType, string $loginCode): array
    {
        $school = [
            'name' => $name,
            'subdomain' => strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $name)).'-'.substr(uniqid(), -6),
            'login_code' => $loginCode,
            'status' => $status,
            'institution_type' => $institutionType,
        ];

        $school['id'] = DB::table('schools')->insertGetId($school);

        return $school;
    }

    private function deleteSchoolsForLoginSearch(array $schools): void
    {
        DB::table('schools')->whereIn('id', array_column($schools, 'id'))->delete();
    }

    public function test_new_registrations_start_with_the_digital_cobalt_theme(): void
    {
        Queue::fake();
        Config::set('tenancy.mode', 'multi');
        $subdomain = 'theme-'.substr(uniqid(), -8);

        DB::beginTransaction();

        try {
            Livewire::test(RegistrationWizard::class)
                ->set([
                    'currentStep' => 3,
                    'termsAccepted' => true,
                    'schoolName' => 'Theme Default Academy',
                    'country' => 'Zimbabwe',
                    'physicalAddress' => '12 Sample Street',
                    'language' => 'english',
                    'institutionType' => 'secondary',
                    'phone' => '+263771234567',
                    'adminName' => 'Theme Admin',
                    'adminEmail' => 'theme-'.uniqid().'@gmail.com',
                    'institutionEmail' => 'office-'.uniqid().'@gmail.com',
                    'subdomain' => $subdomain,
                    'selectedModules' => ['administration', 'saas'],
                ])
                ->call('submit')
                ->assertHasNoErrors();

            $school = School::where('subdomain', $subdomain)->firstOrFail();

            $this->assertSame(
                'digital_cobalt',
                SystemSetting::withoutTenantScope()
                    ->where('school_id', $school->id)
                    ->where('group', 'branding')
                    ->where('key', 'theme')
                    ->value('value')
            );
        } finally {
            DB::rollBack();
        }
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
        // One field for both: a short code, or a name to search for.
        $response->assertSee('name="q"', false);
    }

    public function test_valid_code_redirects_to_the_school_workspace_login(): void
    {
        $school = School::where('status', 'active')->whereNotNull('login_code')->whereNotNull('subdomain')->firstOrFail();

        $response = $this->get('/go/'.$school->login_code);

        $response->assertRedirect($school->loginUrl());
        $this->assertStringContainsString($school->subdomain.'.', $school->loginUrl());
    }

    public function test_school_login_form_redirects_on_valid_code(): void
    {
        $school = School::where('status', 'active')->whereNotNull('login_code')->whereNotNull('subdomain')->firstOrFail();

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
        // The header Login dropdown with both options.
        $home->assertSee('kf-login-desktop', false);
        $home->assertSee('kf-login-panel', false);
        $home->assertSee('School Login', false);
        $home->assertSee('Platform Login', false);
        $home->assertSee('/school-login', false);
        $home->assertSee('/platform/login', false);
        // Old single link is gone.
        $home->assertDontSee('>Platform Login</a>', false);
    }

    /**
     * Nobody types a school's name exactly. Any ordered run of its letters
     * has to find it — "mhofu chrstn" finds "Mhofu Christian Academy".
     */
    public function test_a_school_is_found_by_any_ordered_run_of_its_letters(): void
    {
        $school = $this->insertSchoolForLoginSearch('Mhofu Christian Academy', 'active', 'secondary', 'MHOFU-SEC');

        try {
            $response = $this->get('/school-login?q='.urlencode('mhofu chrstn'));

            $response->assertOk();
            $response->assertSee('Mhofu Christian Academy');
            $response->assertSee($school['login_code']);
            $response->assertSee(route('school.go', ['code' => $school['login_code']]), false);
        } finally {
            $this->deleteSchoolsForLoginSearch([$school]);
        }
    }

    public function test_the_suggestions_list_the_matching_schools(): void
    {
        $school = $this->insertSchoolForLoginSearch('Mhofu Christian Academy', 'active', 'secondary', 'MHOFU-SUG');

        try {
            $response = $this->getJson('/school-login/suggest?q='.urlencode('mhofu chrstn'));

            $response->assertOk();

            $names = array_column($response->json('results'), 'name');
            $this->assertContains('Mhofu Christian Academy', $names);

            $codes = array_column($response->json('results'), 'code');
            $this->assertContains($school['login_code'], $codes);
        } finally {
            $this->deleteSchoolsForLoginSearch([$school]);
        }
    }

    public function test_a_partial_name_finds_the_school(): void
    {
        $school = $this->insertSchoolForLoginSearch('Tinway Primary School', 'active', 'primary', 'TINWAY-PRI');

        try {
            $response = $this->get('/school-login?q=TIN');

            $response->assertOk();
            $response->assertSee('Tinway Primary School');
            $response->assertSee($school['login_code']);
        } finally {
            $this->deleteSchoolsForLoginSearch([$school]);
        }
    }

    public function test_no_schools_are_listed_without_a_search_term(): void
    {
        $schools = [
            $this->insertSchoolForLoginSearch('Active Test Academy', 'active', 'secondary', 'ACTIVE-SEC'),
            $this->insertSchoolForLoginSearch('Pending Test Academy', 'pending', 'secondary', 'PENDING-SEC'),
            $this->insertSchoolForLoginSearch('Suspended Test Academy', 'suspended', 'secondary', 'SUSPEND-SEC'),
        ];

        try {
            $response = $this->get('/school-login');

            $response->assertOk();
            foreach ($schools as $school) {
                $response->assertDontSee($school['name']);
                $response->assertDontSee($school['login_code']);
            }
        } finally {
            $this->deleteSchoolsForLoginSearch($schools);
        }
    }

    public function test_suggest_returns_no_results_without_a_search_term(): void
    {
        $school = $this->insertSchoolForLoginSearch('Tinway Primary School', 'active', 'primary', 'TINWAY-PRI');

        try {
            $this->getJson('/school-login/suggest')
                ->assertOk()
                ->assertJsonPath('results', [])
                ->assertJsonPath('exact', null);
        } finally {
            $this->deleteSchoolsForLoginSearch([$school]);
        }
    }

    public function test_missing_characters_find_the_school(): void
    {
        $school = $this->insertSchoolForLoginSearch('Tinway Primary School', 'active', 'primary', 'TINWAY-PRI');

        try {
            $this->get('/school-login?q=tnway')
                ->assertOk()
                ->assertSee('Tinway Primary School')
                ->assertSee($school['login_code']);

            $this->getJson('/school-login/suggest?q=tnway')
                ->assertOk()
                ->assertJsonFragment([
                    'name' => 'Tinway Primary School',
                    'code' => $school['login_code'],
                ]);
        } finally {
            $this->deleteSchoolsForLoginSearch([$school]);
        }
    }

    public function test_a_suspended_school_is_offered_but_cannot_be_signed_in_to(): void
    {
        $school = $this->insertSchoolForLoginSearch('Mhofu Christian Academy', 'suspended', 'secondary', 'MHOFU-SUSP');

        try {
            $response = $this->get('/school-login?q='.urlencode('Mhofu Christian Academy'));

            $response->assertOk();
            $response->assertSee('Mhofu Christian Academy');
            $response->assertSee($school['login_code']);
            $response->assertSee('Suspended');

            $this->get('/go/'.$school['login_code'])
                ->assertRedirect(route('school.login', ['code' => $school['login_code']]));
        } finally {
            $this->deleteSchoolsForLoginSearch([$school]);
        }
    }

    public function test_a_suspended_school_cannot_be_signed_in_to_by_its_code(): void
    {
        $school = $this->insertSchoolForLoginSearch('Mhofu Christian Academy', 'suspended', 'secondary', 'MHOFU-CODE');

        try {
            $page = $this->get('/school-login?q='.urlencode($school['login_code']));

            $page->assertOk();
            $page->assertSee('cannot be signed in to', false);

            $this->getJson('/school-login/suggest?q='.$school['login_code'])
                ->assertOk()
                ->assertJsonPath('exact', null);

            $this->get('/go/'.$school['login_code'])
                ->assertRedirect(route('school.login', ['code' => $school['login_code']]));
        } finally {
            $this->deleteSchoolsForLoginSearch([$school]);
        }
    }
}
