<?php

namespace Tests\Feature;

use App\Filament\App\Widgets\DemoDataWidget;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Modules\Academics\Models\AcademicReport;
use Modules\Students\Models\Student;

class DemoDataWidgetTest extends TestCase
{
    protected int $schoolId;

    protected School $school;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'schoolcore']);

        // The widget seeds and wipes the school it is signed in to, so the test
        // uses a school of its own rather than a live tenant.
        $suffix = substr(uniqid(), -8);

        $this->school = School::create([
            'name' => 'Demo Widget School '.$suffix,
            'subdomain' => 'demo-widget-'.$suffix,
            'status' => 'active',
        ]);

        $this->schoolId = (int) $this->school->id;

        app()->instance('current_tenant', $this->school);
        URL::defaults(['tenant' => $this->school->subdomain]);
        $this->withSession(['locale' => 'en']);
    }

    protected function tearDown(): void
    {
        // The school goes first: deleting it writes an audit log that still
        // points at the acting administrator, so that account has to exist
        // while it happens.
        $this->school?->delete();

        if (isset($this->schoolId)) {
            DB::table('users')->where('school_id', $this->schoolId)->delete();
        }

        parent::tearDown();
    }

    private function widget(): DemoDataWidget
    {
        $this->actingAs(
            User::create([
                'school_id' => $this->schoolId,
                'name' => 'Demo Widget Administrator',
                'email' => 'demo-widget-admin-'.substr(uniqid(), -8).'@example.test',
                'username' => 'demo-widget-admin-'.substr(uniqid(), -8),
                'password' => 'Password@12345',
                'account_status' => User::STATUS_ACTIVE,
                'requested_role' => 'administrator',
            ])
        );

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
            ->where('school_id', $this->schoolId)
            ->where('student_id_number', 'LIKE', 'TEST-STU-%')
            ->get();

        $this->assertTrue($students->isNotEmpty(), 'Seeding should create TEST-STU students');
        $this->assertTrue(
            AcademicReport::withoutGlobalScopes()->whereIn('student_id', $students->pluck('id'))->exists(),
            'Seeding should create academic reports'
        );
        $this->assertTrue((new DemoDataWidget)->hasDemoData);

        // Every demo employee is created with a linked portal login.
        $this->assertGreaterThan(
            0,
            $this->demoStaffAccounts()->count(),
            'Seeding should create the staff portal accounts'
        );

        // Wipe through the widget action
        $widget->wipe();

        $this->assertFalse(Student::withoutGlobalScopes()
            ->where('school_id', $this->schoolId)
            ->where('student_id_number', 'LIKE', 'TEST-STU-%')
            ->exists(), 'Wiping should remove all TEST-STU students');

        $this->assertFalse((new DemoDataWidget)->hasDemoData);

        // The staff logins are linked to their employee row, and the wipe
        // removes the employees with a query builder — which fires no model
        // events — so the logins have to be removed explicitly. Otherwise they
        // stay behind as orphaned accounts on the User Accounts page.
        $this->assertSame(
            0,
            $this->demoStaffAccounts()->count(),
            'Wiping should remove the demo staff portal accounts'
        );
    }

    /**
     * The portal logins that belong to the demo staff.
     */
    private function demoStaffAccounts()
    {
        return User::withoutGlobalScopes()
            ->where('school_id', $this->schoolId)
            ->where('email', 'LIKE', '%@demo.schoolcore.test')
            ->get();
    }
}
