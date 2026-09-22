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

    public function test_zimsec_primary_template_uses_unit_scale_without_letter_grades(): void
    {
        $template = ZimsecGradingTemplates::template('zimsec_grade7');
        $this->assertNotNull($template);

        $points = $template['points'];
        $this->assertCount(9, $points);

        $expected = [
            ['symbol' => '1', 'min' => 90, 'max' => 100, 'remark' => 'Excellent / Distinction'],
            ['symbol' => '2', 'min' => 80, 'max' => 89, 'remark' => 'Superior'],
            ['symbol' => '3', 'min' => 70, 'max' => 79, 'remark' => 'Very Good'],
            ['symbol' => '4', 'min' => 60, 'max' => 69, 'remark' => 'Good'],
            ['symbol' => '5', 'min' => 50, 'max' => 59, 'remark' => 'Credit / Competent'],
            ['symbol' => '6', 'min' => 40, 'max' => 49, 'remark' => 'Satisfactory Pass'],
            ['symbol' => '7', 'min' => 30, 'max' => 39, 'remark' => 'Low Pass'],
            ['symbol' => '8', 'min' => 20, 'max' => 29, 'remark' => 'Marginal Pass'],
            ['symbol' => '9', 'min' => 0, 'max' => 19, 'remark' => 'Unsatisfactory / Fail'],
        ];

        foreach ($expected as $i => $band) {
            $this->assertSame($band['symbol'], $points[$i]['symbol'], "Band {$i} symbol");
            $this->assertSame($band['min'], (int) $points[$i]['min_score'], "Band {$i} min score");
            $this->assertSame($band['max'], (int) $points[$i]['max_score'], "Band {$i} max score");
            $this->assertSame($band['remark'], $points[$i]['remark'], "Band {$i} remark");
        }

        // Primary reporting uses the 1-9 unit scale, never letter grades.
        $symbols = array_column($points, 'symbol');
        $this->assertSame(range('1', '9'), $symbols, 'Primary template must use unit numbers only');
        $this->assertTrue(
            collect($symbols)->every(fn (string $symbol): bool => is_numeric($symbol)),
            'Primary template must not introduce letter grade symbols'
        );
    }

    public function test_calculate_subject_final_includes_all_terms_assessment(): void
    {
        $fixtures = $this->makeFixtures();

        $termScoped = $this->makeType('AllTermsMix_TermScoped', $fixtures->term->id, 50, 60, $fixtures);
        $allTerms = $this->makeType('AllTermsMix_AllTerms', null, 50, 100, $fixtures);

        $final = (new AssessmentResultCalculator)->calculateSubjectFinal(
            $fixtures->enrollment->id,
            $fixtures->subject->id,
            $fixtures->term->id
        );

        $this->assertSame(80.0, $final);
        $this->assertNotNull($termScoped->id);
        $this->assertNotNull($allTerms->id);
    }

    public function test_final_grade_comes_from_exam_only_when_tests_have_zero_weight(): void
    {
        $fixtures = $this->makeFixtures();

        // Convention from the import template: Test 1 and Test 2 carry 0% weight,
        // so the Exam alone (100%) determines the final grade.
        $this->makeType('ZeroWeight_Test1', null, 0, 40, $fixtures);
        $this->makeType('ZeroWeight_Test2', null, 0, 50, $fixtures);
        $this->makeType('ZeroWeight_Exam', null, 100, 90, $fixtures);

        $final = (new AssessmentResultCalculator)->calculateSubjectFinal(
            $fixtures->enrollment->id,
            $fixtures->subject->id,
            $fixtures->term->id
        );

        $this->assertSame(90.0, $final);
    }

    private function makeFixtures(): object
    {
        $user = User::withoutGlobalScopes()->where('school_id', $this->school->id)->first();
        $this->assertNotNull($user, 'School chiwariraprimary must have a user');

        $year = AcademicYear::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => self::TAG.'Year_'.uniqid('', true),
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'is_current' => true,
        ]);

        $term = Term::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => self::TAG.'Term_'.uniqid('', true),
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);

        $course = Course::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => self::TAG.'Course_'.uniqid('', true),
            'code' => 'ZIMC'.substr(uniqid('', true), -4),
        ]);

        $section = Section::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'course_id' => $course->id,
            'name' => self::TAG.'Section_'.uniqid('', true),
            'capacity' => 40,
            'target_size' => 40,
        ]);

        $subject = Subject::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => self::TAG.'Subject_'.uniqid('', true),
            'code' => 'ZIMS'.substr(uniqid('', true), -4),
        ]);

        $student = Student::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'first_name' => self::TAG.'Student_'.uniqid('', true),
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

        return (object) compact('user', 'year', 'term', 'course', 'section', 'subject', 'student', 'enrollment');
    }

    private function makeType(string $name, ?int $termId, float $weight, float $mark, object $fixtures): AssessmentType
    {
        $type = AssessmentType::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'term_id' => $termId,
            'name' => self::TAG.$name,
            'max_mark' => 100,
            'weight_percentage' => $weight,
            'created_by_id' => $fixtures->user->id,
            'status' => 'marking',
        ]);

        AssessmentMark::create([
            'school_id' => $this->school->id,
            'enrollment_id' => $fixtures->enrollment->id,
            'assessment_type_id' => $type->id,
            'subject_id' => $fixtures->subject->id,
            'marks_obtained' => $mark,
        ]);

        return $type;
    }
}
