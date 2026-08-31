<?php

namespace Tests\Feature;

use App\Filament\App\Resources\HostelAttendanceResource\Pages\CreateHostelAttendance;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Hostels\Models\Hostel;
use Modules\Hostels\Models\HostelAttendance;
use Modules\Hostels\Models\HostelAttendanceStudent;
use Modules\Students\Models\Student;

class HostelAttendanceRollCallTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'schoolcore']);

        $school = School::find(5);
        app()->instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);
        $this->withSession(['locale' => 'en']);
    }

    public function test_create_page_renders_with_roll_call_section(): void
    {
        $user = User::find(15);
        $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'tinwayacademy.lvh.me:8000']);

        $r = $this->get('/workspace/hostel-attendances/create');
        $r->assertOk();
        $html = $r->getContent();
        $this->assertStringContainsString('Roll Call', $html);
        $this->assertStringContainsString('Load learners from selected hostel', $html);
    }

    public function test_save_creates_attendance_and_student_rows_with_statuses(): void
    {
        $user = User::find(15);
        $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'tinwayacademy.lvh.me:8000']);

        $hostel = Hostel::create([
            'name' => 'Roll Call Smoke '.uniqid(),
            'type' => 'boys',
            'status' => 'active',
        ]);

        $s1 = Student::create([
            'student_id_number' => 'RC-S1-'.uniqid(),
            'admission_number' => 'RC-ADM1-'.uniqid(),
            'first_name' => 'Roll',
            'last_name' => 'Present',
            'gender' => 'male',
            'date_of_birth' => now()->subYears(14)->toDateString(),
            'admission_date' => now()->startOfYear()->toDateString(),
            'status' => 'active',
        ]);
        $s2 = Student::create([
            'student_id_number' => 'RC-S2-'.uniqid(),
            'admission_number' => 'RC-ADM2-'.uniqid(),
            'first_name' => 'Roll',
            'last_name' => 'Absent',
            'gender' => 'male',
            'date_of_birth' => now()->subYears(14)->toDateString(),
            'admission_date' => now()->startOfYear()->toDateString(),
            'status' => 'active',
        ]);

        try {
            Livewire::test(CreateHostelAttendance::class)
                ->set('data.hostel_id', $hostel->id)
                ->set('data.date', now()->toDateString())
                ->set('data.type', 'evening')
                ->set('data.learners', [
                    ['student_id' => $s1->id, 'is_present' => true, 'remarks' => ''],
                    ['student_id' => $s2->id, 'is_present' => false, 'remarks' => 'Sick'],
                ])
                ->call('create')
                ->assertHasNoFormErrors();

            $attendance = HostelAttendance::where('hostel_id', $hostel->id)->latest('id')->first();
            $this->assertNotNull($attendance);
            $this->assertSame('evening', $attendance->type);
            $this->assertSame($user->id, $attendance->recorded_by_user_id);

            $rows = HostelAttendanceStudent::where('hostel_attendance_id', $attendance->id)->get();
            $this->assertCount(2, $rows);

            $byStudent = $rows->keyBy('student_id');
            $this->assertSame('present', $byStudent[$s1->id]->status);
            $this->assertSame('absent', $byStudent[$s2->id]->status);
            $this->assertSame('Sick', $byStudent[$s2->id]->remarks);
        } finally {
            HostelAttendanceStudent::whereIn('student_id', [$s1->id, $s2->id])->delete();
            HostelAttendance::where('hostel_id', $hostel->id)->delete();
            $s1->forceDelete();
            $s2->forceDelete();
            $hostel->forceDelete();
        }
    }
}
