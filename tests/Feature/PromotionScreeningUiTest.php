<?php

namespace Tests\Feature;

use App\Filament\App\Resources\PromotionRunResource;
use App\Filament\App\Resources\PromotionRunResource\Pages\ListPromotionRuns;
use App\Filament\App\Resources\ScreeningRunResource;
use App\Filament\App\Resources\ScreeningRunResource\Pages\ListScreeningRuns;
use App\Models\School;
use App\Models\User;
use App\Navigation\ModuleNavigation;
use App\Navigation\ModuleNavigationService;
use App\Services\Promotion\PromotionService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\SystemSetting;
use Modules\Promotion\Models\PromotionItem;
use Modules\Promotion\Models\PromotionRun;
use Modules\Screening\Models\ScreeningItem;
use Modules\Screening\Models\ScreeningRun;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Tests\TestCase;

class PromotionScreeningUiTest extends TestCase
{
    private School $school;

    private User $user;

    private ?string $academicsSaved = null;

    private ?string $promotionSaved = null;

    private ?string $screeningSaved = null;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));

        $this->school = School::where('subdomain', 'tinwayacademy')->first();
        $this->assertTrue($this->school->id > 0, 'School tinwayacademy must exist');

        $this->actingAsTenant($this->school);

        // The resources are gated behind the academics module + their sub-page
        // toggles. Force them on for the tenant and restore afterwards.
        $this->academicsSaved = SystemSetting::get('modules', 'academics', '1');
        $this->promotionSaved = SystemSetting::get('modules', 'academics_promotion', '1');
        $this->screeningSaved = SystemSetting::get('modules', 'academics_screening', '1');
        SystemSetting::set('modules', 'academics', '1');
        SystemSetting::set('modules', 'academics_promotion', '1');
        SystemSetting::set('modules', 'academics_screening', '1');

        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->user = $this->adminUser();
    }

    protected function tearDown(): void
    {
        if ($this->academicsSaved !== null) {
            SystemSetting::set('modules', 'academics', $this->academicsSaved);
        }
        if ($this->promotionSaved !== null) {
            SystemSetting::set('modules', 'academics_promotion', $this->promotionSaved);
        }
        if ($this->screeningSaved !== null) {
            SystemSetting::set('modules', 'academics_screening', $this->screeningSaved);
        }

        if (isset($this->school)) {
            $tag = 'PRUI_';

            $uiYearIds = DB::connection('mysql')->table('academic_years')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag . '%')
                ->pluck('id');

            DB::connection('mysql')->table('screening_items')
                ->where('school_id', $this->school->id)
                ->delete();

            DB::connection('mysql')->table('screening_runs')
                ->where('school_id', $this->school->id)
                ->delete();

            DB::connection('mysql')->table('promotion_items')
                ->where('school_id', $this->school->id)
                ->delete();

            DB::connection('mysql')->table('promotion_runs')
                ->where('school_id', $this->school->id)
                ->where(function ($q) use ($uiYearIds) {
                    $q->whereIn('source_academic_year_id', $uiYearIds)
                        ->orWhereIn('target_academic_year_id', $uiYearIds);
                })
                ->delete();

            $uiStudentIds = DB::connection('mysql')->table('students')
                ->where('school_id', $this->school->id)
                ->where('first_name', 'like', $tag . '%')
                ->pluck('id');

            DB::connection('mysql')->table('enrollments')
                ->whereIn('student_id', $uiStudentIds)
                ->delete();

            DB::connection('mysql')->table('students')
                ->where('school_id', $this->school->id)
                ->where('first_name', 'like', $tag . '%')
                ->delete();

            $uiCourseIds = DB::connection('mysql')->table('courses')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag . '%')
                ->pluck('id');

            DB::connection('mysql')->table('sections')
                ->whereIn('course_id', $uiCourseIds)
                ->delete();

            DB::connection('mysql')->table('courses')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag . '%')
                ->delete();

            DB::connection('mysql')->table('academic_years')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag . '%')
                ->delete();
        }

        parent::tearDown();
    }

    private function adminUser(): User
    {
        $adminRoleId = CustomRole::where('school_id', $this->school->id)
            ->where('name', 'Administrator')
            ->value('id');

        $admin = $adminRoleId
            ? User::where('school_id', $this->school->id)->where('custom_role_id', $adminRoleId)->first()
            : null;

        // Tinotenda Wayne Hlatywayo is the canonical tenant owner.
        return $admin ?? User::findOrFail(15);
    }

    private function makeYear(string $suffix): AcademicYear
    {
        return AcademicYear::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => 'PRUI_' . $suffix,
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'is_current' => false,
        ]);
    }

    private function makeCourse(string $name, ?int $nextLevelId = null, bool $isTerminal = false): Course
    {
        return Course::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => $name . '_' . uniqid('', true),
            'code' => strtoupper(substr(md5($name . uniqid('', true)), 0, 6)),
            'next_level_id' => $nextLevelId,
            'is_terminal' => $isTerminal,
        ]);
    }

    private function makeSection(int $courseId, string $name): Section
    {
        return Section::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'course_id' => $courseId,
            'name' => $name,
            'capacity' => 40,
            'target_size' => 40,
        ]);
    }

    private function makeStudent(string $suffix): Student
    {
        $student = Student::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'first_name' => 'PRUI_' . $suffix,
            'last_name' => 'Student',
            'gender' => 'Male',
            'date_of_birth' => '2015-01-01',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);

        return $student->fresh();
    }

    private function makeEnrollment(Student $student, int $courseId, int $sectionId, int $yearId): Enrollment
    {
        return Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $yearId,
            'course_id' => $courseId,
            'section_id' => $sectionId,
            'status' => Enrollment::STATUS_ACTIVE,
            'effective_date' => '2026-09-01',
        ]);
    }

    public function test_promotion_runs_list_renders_run_history_for_tenant(): void
    {
        $sourceYear = $this->makeYear('SrcY');
        $targetYear = $this->makeYear('TgtY');
        $courseA = $this->makeCourse('PRUI_Form1');
        $courseB = $this->makeCourse('PRUI_Form2');
        $courseA->update(['next_level_id' => $courseB->id]);
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = $this->makeSection($courseB->id, 'A');

        $student = $this->makeStudent('Prev1');
        $this->makeEnrollment($student, $courseA->id, $secA->id, $sourceYear->id);

        $run = (new PromotionService())->preview(
            $this->school->id,
            $sourceYear->id,
            $targetYear->id,
            $this->user->id,
        );

        $this->actingAs($this->user);

        $component = Livewire::test(ListPromotionRuns::class);

        $component->assertOk();
        $component->assertSee('PRUI_SrcY');
        $component->assertSee('PRUI_TgtY');
        $component->assertSee('draft');
        $component->assertSee('Preview New Promotion');
    }

    public function test_preview_new_promotion_action_creates_draft_run(): void
    {
        $sourceYear = $this->makeYear('SrcA');
        $targetYear = $this->makeYear('TgtA');
        $courseA = $this->makeCourse('PRUI_Form1');
        $courseB = $this->makeCourse('PRUI_Form2');
        $courseA->update(['next_level_id' => $courseB->id]);
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = $this->makeSection($courseB->id, 'A');

        $student = $this->makeStudent('PrevA');
        $this->makeEnrollment($student, $courseA->id, $secA->id, $sourceYear->id);

        $before = PromotionRun::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->count();

        $this->actingAs($this->user);

        $component = Livewire::test(ListPromotionRuns::class);
        $component->assertOk();

        $component->callAction('previewNewRun', [
            'source_academic_year_id' => $sourceYear->id,
            'target_academic_year_id' => $targetYear->id,
        ]);

        $after = PromotionRun::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->count();

        $this->assertSame($before + 1, $after, 'preview action created one run');

        $run = PromotionRun::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->latest('id')
            ->first();

        $this->assertSame(PromotionRun::STATUS_DRAFT, $run->status);
        $this->assertSame($sourceYear->id, $run->source_academic_year_id);
        $this->assertSame($targetYear->id, $run->target_academic_year_id);
        $this->assertSame($this->user->id, $run->created_by_id);
        $this->assertCount(1, $run->items);
        $this->assertSame(PromotionItem::DECISION_PROMOTED, $run->items->first()->decision);
    }

    public function test_commit_table_action_commits_draft_run(): void
    {
        $sourceYear = $this->makeYear('SrcC');
        $targetYear = $this->makeYear('TgtC');
        $courseA = $this->makeCourse('PRUI_Form1');
        $courseB = $this->makeCourse('PRUI_Form2');
        $courseA->update(['next_level_id' => $courseB->id]);
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = $this->makeSection($courseB->id, 'A');

        $student = $this->makeStudent('PrevC');
        $this->makeEnrollment($student, $courseA->id, $secA->id, $sourceYear->id);

        $run = (new PromotionService())->preview(
            $this->school->id,
            $sourceYear->id,
            $targetYear->id,
            $this->user->id,
        );

        $this->actingAs($this->user);

        $component = Livewire::test(ListPromotionRuns::class);
        $component->callTableAction('commit', $run->getRouteKey());

        $run->refresh();

        $this->assertSame(PromotionRun::STATUS_COMMITTED, $run->status);
        $this->assertNotNull($run->committed_at);

        $newEnrollment = Enrollment::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->where('student_id', $student->id)
            ->where('academic_year_id', $targetYear->id)
            ->first();

        $this->assertNotNull($newEnrollment, 'committed run created an enrollment in target year');
        $this->assertSame(Enrollment::STATUS_ACTIVE, $newEnrollment->status);
        $this->assertSame($courseB->id, $newEnrollment->course_id);
        $this->assertSame($secB->id, $newEnrollment->section_id);

        $component->assertNotified();
    }

    public function test_screening_runs_list_renders_run_history_for_tenant(): void
    {
        $sourceYear = $this->makeYear('SrcS');
        $targetYear = $this->makeYear('TgtS');
        $courseA = $this->makeCourse('PRUI_Form6', null, true);
        $secA = $this->makeSection($courseA->id, 'A');

        $student = $this->makeStudent('Scrn1');
        $enrollment = $this->makeEnrollment($student, $courseA->id, $secA->id, $sourceYear->id);

        $run = ScreeningRun::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'source_academic_year_id' => $sourceYear->id,
            'target_academic_year_id' => $targetYear->id,
            'status' => ScreeningRun::STATUS_DRAFT,
            'created_by_id' => $this->user->id,
        ]);

        ScreeningItem::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'screening_run_id' => $run->id,
            'student_id' => $student->id,
            'source_enrollment_id' => $enrollment->id,
            'overall_score' => 71.5,
            'decision' => ScreeningItem::DECISION_UNPLACED,
            'reason' => 'No matching screening rule',
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(ListScreeningRuns::class);

        $component->assertOk();
        $component->assertSee('PRUI_SrcS');
        $component->assertSee('PRUI_TgtS');
        $component->assertSee('draft');
    }

    public function test_promotion_and_screening_resources_registered_under_progression(): void
    {
        $module = null;
        foreach (ModuleNavigation::modules() as $m) {
            if ($m['slug'] === 'academics') {
                $module = $m;
                break;
            }
        }

        $this->assertNotNull($module, 'academics module must be registered');

        $tabs = array_merge($module['tabs'] ?? [], $module['more'] ?? []);

        $promotionTab = collect($tabs)->first(fn ($t) => ($t['resource'] ?? null) === PromotionRunResource::class);
        $screeningTab = collect($tabs)->first(fn ($t) => ($t['resource'] ?? null) === ScreeningRunResource::class);

        $this->assertNotNull($promotionTab, 'PromotionRunResource listed under academics');
        $this->assertSame(__('Progression'), $promotionTab['group'] ?? null);
        $this->assertNotNull($screeningTab, 'ScreeningRunResource listed under academics');
        $this->assertSame(__('Progression'), $screeningTab['group'] ?? null);

        // The navigation service must actually resolve real URLs for both.
        App::instance('current_tenant', $this->school);
        URL::defaults(['tenant' => $this->school->subdomain]);

        $service = app(ModuleNavigationService::class);
        $resolved = collect(array_merge($service->moduleTabs($module), $service->moduleMoreTabs($module)));

        $this->assertTrue(
            $resolved->contains('class', PromotionRunResource::class),
            'PromotionRunResource resolves to a navigation tab'
        );
        $this->assertTrue(
            $resolved->contains('class', ScreeningRunResource::class),
            'ScreeningRunResource resolves to a navigation tab'
        );
    }
}