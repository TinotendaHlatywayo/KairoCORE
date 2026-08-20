<?php

namespace Modules\HR\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\HR\Models\Employee;
use Modules\HR\Models\SalaryGrade;

class ImportEmployeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $filePath;

    protected int $schoolId;

    protected array $mappedKeys;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, int $schoolId, array $mappedKeys)
    {
        $this->filePath = $filePath;
        $this->schoolId = $schoolId;
        $this->mappedKeys = $mappedKeys;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! file_exists($this->filePath)) {
            return;
        }

        $handle = fopen($this->filePath, 'r');
        $headers = fgetcsv($handle); // Skip headers

        $importedCount = 0;
        $duplicateCount = 0;

        DB::transaction(function () use ($handle, &$importedCount, &$duplicateCount) {
            while (($row = fgetcsv($handle)) !== false) {
                $rowValues = [];
                foreach ($this->mappedKeys as $key => $index) {
                    $rowValues[$key] = $row[$index] ?? null;
                }

                // Skip duplicates
                $emailExists = Employee::withTrashed()->where('school_id', $this->schoolId)->where('email', $rowValues['email'])->exists()
                    || User::where('school_id', $this->schoolId)->where('email', $rowValues['email'])->exists();

                if (empty($rowValues['email']) || $emailExists) {
                    $duplicateCount++;

                    continue;
                }
                if (empty($rowValues['national_id']) || Employee::withTrashed()->where('school_id', $this->schoolId)->where('national_id', $rowValues['national_id'])->exists()) {
                    $duplicateCount++;

                    continue;
                }

                $grade = SalaryGrade::where('school_id', $this->schoolId)->where('name', $rowValues['salary_grade'])->first();
                if (! $grade) {
                    $grade = SalaryGrade::where('school_id', $this->schoolId)->first();
                }

                if (! $grade) {
                    $duplicateCount++;

                    continue;
                }

                $passwordRaw = Str::random(10);
                $user = User::create([
                    'school_id' => $this->schoolId,
                    'name' => "{$rowValues['first_name']} {$rowValues['last_name']}",
                    'email' => $rowValues['email'],
                    'password' => Hash::make($passwordRaw),
                ]);

                Employee::create([
                    'school_id' => $this->schoolId,
                    'user_id' => $user->id,
                    'first_name' => $rowValues['first_name'],
                    'last_name' => $rowValues['last_name'],
                    'email' => $rowValues['email'],
                    'phone_number' => $rowValues['phone'],
                    'national_id' => $rowValues['national_id'],
                    'gender' => 'male',
                    'date_of_birth' => '1990-01-01',
                    'date_joined' => now(),
                    'department' => $rowValues['department'],
                    'designation' => $rowValues['designation'],
                    'current_grade_id' => $grade->id,
                    'status' => 'active',
                    'physical_address' => 'Not Provided',
                    'emergency_contact_name' => 'Not Provided',
                    'emergency_contact_phone' => 'Not Provided',
                    'role' => 'Teacher',
                    'marital_status' => 'single',
                ]);

                $importedCount++;
            }
        });

        fclose($handle);
        unlink($this->filePath); // Delete the temporary file cleanly
    }
}
