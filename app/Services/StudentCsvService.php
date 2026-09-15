<?php

namespace App\Services;

use App\Models\School;
use App\Services\Csv\CsvBulkService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
            'student_id_number' => [
                'label' => __('Student ID'),
                'required' => false,
                'guesses' => ['Student ID', 'Student Id', 'Student No', 'Student Number', 'ID', 'Admission No', 'Admission Number'],
                'example' => '',
            ],
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
                'bold' => true,
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
                'example' => 'Grade 1',
            ],
            'section' => [
                'label' => __('Stream / Class'),
                'required' => true,
                'guesses' => ['Stream / Class', 'Stream', 'Class', 'Section', 'Class Stream'],
                'example' => 'A',
            ],
            'academic_year' => [
                'label' => __('Academic Year'),
                'required' => false,
                'bold' => true,
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
                'guesses' => ['Parent / Guardian Email', 'Parent Email', 'Guardian Email'],
                'example' => 'parent@example.com',
            ],
            'email' => [
                'label' => __('Email Address'),
                'required' => false,
                'guesses' => ['Email Address', 'Student Email', 'Email', 'E-Mail', 'E-Mail Address'],
                'example' => 'tendai.moyo@example.com',
            ],
            'phone' => [
                'label' => __('Phone Number'),
                'required' => false,
                'guesses' => ['Phone Number', 'Phone', 'Telephone', 'Mobile Number', 'Contact Number'],
                'example' => '+263 771 234 567',
            ],
            'physical_address' => [
                'label' => __('Physical Address'),
                'required' => false,
                'guesses' => ['Physical Address', 'Home Address', 'Address', 'Residential Address'],
                'example' => '14 Links Lane, Borrowdale, Harare',
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
        $schoolType = self::currentSchoolType();
        $isPrimary = in_array($schoolType, ['primary', 'both'], true);

        return collect(static::columns())
            ->map(fn (array $column): string => static::templateLabelFor($column, $isPrimary))
            ->values()
            ->all();
    }

    protected static function templateLabelFor(array $column, bool $isPrimary): string
    {
        $label = $column['label'];

        if ($isPrimary) {
            $label = match ($label) {
                'Form / Grade' => 'Grade',
                'Stream / Class' => 'Class / Stream',
                default => $label,
            };
        }

        return $label;
    }

    protected static function currentSchoolType(): string
    {
        $schoolId = current_tenant()?->id;

        if (! $schoolId) {
            return 'secondary';
        }

        return strtolower((string) (School::find($schoolId)->institution_type ?? 'secondary'));
    }

    protected static function templateRows(): array
    {
        $schoolType = self::currentSchoolType();
        $isPrimary = in_array($schoolType, ['primary', 'both'], true);

        return [static::templateExampleRow($isPrimary)];
    }

    protected static function templateExampleRow(bool $isPrimary): array
    {
        [$course, $stream] = static::resolveExampleCourseAndSection($isPrimary);
        $year = static::resolveExampleAcademicYear();

        return [
            '', 'Tendai', 'Moyo', 'female', '2013-05-14', '2026-01-12', '', 'active',
            $course, $stream, $year, '12', 'Nelson', 'day_scholar', 'O+',
            'parent@example.com', 'tendai.moyo@example.com', '+263 771 234 567',
            '14 Links Lane, Borrowdale, Harare', 'Mercy Moyo', '+263 771 234 567', 'Asthmatic',
        ];
    }

    /**
     * Choose a real course + section from the tenant school (preferring the
     * school-type prefix, e.g. "Grade" for primary / "Form" for secondary) so
     * the downloaded template can be imported end-to-end as-is. Falls back to
     * the generic defaults when the school has no matching course yet.
     *
     * @return array{0: string, 1: string} [course name, section name]
     */
    protected static function resolveExampleCourseAndSection(bool $isPrimary): array
    {
        $schoolId = current_tenant()?->id;
        $defaultCourse = $isPrimary ? 'Grade 1' : 'Form 1';
        $needle = $isPrimary ? 'grade' : 'form';

        if (! $schoolId) {
            return [$defaultCourse, 'A'];
        }

        $course = Course::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->first(fn (Course $course): bool => str_contains(strtolower($course->name), $needle));

        if (! $course) {
            return [$defaultCourse, 'A'];
        }

        $section = Section::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('course_id', $course->id)
            ->orderBy('name')
            ->value('name') ?? 'A';

        return [$course->name, $section];
    }

    /** Active academic year first, then any built year, then the current calendar year. */
    protected static function resolveExampleAcademicYear(): string
    {
        $schoolId = current_tenant()?->id;

        if ($schoolId) {
            $year = AcademicYear::where('school_id', $schoolId)
                ->orderByDesc('is_active')
                ->orderByDesc('start_date')
                ->value('name');

            if ($year) {
                return $year;
            }
        }

        return (string) now()->year;
    }

    public static function exportHeaders(): array
    {
        return [
            'Student ID', 'Admission Number', 'National ID', 'First Name', 'Last Name',
            'Gender', 'Date of Birth', 'Admission Date', 'Status',
            'Grade', 'Class / Stream', 'Academic Year', 'Roll Number',
            'House', 'Boarding Status', 'Blood Group',
            'Parent / Guardian Email', 'Email Address', 'Phone Number', 'Physical Address',
            'Emergency Contact Name', 'Emergency Contact Phone',
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
            $student->email,
            $student->phone,
            $student->physical_address,
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
            throw new \RuntimeException('The uploaded file has no readable header row. Download the template and use its exact column names.');
        }

        $headerIndex = [];
        foreach ($csvHeaders as $i => $header) {
            $headerIndex[static::normaliseHeader($header)] = $i;
        }

        $mappedIndexes = [];
        foreach ($columnMap as $key => $header) {
            if (blank($header)) {
                $mappedIndexes[$key] = null;

                continue;
            }
            $mappedIndexes[$key] = $headerIndex[static::normaliseHeader($header)] ?? null;
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Could not open the uploaded file.');
        }

        static::skipHeaderBlock($handle); // skip header row

        $total = 0;
        while (fgetcsv($handle, 0, ',', escape: '\\') !== false) {
            $total++;
        }

        rewind($handle);
        static::skipHeaderBlock($handle); // skip header row again

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

        $existingStudentIds = Student::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->whereNotNull('student_id_number')
            ->pluck('student_id_number')
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

            $errors = static::validateAndNormalize($data, $courses, $sectionsByCourse, $academicYears, $activeAcademicYear, $existingNationalIds, $existingStudentIds);

            if (! empty($errors)) {
                $failures[] = ['row' => $rowNumber, 'errors' => $errors, 'data' => $data];
                $onProgress !== null && $onProgress($processed, $total, true, $errors);

                continue;
            }

            try {
                DB::transaction(function () use (&$existingNationalIds, &$existingStudentIds, $data, $schoolId) {
                    $course = $data['_course'];
                    $section = $data['_section'];
                    $year = $data['_academic_year'];

                    $suffix = Student::$levelSuffixes[$course->name] ?? 'X';
                    $admissionDate = $data['admission_date'] !== '' ? $data['admission_date'] : now()->toDateString();

                    $studentId = $data['student_id_number'] !== ''
                        ? $data['student_id_number']
                        : static::generateStudentIdNumber($schoolId, Carbon::parse($admissionDate), $suffix);

                    $student = Student::create([
                        'school_id' => $schoolId,
                        'student_id_number' => $studentId,
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
                        'email' => $data['email'] !== '' ? $data['email'] : null,
                        'phone' => $data['phone'] !== '' ? $data['phone'] : null,
                        'physical_address' => $data['physical_address'] !== '' ? $data['physical_address'] : null,
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

                    if (filled($student->student_id_number)) {
                        $existingStudentIds[strtolower(trim($student->student_id_number))] = true;
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
        Collection $existingStudentIds,
    ): array {
        $errors = [];

        $data['first_name'] = trim($data['first_name'] ?? '');
        $data['last_name'] = trim($data['last_name'] ?? '');
        $data['student_id_number'] = trim($data['student_id_number'] ?? '');
        $data['national_id'] = trim($data['national_id'] ?? '');
        $data['course'] = trim($data['course'] ?? '');
        $data['section'] = trim($data['section'] ?? '');
        $data['academic_year'] = trim($data['academic_year'] ?? '');
        $data['roll_number'] = trim($data['roll_number'] ?? '');
        $data['house'] = trim($data['house'] ?? '');
        $data['parent_email'] = trim($data['parent_email'] ?? '');
        $data['email'] = trim($data['email'] ?? '');
        $data['phone'] = trim($data['phone'] ?? '');
        $data['physical_address'] = trim($data['physical_address'] ?? '');
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

        if ($data['email'] !== '' && filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Email Address ['.$data['email'].'] is not a valid email address.';
        }

        if ($data['email'] !== '' && $data['parent_email'] !== '' && mb_strtolower($data['email']) === mb_strtolower($data['parent_email'])) {
            $errors[] = 'Email Address must differ from Parent / Guardian Email.';
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

        if ($data['student_id_number'] !== '' && isset($existingStudentIds[strtolower($data['student_id_number'])])) {
            $errors[] = 'Student ID ['.$data['student_id_number'].'] is already registered for a student in this school.';
        }

        $data['_course'] = $course;
        $data['_section'] = $section;
        $data['_academic_year'] = $year;

        return $errors;
    }

    /** Replicates the Student model's R-YY-XXXX-letter scheme with the correct level suffix. */
    protected static function generateStudentIdNumber(int $schoolId, Carbon $admissionDate, string $suffix): string
    {
        $yearYY = $admissionDate->format('y');

        do {
            $randomMiddle = mt_rand(1000, 9999);
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
