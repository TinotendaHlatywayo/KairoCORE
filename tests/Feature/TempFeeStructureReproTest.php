<?php

namespace Tests\Feature;

use App\Filament\App\Resources\FeeStructureResource\Pages\CreateFeeStructure;
use App\Filament\App\Resources\FeeStructureResource\Pages\EditFeeStructure;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Finance\Models\FeeCategory;
use Modules\Finance\Models\FeeStructure;

class TempFeeStructureReproTest extends TestCase
{
    protected int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'schoolcore']);
        $this->schoolId = (int) config('tenancy.single_tenant_id');
        $school = School::findOrFail($this->schoolId);
        app()->instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);
        $this->withSession(['locale' => 'en']);
    }

    protected function tenantHost(): string
    {
        $school = School::findOrFail($this->schoolId);

        return $school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST).':8000';
    }

    public function test_fee_structure_create(): void
    {
        $user = User::where('school_id', $this->schoolId)->where('requested_role', 'administrator')->firstOrFail();
        $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->get('/workspace/fee-structures')->assertOk();
        echo "\nINDEX OK\n";
        $this->get('/workspace/fee-structures/create')->assertOk();
        echo "CREATE PAGE OK\n";

        $cat = FeeCategory::withoutGlobalScopes()->where('school_id', $this->schoolId)->first()
            ?? FeeCategory::create(['school_id' => $this->schoolId, 'name' => 'Repro Cat']);
        $year = AcademicYear::withoutGlobalScopes()->where('school_id', $this->schoolId)->where('is_active', true)->first();
        $term = $year ? $year->terms()->first() : null;
        $course = Course::withoutGlobalScopes()->where('school_id', $this->schoolId)->first();

        echo 'term='.($term?->id ?? 'NONE').' year='.($year?->id ?? 'NONE').' course='.($course?->id ?? 'NONE')."\n";

        Livewire::test(CreateFeeStructure::class)
            ->assertOk()
            ->fillForm([
                'fee_category_id' => $cat->id,
                'scope_type' => 'single',
                'course_id' => $course?->id,
                'academic_year_id' => $year?->id,
                'term_id' => $term?->id,
                'currency' => 'USD',
                'amount' => 150,
            ])
            ->call('create')
            ->assertHasNoErrors()
            ->assertOk();

        echo "CREATE VIA LIVEWIRE (single+course) OK\n";

        $this->get('/workspace/fee-structures')->assertOk();
        echo "INDEX AFTER CREATE OK\n";

        $record = FeeStructure::withoutGlobalScopes()->orderByDesc('id')->first();
        echo "created record id={$record->id}\n";

        $this->get("/workspace/fee-structures/{$record->id}/edit")->assertOk();
        echo "EDIT PAGE OK\n";

        Livewire::test(EditFeeStructure::class, ['record' => $record->getRouteKey()])
            ->assertOk();
        echo "LIVEWIRE EDIT OK\n";
    }
}
