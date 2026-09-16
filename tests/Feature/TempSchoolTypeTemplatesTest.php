<?php

namespace Tests\Feature;

use App\Models\School;
use App\Services\Csv\AcademicYearCsvService;
use App\Services\Csv\ClassroomCsvService;
use App\Services\Csv\CourseCsvService;
use App\Services\Csv\SubjectCsvService;
use Tests\TestCase;

class TempSchoolTypeTemplatesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', 'schoolcore');
        config()->set('database.connections.mysql.host', '127.0.0.1');
        config()->set('database.connections.mysql.port', '3306');
        config()->set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        config()->set('database.connections.mysql.password', env('DB_PASSWORD', ''));
    }

    private function parseTemplateRows(string $csv): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);
        $rows = array_map(
            fn (string $line): array => str_getcsv($line, ',', '"', '\\'),
            explode("\n", trim($csv)),
        );

        array_shift($rows); // header row

        return array_values(array_filter($rows, fn (array $row): bool => implode('', $row) !== ''));
    }

    private function schoolOfType(string $type): School
    {
        return School::where('institution_type', $type)->withTrashed()->first() ?? School::withTrashed()->first();
    }

    public function test_primary_course_template_has_ecd_and_grade_streams(): void
    {
        $school = $this->schoolOfType('primary');
        app()->instance('current_tenant', $school);

        $rows = $this->parseTemplateRows(CourseCsvService::templateCsv());

        $this->assertNotEmpty($rows);
        $this->assertContains('ECD A', array_column($rows, 0));
        $this->assertContains('ECD B', array_column($rows, 0));
        $this->assertContains('Grade 7', array_column($rows, 0));
        $this->assertContains('Blue', array_column($rows, 2));
        $this->assertContains('Red', array_column($rows, 2));
        $this->assertContains('North', array_column($rows, 2));
        $this->assertContains('South', array_column($rows, 2));
        $this->assertNotContains('Form 1', array_column($rows, 0));
    }

    public function test_secondary_course_template_has_form_streams(): void
    {
        $school = $this->schoolOfType('secondary');
        app()->instance('current_tenant', $school);

        $rows = $this->parseTemplateRows(CourseCsvService::templateCsv());

        $this->assertNotEmpty($rows);
        $this->assertContains('Form 1', array_column($rows, 0));
        $this->assertContains('Form 4', array_column($rows, 0));
        $this->assertContains('Form 5', array_column($rows, 0));
        $this->assertContains('Form 6', array_column($rows, 0));
        $this->assertContains('Arts', array_column($rows, 2));
        $this->assertContains('Commercials', array_column($rows, 2));
        $this->assertContains('Sciences', array_column($rows, 2));
        $this->assertNotContains('Grade 1', array_column($rows, 0));
    }

    public function test_primary_subject_template_has_new_primary_names(): void
    {
        $school = $this->schoolOfType('primary');
        app()->instance('current_tenant', $school);

        $rows = $this->parseTemplateRows(SubjectCsvService::templateCsv());
        $names = array_column($rows, 0);

        foreach ([
            'Mathematics',
            'English Language',
            'Shona Language',
            'Agriculture, Science and Technology and ICT',
            'Social Sciences',
            'Physical Education and Arts',
        ] as $expected) {
            $this->assertContains($expected, $names);
        }
    }

    public function test_secondary_subject_template_has_full_secondary_curriculum(): void
    {
        $school = $this->schoolOfType('secondary');
        app()->instance('current_tenant', $school);

        $rows = $this->parseTemplateRows(SubjectCsvService::templateCsv());
        $names = array_column($rows, 0);

        foreach ([
            'MATHEMATICS',
            'ENGLISH LANGUAGE',
            'SHONA LANGUAGE',
            'COMBINED SCIENCE',
            'GEOGRAPHY',
            'PHYSICS',
            'CHEMISTRY',
            'BIOLOGY',
            'HISTORY',
            'PE SPORTS AND MASS DISPLAYS',
            'BUILDING TECHNOLOGY AND DESIGN',
            'AGRICULTURE',
            'HERITAGE',
            'COMPUTER SCIENCE',
            'FASHION AND FABRICS',
            'FOOD AND NUTRITION',
            'METALWORK',
            'WOODWORK',
        ] as $expected) {
            $this->assertContains($expected, $names);
        }
    }

    public function test_classroom_templates_match_level_count(): void
    {
        $primary = $this->schoolOfType('primary');
        app()->instance('current_tenant', $primary);
        $primaryRows = $this->parseTemplateRows(ClassroomCsvService::templateCsv());

        $secondary = $this->schoolOfType('secondary');
        app()->instance('current_tenant', $secondary);
        $secondaryRows = $this->parseTemplateRows(ClassroomCsvService::templateCsv());

        $this->assertNotEmpty($primaryRows);
        $this->assertNotEmpty($secondaryRows);

        $primaryNames = array_column($primaryRows, 0);
        $secondaryNames = array_column($secondaryRows, 0);

        $this->assertContains('ECD A Blue', $primaryNames);
        $this->assertContains('Grade 7 South', $primaryNames);
        $this->assertNotContains('Form 1 North', $primaryNames);

        $this->assertContains('Form 1 North', $secondaryNames);
        $this->assertContains('Form 6 Sciences', $secondaryNames);
        $this->assertNotContains('ECD A Blue', $secondaryNames);
    }

    public function test_academic_year_template_has_ten_years_with_2026_active(): void
    {
        $school = School::first();
        app()->instance('current_tenant', $school);

        $rows = $this->parseTemplateRows(AcademicYearCsvService::templateCsv());

        $this->assertCount(10, $rows);
        $this->assertContains('2026', array_column($rows, 0));
        $this->assertContains('2035', array_column($rows, 0));

        // Column order: name, start, end, t1 start, t1 end, t2 start, t2 end,
        // t3 start, t3 end, is_active (index 9).
        $this->assertSame(10, count($rows[0]));

        $active = array_filter($rows, fn (array $row): bool => strtolower($row[9]) === 'yes');
        $this->assertCount(1, $active);
        $this->assertSame('2026', array_values($active)[0][0]);

        // Each year carries three terms with realistic dates, and the term
        // periods never overlap or run outside the year's own window.
        $year2026 = array_values(array_filter($rows, fn (array $row): bool => $row[0] === '2026'))[0];

        $this->assertSame('2026-01-05', $year2026[3]);
        $this->assertSame('2026-03-28', $year2026[4]);
        $this->assertSame('2026-05-09', $year2026[5]);
        $this->assertSame('2026-08-01', $year2026[6]);
        $this->assertSame('2026-09-07', $year2026[7]);
        $this->assertSame('2026-12-03', $year2026[8]);

        foreach ($rows as $row) {
            $this->assertGreaterThanOrEqual($row[1], $row[3], 'term 1 start is within the year');
            $this->assertLessThanOrEqual($row[2], $row[8], 'term 3 end is within the year');
            $this->assertGreaterThanOrEqual($row[4], $row[5], 'term 2 starts after term 1 ends');
            $this->assertGreaterThanOrEqual($row[6], $row[7], 'term 3 starts after term 2 ends');
        }
    }
}
