<?php

namespace App\Services;

use App\Services\Csv\CsvBulkService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

class StudentCsvService extends CsvBulkService
{
    /**
     * Single source of truth for every student CSV feature.
     *
     * Keys are used as the columnMap keys in the two-phase import flow.
     * 'label' is the exact system column header shown in the template,
     * the upload step and the export.
     */
    public static function columns(): array
    {
        return [
            'first_name' => [
                'label' => __('First Name'),
                'required' => true,
                'guesses' => ['First Name', 'Firstname', 'Given Name', 'Student First Name'],
                'example' => 'Tendai',
            ],
            'last_name' => [
                'label' => __('Last Name'),
                'required' => true,
                'guesses' => ['Last Name', 'Lastname', 'Surname', 'Student Last Name'],
                'example' => 'Moyo',
            ],
            'gender' => [
                'label' => __('Gender'),
                'required' => true,
                'guesses' => ['Gender', 'Sex'],
                'example' => 'female',
                'in' => ['male', 'female', 'other'],
            ],
            'date_of_birth' => [
                'label' => __('Date of Birth'),
                'required' => true,
                'guesses' => ['Date of Birth', 'DOB', 'Birth Date'],
                'example' => '2013-05-14',
                'date' => true,
            ],
            'admission_date' => [
                'label' => __('Admission Date'),
                'required' => false,
                'guesses' => ['Admission Date'],
                'example' => '2026-01-12',
                'date' => true,
            ],
            'national_id' => [
                'label' => __('National ID'),
                'required' => false,
                'guesses' => ['National ID', 'ID Number', 'Passport'],
                'example' => '63-123456A78',
            ],
            'status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status'],
                'example' => 'active',
                'default' => 'active',
                'in' => ['active', 'inactive', 'suspended', 'graduated'],
            ],
            'course' => [
                'label' => __('Form / Grade'),
                'required' => true,
                'guesses' => ['Form / Grade', 'Form', 'Grade', 'Level', 'Course', 'Class Level'],
                'example' => 'Form 1',
            ],
            'section' => [
                'label' => __('Stream / Class'),
                'required' => true,
                'guesses' => ['Stream / Class', 'Stream', 'Class', 'Section'],
                'example' => 'Form 1A',
            ],
            'academic_year' => [
                'label' => __('Academic Year'),
                'required' => false,
                'guesses' => ['Academic Year', 'Year'],
                'example' => '2026',
            ],
            'roll_number' => [
                'label' => __('Roll Number'),
                'required' => false,
                'guesses' => ['Roll Number', 'Roll No', 'Roll'],
                'example' => '12',
            ],
            'house' => [
                'label' => __('House'),
                'required' => false,
                'guesses' => ['House'],
                'example' => 'Nelson',
            ],
            'boarding_status' => [
                'label' => __('Boarding Status'),
                'required' => false,
                'guesses' => ['Boarding Status', 'Boarding'],
                'example' => 'day_scholar',
                'default' => 'day_scholar',
                'in' => ['day_scholar', 'boarder'],
            ],
            'blood_group' => [
                'label' => __('Blood Group'),
                'required' => false,
                'guesses' => ['Blood Group', 'Blood Type'],
                'example' => 'O+',
                'in' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            ],
            'parent_email' => [
                'label' => __('Parent / Guardian Email'),
                'required' => false,
                'guesses' => ['Parent / Guardian Email', 'Parent Email', 'Guardian Email', 'Email'],
                'example' => 'parent@example.com',
            ],
            'emergency_contact_name' => [
                'label' => __('Emergency Contact Name'),
                'required' => false,
                'guesses' => ['Emergency Contact Name'],
                'example' => 'Mercy Moyo',
            ],
            'emergency_contact_phone' => [
                'label' => __('Emergency Contact Phone'),
                'required' => false,
                'guesses' => ['Emergency Contact Phone'],
                'example' => '+263 771 234 567',
            ],
            'medical_notes' => [
                'label' => __('Medical Notes'),
                'required' => false,
                'guesses' => ['Medical Notes', 'Medical Info'],
                'example' => 'Asthmatic',
            ],
        ];
    }

    public static function templateHeaders(): array
    {
        return array_column(static::columns(), 'label');
    }

    public static function templateCsv(): string
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it cleanly
        fputcsv($out, static::templateHeaders());
        fputcsv($out, array_column(static::columns(), 'example'));
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    public static function exportHeaders(): array
    {
        return [
            'Student ID', 'Admission Number', 'National ID', 'First Name', 'Last Name',
            'Gender', 'Date of Birth', 'Admission Date', 'Status',
            'Form / Grade', 'Stream / Class', 'Academic Year', 'Roll Number',
            'House', 'Boarding Status', 'Blood Group',
            'Parent / Guardian Email', 'Emergency Contact Name', 'Emergency Contact Phone',
            'Medical Notes', 'Guardian Name', 'Guardian Phone', 'Guardian Email',
        ];
    }

    public static function exportRow(Student $student): array
    {
        $enrollment = $student->currentEnrollment;
        $guardian = $student->guardians->first();

        return [
            $student->student_id_number,
            $student->admission_number,
            $student->national_id,
            $student->first_name,
            $student->last_name,
            $student->gender,
            optional($student->date_of_birth)->format('Y-m-d'),
            optional($student->admission_date)->format('Y-m-d'),
            $student->status,
            $enrollment?->course?->name,
            $enrollment?->section?->name,
            $enrollment?->academicYear?->name,
            $enrollment?->roll_number,
            $student->house,
            $student->boarding_status,
            $student->blood_group,
            $student->parent_email,
            $student->emergency_contact_name,
            $student->emergency_contact_phone,
            $student->medical_notes,
            $guardian?->name,
            $guardian?->phone,
            $guardian?->email,
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = Student::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['currentEnrollment.course', 'currentEnrollment.section', 'currentEnrollment.academicYear', 'guardians'])
            ->orderBy('id');

        $lastId = 0;

        do {
            $students = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($students->isEmpty()) {
                break;
            }

            foreach ($students as $student) {
                yield static::exportRow($student);
            }

            $lastId = $students->last()->id;
        } while (true);
    }

    public static function resolveTempFilePath(string|TemporaryUploadedFile|array $file): string
    {
        if (is_array($file)) {
            $file = Arr::first($file);
        }

        if ($file instanceof TemporaryUploadedFile) {
            return $file->getRealPath();
        }

        $disk = config('livewire.temporary_file_upload.disk') ?: config('filesystems.default');

        return Storage::disk($disk)->path($file);
    }

    /** Read the header row of an uploaded CSV (BOM-safe). */
    public static function readCsvHeaders(string $filePath): array
    {
        if (! is_readable($filePath)) {
            return [];
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return [];
        }

        $line = fgets($handle);
        fclose($handle);

        if ($line === false) {
            return [];
        }

        $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
        $headers = str_getcsv(trim($line), escape: '\\');

        return array_map('trim', array_map('strval', $headers ?: []));
    }

    /** Auto-match each expected column to the closest matching CSV header. */
    public static function guessMapping(array $csvHeaders): array
    {
        $lowerHeaders = array_map('strtolower', $csvHeaders);
        $mapping = [];

        foreach (static::columns() as $key => $column) {
            $guesses = array_map('strtolower', $column['guesses']);
            $mapping[$key] = null;

            foreach ($lowerHeaders as $i => $lowerHeader) {
                if (in_array($lowerHeader, $guesses, true)) {
                    $mapping[$key] = $csvHeaders[$i];
                    break;
                }
            }
        }

        return $mapping;
    }

    /**
     * Synchronously import a CSV into students + enrollments for a school.
     * Valid rows are saved; invalid rows are reported per-row with exact messages.
     *
     * @param  array<string, string|null>  $columnMap  expected column key => selected CSV header
     * @param  callable|null  $onProgress  fn(int $processed, int $total, bool $rowFailed, array $errors)
     * @return array{success: int, total: int, failures: array}
     */
    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $csvHeaders = static::readCsvHeaders($filePath);

        if (empty($csvHeaders)) {
            throw new \RuntimeException('The CSV file has no readable header row. Download the template and use its exact column names.');
        }

        $headerIndex = [];
        foreach ($csvHeaders as $i => $header) {
            $headerIndex[strtolower($header)] = $i;
        }

        $mappedIndexes = [];
        foreach ($columnMap as $key => $header) {
            if (blank($header)) {
                $mappedIndexes[$key] = null;

                continue;
            }
            $mappedIndexes[$key] = $headerIndex[strtolower($header)] ?? null;
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Could not open the CSV file.');
        }

        fgets($handle); // skip header row

        $total = 0;
        while (fgetcsv($handle, 0, ',', escape: '\\') !== false) {
            $total++;
        }

        rewind($handle);
        fgets($handle); // skip header row again

        $courses = Course::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn (Course $c): string => strtolower(trim($c->name)));

        $sectionsByCourse = Section::withoutTenantScope()->where('school_id', $schoolId)->get()->groupBy('course_id');

        $academicYears = AcademicYear::where('school_id', $schoolId)->get()
            ->keyBy(fn (AcademicYear $y): string => strtolower(trim($y->name)));

        $activeAcademicYear = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->first();

        $existingNationalIds = Student::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->whereNotNull('national_id')
            ->pluck('national_id')
            ->map(fn ($v): string => strtolower(trim((string) $v)))
            ->flip();

        $success = 0;
        $failures = [];
        $processed = 0;
        $rowNumber = 1; // row 1 is the header

        while (($row = fgetcsv($handle, 0, ',', escape: '\\')) !== false) {
            $rowNumber++;
            $processed++;
            $row = array_map('trim', $row);

            $data = array_fill_keys(array_keys(static::columns()), '');

            foreach ($mappedIndexes as $key => $index) {
                $data[$key] = ($index !== null && isset($row[$index])) ? $row[$index] : '';
            }

            if (implode('', $data) === '') {
                $onProgress !== null && $onProgress($processed, $total, false, []);

                continue;
            }

            $errors = static::validateAndNormalize($data, $courses, $sectionsByCourse, $academicYears, $activeAcademicYear, $existingNationalIds);

            if (! empty($errors)) {
                $failures[] = ['row' => $rowNumber, 'errors' => $errors, 'data' => $data];
                $onProgress !== null && $onProgress($processed, $total, true, $errors);

                continue;
            }

            try {
                DB::transaction(function () use (&$existingNationalIds, $data, $schoolId) {
                    $course = $data['_course'];
                    $section = $data['_section'];
                    $year = $data['_academic_year'];

                    $suffix = Student::$levelSuffixes[$course->name] ?? 'X';
                    $admissionDate = $data['admission_date'] !== '' ? $data['admission_date'] : now()->toDateString();

                    $student = Student::create([
                        'school_id' => $schoolId,
                        'student_id_number' => static::generateStudentIdNumber($schoolId, Carbon::parse($admissionDate), $suffix),
                        'national_id' => $data['national_id'] !== '' ? $data['national_id'] : null,
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'gender' => $data['gender'],
                        'date_of_birth' => $data['date_of_birth'],
                        'admission_date' => $admissionDate,
                        'status' => $data['status'] !== '' ? $data['status'] : 'active',
                        'house' => $data['house'] !== '' ? $data['house'] : null,
                        'boarding_status' => $data['boarding_status'] !== '' ? $data['boarding_status'] : 'day_scholar',
                        'blood_group' => $data['blood_group'] !== '' ? $data['blood_group'] : null,
                        'parent_email' => $data['parent_email'] !== '' ? $data['parent_email'] : null,
                        'emergency_contact_name' => $data['emergency_contact_name'] !== '' ? $data['emergency_contact_name'] : null,
                        'emergency_contact_phone' => $data['emergency_contact_phone'] !== '' ? $data['emergency_contact_phone'] : null,
                        'medical_notes' => $data['medical_notes'] !== '' ? $data['medical_notes'] : null,
                    ]);

                    Enrollment::create([
                        'school_id' => $schoolId,
                        'student_id' => $student->id,
                        'academic_year_id' => $year->id,
                        'course_id' => $course->id,
                        'section_id' => $section->id,
                        'roll_number' => $data['roll_number'] !== '' ? $data['roll_number'] : null,
                    ]);

                    if (filled($student->national_id)) {
                        $existingNationalIds[strtolower(trim($student->national_id))] = true;
                    }
                });

                $success++;
                $onProgress !== null && $onProgress($processed, $total, false, []);
            } catch (\Throwable $e) {
                $failures[] = [
                    'row' => $rowNumber,
                    'errors' => ['Unexpected database error while saving: '.$e->getMessage()],
                    'data' => $data,
                ];
                $onProgress !== null && $onProgress($processed, $total, true, ['Unexpected database error']);
            }
        }

        fclose($handle);

        return compact('success', 'total', 'failures');
    }

    /**
     * Validate one CSV row and normalize its values (dates, enums, lookups).
     * Reference lookups are attached to $data as '_course', '_section', '_academic_year'.
     *
     * @return array<int, string> List of human-readable errors (empty when valid).
     */
    protected static function validateAndNormalize(
        array &$data,
        Collection $courses,
        Collection $sectionsByCourse,
        Collection $academicYears,
        ?AcademicYear $activeAcademicYear,
        Collection $existingNationalIds,
    ): array {
        $errors = [];

        $data['first_name'] = trim($data['first_name'] ?? '');
        $data['last_name'] = trim($data['last_name'] ?? '');
        $data['national_id'] = trim($data['national_id'] ?? '');
        $data['course'] = trim($data['course'] ?? '');
        $data['section'] = trim($data['section'] ?? '');
        $data['academic_year'] = trim($data['academic_year'] ?? '');
        $data['roll_number'] = trim($data['roll_number'] ?? '');
        $data['house'] = trim($data['house'] ?? '');
        $data['parent_email'] = trim($data['parent_email'] ?? '');
        $data['emergency_contact_name'] = trim($data['emergency_contact_name'] ?? '');
        $data['emergency_contact_phone'] = trim($data['emergency_contact_phone'] ?? '');
        $data['medical_notes'] = trim($data['medical_notes'] ?? '');

        if ($data['first_name'] === '') {
            $errors[] = 'First Name is required (column empty or not mapped).';
        }

        if ($data['last_name'] === '') {
            $errors[] = 'Last Name is required (column empty or not mapped).';
        }

        $data['gender'] = static::normaliseGender($data['gender'] ?? '');
        if (! in_array($data['gender'], ['male', 'female', 'other'], true)) {
            $errors[] = 'Gender must be one of: male, female, other.';
        }

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] !== '' && ! in_array($data['status'], ['active', 'inactive', 'suspended', 'graduated'], true)) {
            $errors[] = 'Status must be one of: active, inactive, suspended, graduated.';
        }

        $data['boarding_status'] = static::normaliseBoardingStatus($data['boarding_status'] ?? '');
        if ($data['boarding_status'] !== '' && ! in_array($data['boarding_status'], ['day_scholar', 'boarder'], true)) {
            $errors[] = 'Boarding Status must be one of: day_scholar, boarder.';
        }

        $data['blood_group'] = strtoupper(trim($data['blood_group'] ?? ''));
        if ($data['blood_group'] !== '' && ! in_array($data['blood_group'], ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true)) {
            $errors[] = 'Blood Group must be one of: A+, A-, B+, B-, AB+, AB-, O+, O-.';
        }

        if ($data['parent_email'] !== '' && filter_var($data['parent_email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Parent / Guardian Email ['.$data['parent_email'].'] is not a valid email address.';
        }

        foreach (['date_of_birth', 'admission_date'] as $dateField) {
            $raw = trim($data[$dateField] ?? '');

            if ($raw === '') {
                if ($dateField === 'date_of_birth') {
                    $errors[] = 'Date of Birth is required (column empty or not mapped).';
                }

                continue;
            }

            try {
                $data[$dateField] = Carbon::parse($raw)->toDateString();
            } catch (\Throwable) {
                $errors[] = static::columns()[$dateField]['label'].' ['.$raw.'] is not a valid date. Use YYYY-MM-DD.';
            }
        }

        if (empty($errors) && $data['course'] === '') {
            $errors[] = 'Form / Grade is required (column empty or not mapped).';
        }

        if (empty($errors) && $data['section'] === '') {
            $errors[] = 'Stream / Class is required (column empty or not mapped).';
        }

        if (! empty($errors)) {
            return $errors;
        }

        $course = $courses[strtolower($data['course'])] ?? null;
        if (! $course) {
            $errors[] = 'Form / Grade ['.$data['course'].'] was not found in this school. Available levels: '.($courses->pluck('name')->implode(', ') ?: 'none').'.';

            return $errors;
        }

        $sections = $sectionsByCourse->get($course->id, collect());
        $section = $sections->first(fn (Section $s): bool => strtolower(trim($s->name)) === strtolower($data['section']));
        if (! $section) {
            $errors[] = 'Stream / Class ['.$data['section'].'] was not found under ['.$course->name.']. Available streams: '.($sections->pluck('name')->implode(', ') ?: 'none').'.';

            return $errors;
        }

        $year = $data['academic_year'] !== ''
            ? ($academicYears[strtolower($data['academic_year'])] ?? null)
            : $activeAcademicYear;

        if (! $year) {
            $errors[] = $data['academic_year'] !== ''
                ? 'Academic Year ['.$data['academic_year'].'] was not found in this school. Available years: '.($academicYears->pluck('name')->implode(', ') ?: 'none').'.'
                : 'No active Academic Year is set for this school. Set one active year, or include an Academic Year column.';

            return $errors;
        }

        if ($data['national_id'] !== '' && isset($existingNationalIds[strtolower($data['national_id'])])) {
            $errors[] = 'National ID ['.$data['national_id'].'] is already registered for a student in this school.';
        }

        $data['_course'] = $course;
        $data['_section'] = $section;
        $data['_academic_year'] = $year;

        return $errors;
    }

    /** Replicates the Student model's R-YY-XXXXXXX-letter scheme with the correct level suffix. */
    protected static function generateStudentIdNumber(int $schoolId, Carbon $admissionDate, string $suffix): string
    {
        $yearYY = $admissionDate->format('y');

        do {
            $randomMiddle = mt_rand(1000000, 9999999);
            $candidate = "R{$yearYY}{$randomMiddle}{$suffix}";
        } while (Student::withoutTenantScope()->where('school_id', $schoolId)->where('student_id_number', $candidate)->exists());

        return $candidate;
    }

    protected static function normaliseGender(string $value): string
    {
        $normalised = strtolower(trim($value));
        $map = [
            'm' => 'male',
            'male' => 'male',
            'f' => 'female',
            'female' => 'female',
            'other' => 'other',
        ];

        return $map[$normalised] ?? $normalised;
    }

    protected static function normaliseBoardingStatus(string $value): string
    {
        $normalised = strtolower(trim($value));
        $map = [
            'day' => 'day_scholar',
            'day scholar' => 'day_scholar',
            'dayscholar' => 'day_scholar',
            'day_scholar' => 'day_scholar',
            'boarding' => 'boarder',
            'boarder' => 'boarder',
            'residential' => 'boarder',
        ];

        return $map[$normalised] ?? $normalised;
    }
}
