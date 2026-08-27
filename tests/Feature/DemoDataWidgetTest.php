<?php

namespace Tests\Feature;

use App\Filament\App\Widgets\DemoDataWidget;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\URL;
use Modules\Academics\Models\AcademicReport;
use Modules\Students\Models\Student;

class DemoDataWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'schoolcore']);

        $school = School::find(15);
        app()->instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);
        $this->withSession(['locale' => 'en']);
    }

    private function widget(): DemoDataWidget
    {
        $this->actingAs(User::find(13));

        return new DemoDataWidget;
    }

    public function test_demo_data_widget_seeds_and_wipes(): void
    {
        $widget = $this->widget();

        // Clean slate
        $widget->wipe();
        $this->assertFalse($widget->hasDemoData);

        // Seed through the widget action
        $widget->seed();

        $students = Student::withoutGlobalScopes()
            ->where('school_id', 15)
            ->where('student_id_number', 'LIKE', 'TEST-STU-%')
            ->get();

        $this->assertTrue($students->isNotEmpty(), 'Seeding should create TEST-STU students');
        $this->assertTrue(
            AcademicReport::withoutGlobalScopes()->whereIn('student_id', $students->pluck('id'))->exists(),
            'Seeding should create academic reports'
        );
        $this->assertTrue((new DemoDataWidget)->hasDemoData);

        // Wipe through the widget action
        $widget->wipe();

        $this->assertFalse(Student::withoutGlobalScopes()
            ->where('school_id', 15)
            ->where('student_id_number', 'LIKE', 'TEST-STU-%')
            ->exists(), 'Wiping should remove all TEST-STU students');

        $this->assertFalse((new DemoDataWidget)->hasDemoData);
    }
}
