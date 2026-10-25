<?php

namespace Tests\Feature;

use App\Models\School;
use App\Services\Csv\DepartmentCsvService;
use App\Services\Csv\EmployeeCsvService;
use App\Services\Csv\FeeStructureCsvService;
use App\Services\Csv\FixedAssetCsvService;
use App\Services\Csv\InventoryItemCsvService;
use App\Services\Csv\ProcurementRequestCsvService;
use App\Services\Csv\SalaryGradeCsvService;
use App\Services\StudentCsvService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\HR\Models\SalaryGrade;
use Tests\TestCase;

/**
 * Every Excel template we hand to an administrator has to import into a real
 * tenant straight out of the box — the example rows must use references that
 * actually exist (salary grades, employees, courses, academic years) and must
 * never repeat a value that a unique index would reject.
 *
 * The import runs inside a transaction that is always rolled back, so the
 * fixture school is never touched.
 */
class CsvTemplateImportRegressionTest extends TestCase
{
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.env', 'local');
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');

        Config::set('mail.default', 'array');
        Config::set('queue.default', 'sync');

        $this->school = School::where('subdomain', 'tinwayacademy')->first()
            ?? School::where('institution_type', 'primary')->first()
            ?? School::firstOrFail();

        $this->actingAsTenant($this->school);
    }

    /**
     * Write the service's own template to disk, work out the column mapping
     * exactly the way the import wizard does, run the import, then roll it
     * all back.
     *
     * @return array{success: int, total: int, failures: array<int, array{row: int, errors: array<int, string>}>}
     */
    private function importOwnTemplate(string $service): array
    {
        $relative = 'csv-imports/test-'.strtolower((new \ReflectionClass($service))->getShortName()).'-'.uniqid().'.xlsx';
        $absolute = storage_path('app/public/'.$relative);

        File::ensureDirectoryExists(dirname($absolute));
        File::put($absolute, $service::templateXlsx());

        try {
            $filePath = $service::resolveTempFilePath($relative);
            $mapping = $service::guessMapping($service::readCsvHeaders($filePath));

            DB::beginTransaction();

            try {
                $result = $service::import($filePath, $this->school->id, $mapping, null);
            } finally {
                DB::rollBack();
            }

            return $result;
        } finally {
            File::delete($absolute);
        }
    }

    /**
     * @return array<int, string>
     */
    private function failureMessages(array $result): array
    {
        $messages = [];

        foreach ($result['failures'] as $failure) {
            $messages[] = 'row '.$failure['row'].': '.implode(' | ', array_slice($failure['errors'], 0, 2));
        }

        return $messages;
    }

    /**
     * @return array<string, int>  service class => rows the template holds
     */
    private function templates(): array
    {
        return [
            EmployeeCsvService::class => 5,
            StudentCsvService::class => 1,
            DepartmentCsvService::class => 5,
            SalaryGradeCsvService::class => 5,
            FeeStructureCsvService::class => 5,
            InventoryItemCsvService::class => 5,
            FixedAssetCsvService::class => 5,
            ProcurementRequestCsvService::class => 5,
        ];
    }

    public function test_every_template_imports_into_a_real_tenant(): void
    {
        foreach ($this->templates() as $service => $expectedRows) {
            $result = $this->importOwnTemplate($service);

            $this->assertSame(
                $expectedRows,
                $result['total'],
                class_basename($service).' must generate '.$expectedRows.' example rows.',
            );

            $this->assertSame(
                [],
                $result['failures'],
                class_basename($service).' template rows were rejected: '.implode(', ', $this->failureMessages($result)),
            );

            $this->assertSame(
                $result['total'],
                $result['success'],
                class_basename($service).' imported only '.$result['success'].' of '.$result['total'].' rows.',
            );
        }
    }

    public function test_employee_template_uses_distinct_people_and_a_grade_that_exists(): void
    {
        [$header, $rows] = $this->employeeTemplate();

        $this->assertCount(5, $rows);

        $nameIndex = array_search('First Name', $header, true);
        $surnameIndex = array_search('Last Name', $header, true);
        $emailIndex = array_search('Email', $header, true);

        $this->assertNotFalse($nameIndex, 'The employee template must carry a first name column.');
        $this->assertNotFalse($surnameIndex, 'The employee template must carry a last name column.');
        $this->assertNotFalse($emailIndex, 'The employee template must carry an email column.');

        $emails = array_map(fn (array $row): string => mb_strtolower(trim((string) $row[$emailIndex])), $rows);
        $this->assertCount(5, array_unique($emails), 'Employee example rows must not repeat an email address.');

        $names = array_map(
            fn (array $row): string => trim($row[$nameIndex].' '.$row[$surnameIndex]),
            $rows,
        );
        $this->assertCount(5, array_unique($names), 'Employee example rows must not repeat a person.');

        // A salary grade that does not exist is the exact failure that used to
        // reject every row of this template.
        $gradeIndex = array_search('Salary Grade Name', $header, true);
        $this->assertNotFalse($gradeIndex, 'The employee template must carry a salary grade column.');

        $gradeNames = array_values(array_unique(array_filter(array_map(
            fn (array $row): string => trim((string) $row[$gradeIndex]),
            $rows,
        ))));

        if ($gradeNames === []) {
            return;
        }

        $existing = SalaryGrade::query()
            ->where('school_id', $this->school->id)
            ->pluck('name')
            ->map(fn ($name): string => mb_strtolower(trim((string) $name)))
            ->all();

        foreach ($gradeNames as $grade) {
            $this->assertContains(
                mb_strtolower($grade),
                $existing,
                "The employee template asks for salary grade [$grade], which does not exist in this school.",
            );
        }
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, array<int, string>>}
     */
    private function employeeTemplate(): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', EmployeeCsvService::templateCsv());
        $lines = array_map(
            fn (string $line): array => str_getcsv($line, ',', '"', '\\'),
            explode("\n", trim($csv)),
        );

        $header = array_shift($lines);

        return [$header, array_values(array_filter($lines, fn (array $row): bool => implode('', $row) !== ''))];
    }
}
