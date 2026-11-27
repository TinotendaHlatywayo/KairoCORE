<?php

namespace Tests\Feature;

use App\Models\School;
use App\Services\DummyDataSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class DemoSandboxSeederGuardTest extends TestCase
{
    use DatabaseTransactions;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql.database', 'schoolcore_testing');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutTenant();
    }

    public function test_normal_school_cannot_be_seeded_or_wiped(): void
    {
        $school = $this->createSchool('normal-'.strtolower(Str::random(8)), false, false);
        $seeder = app(DummyDataSeeder::class);

        try {
            $seeder->wipe((int) $school->id);
            $this->fail('A normal school must not be wiped by demo seeder routines.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        try {
            $seeder->seed((int) $school->id);
            $this->fail('A normal school must not be seeded by demo seeder routines.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }
    }

    public function test_flagged_sandbox_and_explicit_master_demo_are_allowed(): void
    {
        $suffix = strtolower(Str::random(8));

        $sandbox = $this->createSchool('demo-'.$suffix, true, false);
        $master = School::withoutGlobalScopes()->firstOrCreate(
            ['subdomain' => 'demo'],
            [
                'name' => 'Seeder Guard Master Demo',
                'login_code' => 'GRD-'.strtoupper(Str::random(12)),
                'status' => 'active',
                'institution_type' => 'secondary',
                'allow_demo_seed_wipe' => true,
            ]
        );
        $master->forceFill(['allow_demo_seed_wipe' => true])->saveQuietly();

        $seeder = app(DummyDataSeeder::class);

        $seeder->assertSeedAllowed((int) $sandbox->id);
        $seeder->assertSeedAllowed((int) $master->id);

        $this->assertTrue(true);
    }

    private function createSchool(string $subdomain, bool $sandbox, bool $allowWipe): School
    {
        return School::withoutGlobalScopes()->create([
            'name' => 'Seeder Guard '.strtoupper($subdomain),
            'subdomain' => $subdomain,
            'login_code' => 'GRD-'.strtoupper(Str::random(12)),
            'status' => 'active',
            'institution_type' => 'secondary',
            'is_demo_sandbox' => $sandbox,
            'allow_demo_seed_wipe' => $allowWipe,
        ]);
    }
}
