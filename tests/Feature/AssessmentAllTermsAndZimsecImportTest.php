<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\AssessmentMark;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;
use Modules\Academics\Services\AssessmentResultCalculator;
use Modules\Academics\Services\ZimsecGradingTemplates;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Tests\TestCase;

class AssessmentAllTermsAndZimsecImportTest extends TestCase
{
    use InteractsWithDatabase;

    private const TAG = 'ZIM_';

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));

        $this->school = School::where('subdomain', 'chiwariraprimary')->first();
        $this->assertTrue($this->school->id > 0, 'School chiwariraprimary must exist');

        $this->actingAsTenant($this->school);
    }

    protected function tearDown(): void
    {
        if (isset($this->school)) {
            $tag = self::TAG;

            $studentIds = DB::connection('mysql')->table('students')
                ->where('school_id', $this->school->id)
                ->where('first_name', 'like', $tag.'%')
                ->pluck('id');

            $enrollmentIds = DB::connection('mysql')->table('enrollments')
                ->whereIn('student_id', $studentIds)
                ->pluck('id');

            DB::connection('mysql')->table('assessment_marks')
                ->whereIn('enrollment_id', $enrollmentIds)
                ->delete();

            DB::connection('mysql')->table('assessment_types')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag.'%')
                ->delete();

            $courseIds = DB::connection('mysql')->table('courses')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag.'%')
                ->pluck('id');

            $sectionIds = DB::connection('mysql')->table('sections')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag.'%')
                ->pluck('id');

            DB::connection('mysql')->table('enrollments')
                ->whereIn('id', $enrollmentIds)
                ->delete();

            DB::connection('mysql')->table('sections')
                ->whereIn('id', $sectionIds)
                ->delete();

            DB::connection('mysql')->table('courses')
                ->whereIn('id', $courseIds)
                ->delete();

            DB::connection('mysql')->table('subjects')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag.'%')
                ->delete();

            $termIds = DB::connection('mysql')->table('terms')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag.'%')
                ->pluck('id');

            DB::connection('mysql')->table('terms')
                ->whereIn('id', $termIds)
                ->delete();

            DB::connection('mysql')->table('academic_years')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag.'%')
                ->delete();

            DB::connection('mysql')->table('students')
                ->whereIn('id', $studentIds)
                ->delete();
        }

        parent::tearDown();
    }

    public function test_zimsec_import_options_match_school_type(): void
    {
        $primary = new School(['institution_type' => 'primary']);
        $secondary = new School(['institution_type' => 'secondary']);
        $high = new School(['institution_type' => 'high school']);

        $this->assertSame(['zimsec_grade7' => 'ZIMSEC Grade 7 (Primary)'], ZimsecGradingTemplates::optionsFor($primary));

        $secondaryOptions = ZimsecGradingTemplates::optionsFor($secondary);
        $this->assertArrayHasKey('zimsec_olevel', $secondaryOptions);
        $this->assertArrayHasKey('zimsec_alevel', $secondaryOptions);
        $this->assertArrayHasKey('zimsec_both', $secondaryOptions);

        $this->assertSame($secondaryOptions, ZimsecGradingTemplates::optionsFor($high));
    }

    public function test_zimsec_templates_are_valid_contiguous_bands(): void
    {
        foreach (ZimsecGradingTemplates::TEMPLATES as $key => $template) {
            $this->assertNotEmpty($template['name'], "Template {$key} needs a name");
            $points = $template['points'];
            $this->assertNotEmpty($points, "Template {$key} needs points");

            $symbols = array_column($points, 'symbol');
            $this->assertCount(count($symbols), array_unique($symbols), "Template {$key} has duplicate symbols");

            usort($points, fn ($a, $b) => ((int) $a['min_score']) <=> ((int) $b['min_score']));

            $expectedMin = 0;
            foreach ($points as $i => $point) {
                $min = (int) $point['min_score'];
                $max = (int) $point['max_score'];
                $this->assertTrue($min >= 0 && $max <= 100, "Template {$key} point {$i} outside 0..100");
                $this->assertTrue($min <= $max, "Template {$key} point {$i} has min > max");
                if ($i === 0) {
                    $this->assertSame(0, $min, "Template {$key} first band must start at 0");
                } else {
                    $this->assertSame($expectedMin, $min, "Template {$key} bands must be contiguous");
                }
                $expectedMin = $max + 1;
            }
            $this->assertSame(101, $expectedMin, "Template {$key} bands must reach 100");
        }
    }

    public function test_calculate_subject_final_includes_all_terms_assessment(): void
    {
        $user = User::withoutGlobalScopes()->where('school_id', $this->school->id)->first();
        $this->assertNotNull($user, 'School chiwariraprimary must have a user');

        $year = AcademicYear::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => self::TAG.'Year',
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'is_current' => true,
        ]);

        $term = Term::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => self::TAG.'Term1',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);

        $course = Course::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => self::TAG.'Course',
            'code' => 'ZIMC',
        ]);

        $section = Section::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'course_id' => $course->id,
            'name' => self::TAG.'Section',
            'capacity' => 40,
            'target_size' => 40,
        ]);

        $subject = Subject::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => self::TAG.'Subject',
            'code' => 'ZIMS',
        ]);

        $student = Student::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'first_name' => self::TAG.'Student',
            'last_name' => 'One',
            'gender' => 'Male',
            'date_of_birth' => '2015-01-01',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);

        $enrollment = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'course_id' => $course->id,
            'section_id' => $section->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'effective_date' => '2026-09-01',
        ]);

        $termScoped = AssessmentType::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'term_id' => $term->id,
            'name' => self::TAG.'TermScoped',
            'max_mark' => 100,
            'weight_percentage' => 50,
            'created_by_id' => $user->id,
            'status' => 'marking',
        ]);

        $allTerms = AssessmentType::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'term_id' => null,
            'name' => self::TAG.'AllTerms',
            'max_mark' => 100,
            'weight_percentage' => 50,
            'created_by_id' => $user->id,
            'status' => 'marking',
        ]);

        AssessmentMark::create([
            'school_id' => $this->school->id,
            'enrollment_id' => $enrollment->id,
            'assessment_type_id' => $termScoped->id,
            'subject_id' => $subject->id,
            'marks_obtained' => 60,
        ]);

        AssessmentMark::create([
            'school_id' => $this->school->id,
            'enrollment_id' => $enrollment->id,
            'assessment_type_id' => $allTerms->id,
            'subject_id' => $subject->id,
            'marks_obtained' => 100,
        ]);

        $final = (new AssessmentResultCalculator)->calculateSubjectFinal($enrollment->id, $subject->id, $term->id);

        $this->assertSame(80.0, $final);
    }
}
