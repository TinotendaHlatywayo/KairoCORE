<?php

namespace Tests\Feature;

use App\Filament\App\Resources\TimetableLessonResource\Pages\ListTimetableLessons;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Classroom;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\CourseSubject;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;
use Modules\Timetables\Models\TimeSlot;
use Modules\Timetables\Models\TimetableLesson;
use Modules\Timetables\Models\TimetableTemplate;
use Modules\Timetables\Services\TimetableGeneratorService;
use Tests\TestCase as BaseTestCase;

class TempTimetableCustomizationTest extends BaseTestCase
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

    public function test_pinned_classroom_is_exclusive_and_swap_exchanges_slots(): void
    {
        $sid = $this->schoolId;

        $teacherId = (int) CourseSubject::where('school_id', $sid)->whereNotNull('teacher_id')->value('teacher_id');
        $this->assertGreaterThan(0, $teacherId, 'expected an existing teacher for the fixture');

        $subjectId = (int) Subject::where('school_id', $sid)->value('id');
        $year = AcademicYear::where('school_id', $sid)->firstOrFail();
        $term = Term::where('school_id', $sid)->firstOrFail();

        $classroom = Classroom::create(['school_id' => $sid, 'name' => 'QA Pinned Room']);
        $course = Course::create(['school_id' => $sid, 'name' => 'QA Pinned Course', 'code' => 'QA-PIN']);
        $section = Section::create([
            'school_id' => $sid,
            'course_id' => $course->id,
            'name' => 'QA Stream A',
            'code' => 'QA-A',
            'classroom_id' => $classroom->id,
        ]);

        // The generator auto-places every teacher-assigned course_subject, so
        // suspend all pre-existing assignments and keep only our QA one. This
        // makes the generated template deterministic and touch-free vs demo data.
        $existing = DB::table('course_subject')
            ->where('school_id', $sid)
            ->whereNotNull('teacher_id')
            ->pluck('teacher_id', 'id')
            ->all();

        $assignment = null;

        try {
            DB::table('course_subject')
                ->where('school_id', $sid)
                ->whereNotNull('teacher_id')
                ->update(['teacher_id' => null]);

            $assignment = CourseSubject::create([
                'school_id' => $sid,
                'course_id' => $course->id,
                'section_id' => $section->id,
                'subject_id' => $subjectId,
                'teacher_id' => $teacherId,
                'role' => 'main',
                'periods_per_week' => 2,
            ]);

            $template = TimetableTemplate::create([
                'school_id' => $sid,
                'name' => 'QA Pinned Template',
                'is_active' => false,
                'settings' => ['start_time' => '08:00:00', 'end_time_of_lessons' => '12:00:00', 'period_length' => 45],
            ]);

            $generator = app(TimetableGeneratorService::class);
            $generator->generate([
                'start_time' => '08:00:00',
                'end_time_of_lessons' => '12:00:00',
                'period_length' => 45,
                'has_fixed_break' => false,
                'has_fixed_lunch' => false,
            ], $template->id);

            $result = $generator->autoPlaceLessons([
                'template_id' => $template->id,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
            ]);

            $this->assertSame(0, count($result['unplaced']), 'QA assignment must fully place: '.json_encode($result['unplaced']));

            $lessons = TimetableLesson::where('template_id', $template->id)->get();
            $this->assertCount(2, $lessons, 'exactly two periods generated for the pinned class');

            foreach ($lessons as $lesson) {
                $this->assertSame($section->id, (int) $lesson->section_id, 'every lesson belongs to the pinned section');
                $this->assertSame((int) $classroom->id, (int) $lesson->classroom_id, 'pinned section must always use its fixed classroom');
            }

            $this->assertSame(2, TimetableLesson::where('classroom_id', $classroom->id)->count(), 'the pinned classroom must never leak to another class');
            $this->assertCount(2, $lessons->pluck('day_of_week')->unique(), 'the two periods must land on different days (day-spread)');

            // --- Drag & drop swap logic --------------------------------------------------
            $page = new ListTimetableLessons();
            $a = $lessons->get(0);
            $b = $lessons->get(1);
            $origA = [$a->time_slot_id, $a->day_of_week];
            $origB = [$b->time_slot_id, $b->day_of_week];

            $page->swapLesson($a->id, (int) $b->time_slot_id, $b->day_of_week);

            $a->refresh();
            $b->refresh();
            $this->assertSame($origB[0], (int) $a->time_slot_id, 'lesson A should now occupy lesson B cell');
            $this->assertSame($origB[1], $a->day_of_week, 'lesson A should now be on lesson B day');
            $this->assertSame($origA[0], (int) $b->time_slot_id, 'lesson B should now occupy lesson A cell');
            $this->assertSame($origA[1], $b->day_of_week, 'lesson B should now be on lesson A day');

            // Invalid day / unknown lesson must be a no-op.
            $before = TimetableLesson::where('template_id', $template->id)->get()->map(fn ($l) => [$l->id, $l->day_of_week])->all();
            $page->swapLesson($a->id, (int) $b->time_slot_id, 'sunday');
            $page->swapLesson(99999999, (int) $b->time_slot_id, 'monday');
            $after = TimetableLesson::where('template_id', $template->id)->get()->map(fn ($l) => [$l->id, $l->day_of_week])->all();
            $this->assertSame($before, $after, 'invalid swaps must not mutate the schedule');
        } finally {
            if ($assignment) {
                $assignment->delete();
            }

            TimetableLesson::where('template_id', $template->id ?? 0)->delete();
            TimeSlot::where('template_id', $template->id ?? 0)->delete();
            TimetableTemplate::where('id', $template->id ?? 0)->delete();

            Section::where('id', $section->id)->delete();
            Course::where('id', $course->id)->delete();
            Classroom::where('id', $classroom->id)->delete();

            foreach ($existing as $csId => $teacherId) {
                DB::table('course_subject')->where('id', $csId)->whereNull('teacher_id')->update(['teacher_id' => $teacherId]);
            }
        }
    }
}