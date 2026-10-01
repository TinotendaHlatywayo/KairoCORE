<?php

namespace Tests\Feature;

use App\Filament\App\Widgets\UserRoleStatisticsWidget;
use App\Models\School;
use App\Services\UserRegistrationService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\HR\Models\Employee;
use Tests\TestCase;

class UserRoleStatisticsWidgetTest extends TestCase
{
    private ?School $school = null;

    /** @var array<int, int> */
    private array $createdEmployeeIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->useMysql();

        // A dedicated tenant so the headline counts are fully deterministic.
        $this->school = School::create([
            'name' => 'Widget Stats Test School',
            'subdomain' => 'widget-stats-test',
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->createdEmployeeIds) {
            Employee::whereIn('id', $this->createdEmployeeIds)->forceDelete();
        }

        if ($this->school) {
            $this->school->forceDelete();
        }

        parent::tearDown();
    }

    private function useMysql(): void
    {
        Config::set('app.env', 'local');
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    private function makeEmployee(string $first, string $last, string $role): Employee
    {
        $employee = new Employee;
        $employee->school_id = $this->school->id;
        $employee->first_name = $first;
        $employee->last_name = $last;
        $employee->national_id = '63-'.str_pad((string) count($this->createdEmployeeIds) + 1, 9, '0', STR_PAD_LEFT);
        $employee->gender = 'female';
        $employee->date_of_birth = '1990-01-01';
        $employee->marital_status = 'single';
        $employee->phone_number = '+26370000000';
        $employee->email = strtolower($first).'.'.strtolower($last).'@widget-stats-test.test';
        $employee->physical_address = '1 Test Way';
        $employee->emergency_contact_name = 'Next Of Kin';
        $employee->emergency_contact_phone = '+26370000001';
        $employee->designation = 'Science Teacher';
        $employee->role = $role;
        $employee->employment_type = 'Permanent';
        $employee->date_joined = '2026-09-11';
        $employee->status = 'active';
        $employee->save();

        $this->createdEmployeeIds[] = $employee->id;

        return $employee;
    }

    /**
     * Run the widget's real getStats() and return label => value.
     *
     * @return array<string, int>
     */
    private function stats(): array
    {
        App::instance('current_tenant', $this->school);

        $method = new \ReflectionMethod(UserRoleStatisticsWidget::class, 'getStats');
        $method->setAccessible(true);

        $counts = [];
        foreach ((array) $method->invoke(new UserRoleStatisticsWidget) as $stat) {
            $counts[(string) $stat->getLabel()] = (int) $stat->getValue();
        }

        return $counts;
    }

    public function test_teaching_staff_employee_is_not_counted_as_non_teaching(): void
    {
        $this->makeEmployee('Rumbudzai', 'Mutamba', UserRegistrationService::roleNameForCategory('teaching_staff'));

        $counts = $this->stats();

        $this->assertSame(1, $counts['Teaching Staff']);
        $this->assertSame(0, $counts['Non-Teaching Staff']);
    }

    public function test_supporting_staff_employee_is_counted_as_non_teaching(): void
    {
        $this->makeEmployee('Rumbudzai', 'Mutamba', UserRegistrationService::roleNameForCategory('teaching_staff'));
        $this->makeEmployee('Chipo', 'Dube', UserRegistrationService::roleNameForCategory('supporting_staff'));

        $counts = $this->stats();

        $this->assertSame(1, $counts['Teaching Staff']);
        $this->assertSame(1, $counts['Non-Teaching Staff']);
    }

    public function test_every_employee_role_label_is_classified_exactly_once(): void
    {
        foreach (array_keys(UserRegistrationService::categoryRoleNames()) as $category) {
            if ($category === 'student') {
                continue;
            }

            $this->makeEmployee(
                'Staff'.ucfirst($category),
                'Probe',
                UserRegistrationService::roleNameForCategory($category)
            );
        }

        $counts = $this->stats();

        $teachingLabel = UserRegistrationService::roleNameForCategory('teaching_staff');
        $this->assertSame(1, $counts['Teaching Staff'], "Only the '{$teachingLabel}' employee may be teaching staff.");
        $this->assertSame(
            count(UserRegistrationService::categoryRoleNames()) - 2,
            $counts['Non-Teaching Staff'],
            'Every other employee role label must be counted as non-teaching.'
        );
    }
}
