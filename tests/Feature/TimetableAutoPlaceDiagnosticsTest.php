<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Classroom;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\CourseSubject;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;
use Modules\Timetables\Models\TimetableTemplate;
use Modules\Timetables\Services\TimetableGeneratorService;
use Tests\TestCase;

class TimetableAutoPlaceDiagnosticsTest extends TestCase
{
    private ?School $school = null;

    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'schoolcore']);

        $this->school = School::create([
            'name' => 'TT Diagnostics '.uniqid(),
            'subdomain' => 'tt-diag-'.uniqid(),
            'status' => 'active',
        ]);
        $this->actingAsTenant($this->school);
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    private function buildFixture(): array
    {
        $sid = $this->school->id;

        $course = Course::create(['school_id' => $sid, 'name' => 'Form 1', 'code' => 'F1', 'level' => 'secondary']);
        Section::create(['school_id' => $sid, 'course_id' => $course->id, 'name' => 'A']);
        Section::create(['school_id' => $sid, 'course_id' => $course->id, 'name' => 'B']);
        $subject = Subject::create(['school_id' => $sid, 'name' => 'Maths', 'code' => 'MAT']);
        Classroom::create(['school_id' => $sid, 'name' => 'Room 101']);

        $year = AcademicYear::create(['school_id' => $sid, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
        $term = Term::create(['school_id' => $sid, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31']);

        $teacher = User::create(['school_id' => $sid, 'name' => 'TT Teacher', 'email' => uniqid().'@tt-diag.test', 'password' => bcrypt('secret'), 'account_status' => 'active']);

        $template = TimetableTemplate::create(['school_id' => $sid, 'name' => 'Diag Template', 'is_active' => true, 'settings' => []]);
        $generator = app(TimetableGeneratorService::class);
        $generator->generate([
            'start_time' => '08:00:00',
            'end_time_of_lessons' => '12:00:00',
            'period_length' => 45,
            'has_fixed_break' => false,
            'has_fixed_lunch' => false,
        ], $template->id);

        return compact('course', 'subject', 'year', 'term', 'teacher', 'template', 'generator');
    }

    public function test_zero_placements_reports_missing_teacher_assignments(): void
    {
        $fixture = $this->buildFixture();

        $result = $fixture['generator']->autoPlaceLessons([
            'template_id' => $fixture['template']->id,
            'academic_year_id' => $fixture['year']->id,
            'term_id' => $fixture['term']->id,
        ]);

        $this->assertSame(0, $result['placed']);
        $this->assertCount(0, $result['unplaced']);
        $this->assertNotEmpty($result['skipped'], 'no teacher assignments must be explained via skipped reasons');
        $this->assertStringContainsString(
            'No teacher assignments found',
            implode(' ', $result['skipped']),
            'a tenant with no assignments must not be told it succeeded silently'
        );
    }

    public function test_teacher_assignment_places_a_lesson_per_section(): void
    {
        $fixture = $this->buildFixture();

        CourseSubject::create([
            'school_id' => $this->school->id,
            'course_id' => $fixture['course']->id,
            'subject_id' => $fixture['subject']->id,
            'section_id' => null,
            'teacher_id' => $fixture['teacher']->id,
            'role' => 'main',
            'periods_per_week' => 4,
        ]);

        $result = $fixture['generator']->autoPlaceLessons([
            'template_id' => $fixture['template']->id,
            'academic_year_id' => $fixture['year']->id,
            'term_id' => $fixture['term']->id,
        ]);

        $sectionCount = Section::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->where('course_id', $fixture['course']->id)
            ->count();

        $this->assertSame($sectionCount * 4, $result['placed'], 'course-level assignment must fan out to every stream');
        $this->assertCount(0, $result['unplaced']);
        $this->assertCount(0, $result['skipped']);
    }

    private function cleanup(): void
    {
        if (! $this->school) {
            return;
        }

        $sid = $this->school->id;

        foreach ([
            'timetable_lessons',
            'time_slots',
            'timetable_templates',
            'course_subject',
            'sections',
            'classrooms',
            'courses',
            'subjects',
            'terms',
            'academic_years',
        ] as $table) {
            DB::table($table)->where('school_id', $sid)->delete();
        }

        DB::table('users')->where('school_id', $sid)->delete();
        DB::table('schools')->where('id', $sid)->delete();

        $this->school = null;
    }
}