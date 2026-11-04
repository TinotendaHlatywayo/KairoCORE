<?php

namespace App\Services;

use App\Models\School;
use App\Services\Csv\CsvBulkService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

class StudentCsvService extends CsvBulkService
{
    public static $missingStreamHandler = null;
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
                'guesses' => ['Student ID', 'Student Id', 'Student ID Number', 'Student No', 'Student Number', 'Registration Number', 'Reg Number', 'Reg No', 'ID', 'Admission No', 'Admission Number'],
                'example' => '',
            ],
            'first_name' => [
                'label' => __('First Name'),
                'required' => true,
                'guesses' => ['First Name', 'Firstname', 'First', 'Given Name', 'Forename', 'Student First Name'],
                'example' => 'Tendai',
            ],
            'last_name' => [
                'label' => __('Last Name'),
                'required' => true,
                'guesses' => ['Last Name', 'Lastname', 'Last', 'Surname', 'Family Name', 'Student Last Name'],
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
                'required' => false,
                'guesses' => ['Date of Birth', 'DOB', 'D.O.B', 'Birth Date', 'Birthdate'],
                'example' => '2013-05-14',
                'date' => true,
            ],
            'admission_date' => [
                'label' => __('Admission Date'),
                'required' => false,
                'bold' => true,
                'guesses' => ['Admission Date', 'Date of Admission', 'Enrollment Date', 'Enrolment Date', 'Date Enrolled', 'Joining Date'],
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
                'guesses' => ['Status', 'Student Status', 'Enrollment Status', 'Enrolment Status'],
                'example' => 'active',
                'default' => 'active',
                'in' => ['active', 'inactive', 'suspended', 'graduated'],
            ],
            'course' => [
                'label' => __('Form / Grade'),
                'required' => true,
                'guesses' => ['Form / Grade', 'Form', 'Grade', 'Level', 'Course', 'Class Level', 'Year Group'],
                'example' => 'Grade 1',
            ],
            'section' => [
                'label' => __('Stream / Class'),
                'required' => true,
                'guesses' => ['Stream / Class', 'Class / Stream', 'Stream', 'Class', 'Section', 'Class Stream'],
                'example' => 'A',
            ],
            'academic_year' => [
                'label' => __('Academic Year'),
                'required' => false,
                'bold' => true,
                'guesses' => ['Academic Year', 'Year', 'Academic Session', 'Session'],
                'example' => '2026',
            ],
            'roll_number' => [
                'label' => __('Roll Number'),
                'required' => false,
                'guesses' => ['Roll Number', 'Roll No', 'Roll', 'Class Number'],
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
                'guesses' => ['Boarding Status', 'Boarding', 'Residence', 'Residential Status'],
                'example' => 'day_scholar',
                'default' => 'day_scholar',
                'in' => ['day_scholar', 'boarder'],
            ],
            'blood_group' => [
                'label' => __('Blood Group'),
                'required' => false,
                'guesses' => ['Blood Group', 'Blood Type', 'Blood'],
                'example' => 'O+',
                'in' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            ],
            'parent_email' => [
                'label' => __('Parent / Guardian Email'),
                'required' => false,
                'guesses' => ['Parent / Guardian Email', 'Parent Email', 'Guardian Email', 'Parent E-Mail'],
                'example' => 'parent@example.com',
            ],
            'email' => [
                'label' => __('Email Address'),
                'required' => false,
                'guesses' => ['Email Address', 'Student Email', 'Learner Email', 'Email', 'E-Mail', 'E-Mail Address'],
                'example' => 'tendai.moyo@example.com',
            ],
            'phone' => [
                'label' => __('Phone Number'),
                'required' => false,
                'guesses' => ['Phone Number', 'Phone', 'Telephone', 'Mobile Number', 'Mobile', 'Contact Number', 'Cell Number'],
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

        $courses = Course::withoutTenantScope()->where('school_id', $schoolId)->get();

        if ($courses->isEmpty()) {
            return [$defaultCourse, 'A'];
        }

        $sectionsByCourse = Section::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get()
            ->groupBy('course_id');

        // Prefer a school-type match, but only pick a course that actually has
        // a stream so the downloaded example row can be imported unchanged.
        $ordered = $courses
            ->filter(fn (Course $course): bool => preg_match('/\b'.preg_quote($needle, '/').'\b/i', $course->name) === 1)
            ->concat($courses)
            ->unique('id');

        foreach ($ordered as $course) {
            $section = $sectionsByCourse->get($course->id)?->first()?->name;

            if (filled($section)) {
                return [$course->name, $section];
            }
        }

        return [$courses->first()->name, 'A'];
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

        $delimiter = static::csvDelimiter($filePath);

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

        static::skipHeaderBlock($handle, $delimiter, $csvHeaders); // skip header row

        $total = 0;
        while (fgetcsv($handle, 0, $delimiter, escape: '\\') !== false) {
            $total++;
        }

        rewind($handle);
        static::skipHeaderBlock($handle, $delimiter, $csvHeaders); // skip header row again

        // Lookups are keyed by a forgiving form (case, spacing and punctuation
        // are ignored) so "ECD  B", "ecd-b" and "ECD B" all find the same level.
        $courses = Course::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn (Course $c): string => static::normaliseKey($c->name));

        $sectionsByCourse = Section::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->groupBy('course_id')
            ->map(fn (Collection $sections): Collection => $sections->keyBy(fn (Section $s): string => static::normaliseKey($s->name)));

        $academicYears = AcademicYear::where('school_id', $schoolId)->get()
            ->keyBy(fn (AcademicYear $y): string => static::normaliseKey($y->name));

        $activeAcademicYear = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->first();

        $existingNationalIds = Student::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->whereNotNull('national_id')
            ->pluck('national_id')
            ->map(fn ($v): string => static::normaliseIdentifier((string) $v))
            ->filter(fn (string $v): bool => $v !== '')
            ->flip();

        $existingStudentIds = Student::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->whereNotNull('student_id_number')
            ->pluck('student_id_number')
            ->map(fn ($v): string => static::normaliseIdentifier((string) $v))
            ->filter(fn (string $v): bool => $v !== '')
            ->flip();

        $existingStudentFingerprints = Student::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->get()
            ->map(fn (Student $s): string => static::studentFingerprint($s->first_name, $s->last_name, (string) $s->gender, $s->date_of_birth?->format('Y-m-d')))
            ->flip();

        $success = 0;
        $failures = [];
        $processed = 0;
        $activationSent = 0;
        $activationFailed = 0;
        $rowNumber = 1; // row 1 is the header

        while (($row = fgetcsv($handle, 0, $delimiter, escape: '\\')) !== false) {
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

            $errors = static::validateAndNormalize($data, $courses, $sectionsByCourse, $academicYears, $activeAcademicYear, $existingNationalIds, $existingStudentIds, $existingStudentFingerprints);

            if (! empty($errors)) {
                $failures[] = ['row' => $rowNumber, 'errors' => $errors, 'data' => $data];
                $onProgress !== null && $onProgress($processed, $total, true, $errors);

                continue;
            }

            try {
                $student = DB::transaction(function () use (&$existingNationalIds, &$existingStudentIds, &$existingStudentFingerprints, $data, $schoolId) {
                    $course = $data['_course'];
                    $section = $data['_section'];
                    $year = $data['_academic_year'];

                    $suffix = static::levelSuffix($course->name);
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
                        'date_of_birth' => $data['date_of_birth'] !== '' ? $data['date_of_birth'] : null,
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
                        $existingNationalIds[static::normaliseIdentifier($student->national_id)] = true;
                    }

                    if (filled($student->student_id_number)) {
                        $existingStudentIds[static::normaliseIdentifier($student->student_id_number)] = true;
                    }

                    $existingStudentFingerprints[static::studentFingerprint(
                        $student->first_name,
                        $student->last_name,
                        (string) $student->gender,
                        $student->date_of_birth?->format('Y-m-d'),
                    )] = true;

                    return $student;
                });

                // Portal account provisioning happens after the roster row is
                // committed. A mail-server or account problem must never roll
                // back (and silently drop) a student that was imported fine.
                try {
                    $user = app(RosterAccountProvisioningService::class)->provisionStudent($student);

                    if ($user) {
                        $activationSent++;
                    } elseif (filled($student->email)) {
                        $activationFailed++;
                    }
                } catch (\Throwable $e) {
                    report($e);

                    if (filled($student->email)) {
                        $activationFailed++;
                    }
                }

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

        // Notify about activation emails sent/failed
        if ($activationSent > 0 || $activationFailed > 0) {
            try {
                Notification::make()
                    ->title(__('Activation e-mails'))
                    ->body(
                        ($activationSent > 0 ? __(':count activation e-mail(s) sent', ['count' => $activationSent]) : __(':count activation e-mail(s) sent', ['count' => 0]))
                        .($activationFailed > 0 ? ' | '.__(':count student(s) had email but activation failed', ['count' => $activationFailed]) : '')
                    )
                    ->icon($activationFailed > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                    ->color($activationFailed > 0 ? 'warning' : 'success')
                    ->send();
            } catch (\Throwable) {
                // Ignore notification failures (e.g., in test environment)
            }
        }

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
        Collection $existingStudentFingerprints,
    ): array {
        $errors = [];

        foreach ([
            'first_name', 'last_name', 'course', 'section', 'academic_year',
            'house', 'physical_address', 'emergency_contact_name', 'medical_notes',
        ] as $textField) {
            $data[$textField] = static::normaliseText($data[$textField] ?? '');
        }

        foreach ([
            'student_id_number', 'national_id', 'roll_number', 'parent_email',
            'email', 'phone', 'emergency_contact_phone',
        ] as $trimField) {
            $val = trim($data[$trimField] ?? '');
            if (($trimField === 'national_id' || in_array($trimField, ['parent_email', 'email'], true)) && static::isPlaceholderValue($val)) {
                $val = '';
            }
            $data[$trimField] = $val;
        }

        if (str_ends_with($data['academic_year'], '.0')) {
            $data['academic_year'] = (string) (int) floatval($data['academic_year']);
        }

        // Normalize grade/course typos and numeric levels
        $gradeRaw = $data['course'];
        if (is_numeric($gradeRaw)) {
            $gradeRaw = 'Grade ' . $gradeRaw;
        }
        $gradeRaw = str_ireplace(['garde', 'gr.'], ['grade', 'Grade'], $gradeRaw);
        $data['course'] = static::normaliseText($gradeRaw);

        // Normalize section/stream
        $sectionRaw = $data['section'];
        if (is_numeric($sectionRaw) && in_array(strtoupper($data['course']), ['ECD A', 'ECD B', 'ECD  B'])) {
            $sectionRaw = 'A';
        }
        $data['section'] = strtoupper(static::normaliseText($sectionRaw));

        // If course is blank but section is 'special class', set course to 'Special Class'
        if ($data['course'] === '' && mb_strtolower($data['section']) === 'special class') {
            $data['course'] = 'Special Class';
        }

        // If last_name is blank and first_name has multiple words, split them
        if ($data['last_name'] === '' && str_contains($data['first_name'], ' ')) {
            $parts = preg_split('/\s+/', $data['first_name']);
            if (count($parts) > 1) {
                $data['last_name'] = array_pop($parts);
                $data['first_name'] = implode(' ', $parts);
            }
        }

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

        $data['status'] = static::normaliseStatus($data['status'] ?? '');
        if ($data['status'] !== '' && ! in_array($data['status'], ['active', 'inactive', 'suspended', 'graduated'], true)) {
            $errors[] = 'Status must be one of: active, inactive, suspended, graduated.';
        }

        $data['boarding_status'] = static::normaliseBoardingStatus($data['boarding_status'] ?? '');
        if ($data['boarding_status'] !== '' && ! in_array($data['boarding_status'], ['day_scholar', 'boarder'], true)) {
            $errors[] = 'Boarding Status must be one of: day_scholar, boarder.';
        }

        $data['blood_group'] = static::normaliseBloodGroup($data['blood_group'] ?? '');
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

            if ($raw === '' || static::isPlaceholderValue($raw)) {
                $data[$dateField] = '';
                continue;
            }

            $parsed = static::parseDateValue($raw);

            if ($parsed === null) {
                $errors[] = static::columns()[$dateField]['label'].' ['.$raw.'] is not a valid date. Use YYYY-MM-DD.';
            } else {
                $data[$dateField] = $parsed;
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

        $course = $courses->get(static::normaliseKey($data['course']));
        if (! $course) {
            if (static::$missingStreamHandler !== null) {
                $schoolId = \App\Models\School::first()?->id ?? 1;
                $created = call_user_func(static::$missingStreamHandler, $schoolId, $data['course'], $data['section']);
                if ($created) {
                    $courses = Course::withoutTenantScope()->where('school_id', $schoolId)->get()
                        ->keyBy(fn (Course $c): string => static::normaliseKey($c->name));
                    $sectionsByCourse = Section::withoutTenantScope()->where('school_id', $schoolId)->get()
                        ->groupBy('course_id')
                        ->map(fn (Collection $sections): Collection => $sections->keyBy(fn (Section $s): string => static::normaliseKey($s->name)));
                    $course = $courses->get(static::normaliseKey($data['course']));
                }
            }
            if (! $course) {
                $errors[] = 'Form / Grade ['.$data['course'].'] was not found in this school. Available levels: '.($courses->pluck('name')->implode(', ') ?: 'none').'.';
                return $errors;
            }
        }

        $sections = $sectionsByCourse->get($course->id, collect());
        $section = $sections->get(static::normaliseKey($data['section']));
        if (! $section) {
            if (static::$missingStreamHandler !== null) {
                $schoolId = \App\Models\School::first()?->id ?? 1;
                $created = call_user_func(static::$missingStreamHandler, $schoolId, $course->name, $data['section']);
                if ($created) {
                    $sectionsByCourse = Section::withoutTenantScope()->where('school_id', $schoolId)->get()
                        ->groupBy('course_id')
                        ->map(fn (Collection $sections): Collection => $sections->keyBy(fn (Section $s): string => static::normaliseKey($s->name)));
                    $sections = $sectionsByCourse->get($course->id, collect());
                    $section = $sections->get(static::normaliseKey($data['section']));
                }
            }
            if (! $section) {
                $errors[] = 'Stream / Class ['.$data['section'].'] was not found under ['.$course->name.']. Available streams: '.($sections->pluck('name')->implode(', ') ?: 'none').'.';
                return $errors;
            }
        }

        $year = $data['academic_year'] !== ''
            ? $academicYears->get(static::normaliseKey($data['academic_year']))
            : $activeAcademicYear;

        if (! $year) {
            $errors[] = $data['academic_year'] !== ''
                ? 'Academic Year ['.$data['academic_year'].'] was not found in this school. Available years: '.($academicYears->pluck('name')->implode(', ') ?: 'none').'.'
                : 'No active Academic Year is set for this school. Set one active year, or include an Academic Year column.';

            return $errors;
        }

        if ($data['national_id'] !== '' && isset($existingNationalIds[static::normaliseIdentifier($data['national_id'])])) {
            $errors[] = 'National ID ['.$data['national_id'].'] is already registered for a student in this school.';
        }

        if ($data['student_id_number'] !== '' && isset($existingStudentIds[static::normaliseIdentifier($data['student_id_number'])])) {
            $errors[] = 'Student ID ['.$data['student_id_number'].'] is already registered for a student in this school.';
        }

        $fingerprint = static::studentFingerprint($data['first_name'], $data['last_name'], $data['gender'], $data['date_of_birth'] !== '' ? $data['date_of_birth'] : null);
        if (isset($existingStudentFingerprints[$fingerprint])) {
            $errors[] = 'Duplicate student: '.$data['first_name'].' '.$data['last_name'].' ('.$data['gender'].', '.($data['date_of_birth'] !== '' ? $data['date_of_birth'] : 'no DOB').') is already registered in this school.';
        }

        $data['_course'] = $course;
        $data['_section'] = $section;
        $data['_academic_year'] = $year;

        return $errors;
    }

    /** Resolve the level letter for a course name, tolerating case/spacing variants. */
    protected static function levelSuffix(string $courseName): string
    {
        if (isset(Student::$levelSuffixes[$courseName])) {
            return Student::$levelSuffixes[$courseName];
        }

        $key = static::normaliseKey($courseName);

        foreach (Student::$levelSuffixes as $name => $suffix) {
            if (static::normaliseKey($name) === $key) {
                return $suffix;
            }
        }

        return 'X';
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

    /** Collapse repeated whitespace (including tabs/newlines) into single spaces and trim. */
    protected static function normaliseText(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * Forgiving lookup key: case, spacing and punctuation are ignored, so
     * "ECD  B", "ecd-b", "ECD_B" and "ECD B" all produce "ecdb".
     */
    protected static function normaliseKey(string $value): string
    {
        $value = mb_strtolower(static::normaliseText($value));

        return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', $value);
    }

    /**
     * Identifier key for duplicate checks only (never for storage): case and
     * all whitespace are ignored, so "r260001a" matches "R260001A" and
     * "63-123456 A 78" matches "63-123456A78".
     */
    protected static function normaliseIdentifier(string $value): string
    {
        return (string) preg_replace('/\s+/u', '', mb_strtoupper(trim($value)));
    }

    protected static function studentFingerprint(string $firstName, string $lastName, string $gender, ?string $dateOfBirth): string
    {
        return static::normaliseKey($firstName).'|'
            .static::normaliseKey($lastName).'|'
            .static::normaliseKey($gender).'|'
            .($dateOfBirth ?: '');
    }

    protected static function normaliseGender(string $value): string
    {
        $normalised = static::normaliseKey($value);
        $map = [
            'm' => 'male',
            'male' => 'male',
            'boy' => 'male',
            'man' => 'male',
            'f' => 'female',
            'female' => 'female',
            'girl' => 'female',
            'woman' => 'female',
            'o' => 'other',
            'other' => 'other',
            'others' => 'other',
        ];

        return $map[$normalised] ?? strtolower(static::normaliseText($value));
    }

    protected static function normaliseStatus(string $value): string
    {
        $normalised = static::normaliseKey($value);
        $map = [
            'active' => 'active',
            'enrolled' => 'active',
            'enrolment' => 'active',
            'enrollment' => 'active',
            'current' => 'active',
            'present' => 'active',
            'inschool' => 'active',
            'activestudent' => 'active',
            'currentstudent' => 'active',
            'inactive' => 'inactive',
            'inactivestudent' => 'inactive',
            'left' => 'inactive',
            'withdrawn' => 'inactive',
            'former' => 'inactive',
            'exited' => 'inactive',
            'notactive' => 'inactive',
            'suspended' => 'suspended',
            'expelled' => 'suspended',
            'expulsion' => 'suspended',
            'rusticated' => 'suspended',
            'graduated' => 'graduated',
            'graduate' => 'graduated',
            'alumni' => 'graduated',
            'completed' => 'graduated',
            'finished' => 'graduated',
        ];

        return $map[$normalised] ?? strtolower(static::normaliseText($value));
    }

    protected static function normaliseBoardingStatus(string $value): string
    {
        $normalised = static::normaliseKey($value);
        $map = [
            'd' => 'day_scholar',
            'day' => 'day_scholar',
            'dayscholar' => 'day_scholar',
            'daystudent' => 'day_scholar',
            'nonboarder' => 'day_scholar',
            'b' => 'boarder',
            'boarding' => 'boarder',
            'boarder' => 'boarder',
            'residential' => 'boarder',
            'resident' => 'boarder',
            'hostel' => 'boarder',
        ];

        return $map[$normalised] ?? strtolower(static::normaliseText($value));
    }

    protected static function normaliseBloodGroup(string $value): string
    {
        $normalised = strtoupper(static::normaliseText($value));
        $normalised = str_replace(['–', '—'], '-', $normalised);
        $normalised = (string) preg_replace('/[^A-Z0-9+\-]/', '', $normalised);

        // "O+ve" / "A-ve" is common in paper registers.
        $normalised = (string) preg_replace('/^((?:AB|A|B|O)[+\-])VE$/', '$1', $normalised);

        // A zero is a very common typo for the letter O in blood groups.
        if (str_starts_with($normalised, '0')) {
            $normalised = 'O'.substr($normalised, 1);
        }

        $map = [
            'A+' => 'A+', 'A-' => 'A-',
            'B+' => 'B+', 'B-' => 'B-',
            'AB+' => 'AB+', 'AB-' => 'AB-',
            'O+' => 'O+', 'O-' => 'O-',
            'APOS' => 'A+', 'ANEG' => 'A-',
            'BPOS' => 'B+', 'BNEG' => 'B-',
            'ABPOS' => 'AB+', 'ABNEG' => 'AB-',
            'OPOS' => 'O+', 'ONEG' => 'O-',
            'APOSITIVE' => 'A+', 'ANEGATIVE' => 'A-',
            'BPOSITIVE' => 'B+', 'BNEGATIVE' => 'B-',
            'ABPOSITIVE' => 'AB+', 'ABNEGATIVE' => 'AB-',
            'OPOSITIVE' => 'O+', 'ONEGATIVE' => 'O-',
        ];

        return $map[$normalised] ?? $normalised;
    }

    public static function isPlaceholderValue(string $value): bool
    {
        $normalised = mb_strtolower(trim($value));
        return in_array($normalised, ['n/a', 'na', 'nil', 'n\a', 'none', '-', 'null', 'unknown', ''], true);
    }

    /**
     * Parse the date formats people actually type, returning Y-m-d or null.
     *
     * Handles ISO dates, Excel serial numbers, and day-first dates such as
     * 14/05/2013 (which PHP would otherwise reject). Truly ambiguous values
     * like 05/06/2013 keep Carbon's default interpretation.
     */
    protected static function parseDateValue(string $raw): ?string
    {
        $raw = trim((string) $raw);

        if ($raw === '' || ctype_space($raw) || static::isPlaceholderValue($raw)) {
            return '';
        }

        // Handle backslashes and spaces in dates (e.g. 13\10\14 or 02 04 2013)
        $raw = str_replace(['\\', ' '], ['/', '/'], $raw);
        // Handle typos like letter 'l' instead of '1'
        $raw = str_ireplace('l', '1', $raw);
        $raw = (string) preg_replace('/[\/\-\.]+/', '/', $raw);

        if (preg_match('/^\d{8}$/', $raw) === 1) {
            $year = (int) substr($raw, 0, 4);
            $month = (int) substr($raw, 4, 2);
            $day = (int) substr($raw, 6, 2);

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // A bare year is common on paper registers (year of birth only).
        // Treat it as 1 January rather than letting Carbon invent a month/day.
        if (preg_match('/^(19|20)\d{2}$/', $raw) === 1) {
            return $raw.'-01-01';
        }

        if (is_numeric($raw)) {
            $value = (float) $raw;

            // Spreadsheet serial date (days since 1899-12-30). The range
            // excludes bare years such as "2026".
            if ($value >= 10000 && $value <= 100000) {
                $timestamp = (int) round(($value - 25569) * 86400);

                return Carbon::createFromTimestampUTC($timestamp)->toDateString();
            }
        }

        // Explicit YYYY-MM-DD (and / or . variants) must be a real calendar
        // date; Carbon would otherwise roll 2013-02-30 into March.
        if (preg_match('#^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})$#', $raw, $m) === 1) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];

            return checkdate($month, $day, $year)
                ? sprintf('%04d-%02d-%02d', $year, $month, $day)
                : null;
        }

        if (preg_match('#^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2})$#', $raw, $m) === 1) {
            $first = (int) $m[1];
            $second = (int) $m[2];
            $yy = (int) $m[3];
            $year = $yy < 50 ? 2000 + $yy : 1900 + $yy;

            if ($first > 12 || $second > 12) {
                if ($first > 12 && checkdate($second, $first, $year)) {
                    return sprintf('%04d-%02d-%02d', $year, $second, $first);
                }

                if ($second > 12 && checkdate($first, $second, $year)) {
                    return sprintf('%04d-%02d-%02d', $year, $first, $second);
                }

                return null;
            }

            if ($first > 0 && $second > 0 && checkdate($first, $second, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $first, $second);
            }
        }

        if (preg_match('#^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$#', $raw, $m) === 1) {
            $first = (int) $m[1];
            $second = (int) $m[2];
            $year = (int) $m[3];

            // Day-first (14/05/2013) and US month-first (05/14/2013) are both
            // unambiguous when one side is greater than 12. An impossible
            // value like 31/04/2013 is rejected rather than rolled over.
            if ($first > 12 || $second > 12) {
                if ($first > 12 && checkdate($second, $first, $year)) {
                    return sprintf('%04d-%02d-%02d', $year, $second, $first);
                }

                if ($second > 12 && checkdate($first, $second, $year)) {
                    return sprintf('%04d-%02d-%02d', $year, $first, $second);
                }

                return null;
            }

            if ($first === 0 || $second === 0 || ! checkdate($first, $second, $year)) {
                return null;
            }

            // Keep the historical month-first reading for ambiguous values.
            return sprintf('%04d-%02d-%02d', $year, $first, $second);
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
