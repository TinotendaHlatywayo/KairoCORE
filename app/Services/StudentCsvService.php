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
        $filePath = static::resolveTempFilePath($filePath);
        $csvHeaders = static::readCsvHeaders($filePath);

        if (empty($csvHeaders)) {
            throw new \RuntimeException('The uploaded file has no readable header row. Download the template and use its exact column names.');
        }

        $delimiter = static::csvDelimiter($filePath);
        $mappedIndexes = static::mapColumnIndexes($csvHeaders, $columnMap);

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Could not open the uploaded file.');
        }

        static::skipHeaderBlock($handle, $delimiter, $csvHeaders); // skip header row

        // First pass: count data rows and learn this file's dominant grade,
        // stream, year and gender. Paper registers often leave those cells
        // blank on one or two rows, and copying the rest of the class is the
        // only safe correction when the value is missing altogether.
        $total = 0;
        $seedValues = ['course' => [], 'section' => [], 'academic_year' => [], 'gender' => []];

        while (($row = fgetcsv($handle, 0, $delimiter, escape: '\\')) !== false) {
            $total++;
            $row = array_map(fn ($value): string => trim((string) $value), $row);

            if (implode('', $row) === '') {
                continue;
            }

            $seed = [];
            foreach ($mappedIndexes as $key => $index) {
                $seed[$key] = ($index !== null && isset($row[$index])) ? $row[$index] : '';
            }

            $courseSeed = static::normaliseCourseName($seed['course'] ?? '');
            if ($courseSeed !== '') {
                $seedValues['course'][] = $courseSeed;
            }

            $sectionSeed = static::normaliseSectionName($seed['section'] ?? '', $courseSeed);
            if ($sectionSeed !== '') {
                $seedValues['section'][] = $sectionSeed;
            }

            $yearSeed = static::normaliseAcademicYearName($seed['academic_year'] ?? '');
            if ($yearSeed !== '') {
                $seedValues['academic_year'][] = $yearSeed;
            }

            $genderSeed = static::normaliseGender($seed['gender'] ?? '');
            if (in_array($genderSeed, ['male', 'female', 'other'], true)) {
                $seedValues['gender'][] = $genderSeed;
            }
        }

        $fileDefaults = [
            'course' => static::mostCommonValue($seedValues['course']),
            'section' => static::mostCommonValue($seedValues['section']),
            'academic_year' => static::mostCommonValue($seedValues['academic_year']),
            // Only trust a class-wide gender when every known row agrees.
            // A blank cell with no consensus falls back to "other".
            'gender' => count(array_unique($seedValues['gender'])) === 1
                ? static::mostCommonValue($seedValues['gender'])
                : '',
        ];

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
        $skipped = 0;
        $failures = [];
        $processed = 0;
        $activationSent = 0;
        $activationFailed = 0;
        $rowNumber = 1; // row 1 is the header

        while (($row = fgetcsv($handle, 0, $delimiter, escape: '\\')) !== false) {
            $rowNumber++;
            $processed++;
            $row = array_map(fn ($value): string => trim((string) $value), $row);

            $data = array_fill_keys(array_keys(static::columns()), '');

            foreach ($mappedIndexes as $key => $index) {
                $data[$key] = ($index !== null && isset($row[$index])) ? $row[$index] : '';
            }

            if (implode('', $data) === '') {
                $onProgress !== null && $onProgress($processed, $total, false, []);

                continue;
            }

            $errors = static::validateAndNormalize($data, $schoolId, $courses, $sectionsByCourse, $academicYears, $activeAcademicYear, $existingNationalIds, $existingStudentIds, $existingStudentFingerprints, $fileDefaults);

            if (! empty($errors)) {
                $failures[] = ['row' => $rowNumber, 'errors' => $errors, 'data' => $data];
                $onProgress !== null && $onProgress($processed, $total, true, $errors);

                continue;
            }

            if (! empty($data['_skip'])) {
                $skipped++;
                $onProgress !== null && $onProgress($processed, $total, false, []);

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

        return compact('success', 'skipped', 'total', 'failures');
    }

    /**
     * Validate one CSV row and normalize its values (dates, enums, lookups).
     * Reference lookups are attached to $data as '_course', '_section', '_academic_year'.
     *
     * @return array<int, string> List of human-readable errors (empty when valid).
     */
    protected static function validateAndNormalize(
        array &$data,
        int $schoolId,
        Collection $courses,
        Collection $sectionsByCourse,
        Collection $academicYears,
        ?AcademicYear $activeAcademicYear,
        Collection $existingNationalIds,
        Collection $existingStudentIds,
        Collection $existingStudentFingerprints,
        array $fileDefaults = [],
    ): array {
        $errors = [];

        // "N/A", "nil", "-" and a pasted block of spaces all mean "nothing was
        // entered" on a paper register. Clear them for every column; required
        // fields then fail with a clear message instead of importing "N/A".
        foreach ($data as $key => $value) {
            if (str_starts_with((string) $key, '_') || ! is_string($value)) {
                continue;
            }

            $data[$key] = static::isPlaceholderValue($value) ? '' : trim($value);
        }

        foreach ([
            'first_name', 'last_name', 'course', 'section', 'academic_year',
            'house', 'physical_address', 'emergency_contact_name', 'medical_notes',
        ] as $textField) {
            $data[$textField] = static::normaliseText($data[$textField] ?? '');
        }

        foreach (['phone', 'emergency_contact_phone'] as $phoneField) {
            // Spreadsheet phone columns are often stored as numbers, which
            // surface as "771234567.0" after a CSV round-trip.
            $data[$phoneField] = (string) preg_replace('/^(\+?\d[\d\s\-()]*)\.0+$/', '$1', trim($data[$phoneField] ?? ''));
        }

        $data['course'] = static::normaliseCourseName($data['course']);
        if ($data['course'] === '') {
            $data['course'] = (string) ($fileDefaults['course'] ?? '');
        }

        $data['section'] = static::normaliseSectionName($data['section'], $data['course']);
        if ($data['section'] === '') {
            $data['section'] = (string) ($fileDefaults['section'] ?? '');
        }

        $data['academic_year'] = static::normaliseAcademicYearName($data['academic_year']);
        if ($data['academic_year'] === '') {
            $data['academic_year'] = (string) ($fileDefaults['academic_year'] ?? '');
        }

        // Files such as "special class" only name the stream. If no grade is
        // present in the column or anywhere in the file, the special class is
        // itself the level.
        if ($data['course'] === '' && mb_strtolower($data['section']) === 'special class') {
            $data['course'] = 'Special Class';
        }

        // A teacher sometimes writes the full name in one column and leaves
        // the other blank. Split it either way round ("First Last" or, when
        // the register is surname-first, "Last First").
        if ($data['last_name'] === '' && str_contains($data['first_name'], ' ')) {
            $parts = preg_split('/\s+/', $data['first_name']);
            if (count($parts) > 1) {
                $data['last_name'] = array_pop($parts);
                $data['first_name'] = implode(' ', $parts);
            }
        } elseif ($data['first_name'] === '' && str_contains($data['last_name'], ' ')) {
            $parts = preg_split('/\s+/', $data['last_name']);
            if (count($parts) > 1) {
                $data['first_name'] = array_pop($parts);
                $data['last_name'] = implode(' ', $parts);
            }
        }

        if ($data['first_name'] === '') {
            $errors[] = 'First Name is required (column empty or not mapped).';
        }

        if ($data['last_name'] === '') {
            $errors[] = 'Last Name is required (column empty or not mapped).';
        }

        $data['gender'] = static::normaliseGender($data['gender'] ?? '');
        if ($data['gender'] === '' && in_array($fileDefaults['gender'] ?? '', ['male', 'female', 'other'], true)) {
            $data['gender'] = $fileDefaults['gender'];
        }
        if ($data['gender'] === '') {
            // A missing gender should not drop an otherwise valid child from
            // the roster. "other" is the honest placeholder until corrected.
            $data['gender'] = 'other';
        }
        if (! in_array($data['gender'], ['male', 'female', 'other'], true)) {
            $errors[] = 'Gender must be one of: male, female, other.';
        }

        $data['status'] = static::normaliseStatus($data['status'] ?? '');
        if ($data['status'] === '') {
            $data['status'] = 'active';
        }
        if (! in_array($data['status'], ['active', 'inactive', 'suspended', 'graduated'], true)) {
            $errors[] = 'Status must be one of: active, inactive, suspended, graduated.';
        }

        $data['boarding_status'] = static::normaliseBoardingStatus($data['boarding_status'] ?? '');
        if ($data['boarding_status'] === '') {
            $data['boarding_status'] = 'day_scholar';
        }
        if (! in_array($data['boarding_status'], ['day_scholar', 'boarder'], true)) {
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

                continue;
            }

            if ($parsed === '') {
                $data[$dateField] = '';

                continue;
            }

            if ($dateField === 'date_of_birth' && Carbon::parse($parsed)->isAfter(today())) {
                // A DOB in the future is almost always a mistyped decade
                // ("19/11/2026" for "19/11/2016").
                $corrected = Carbon::parse($parsed)->subYears(10)->toDateString();

                if (! Carbon::parse($corrected)->isAfter(today())) {
                    $parsed = $corrected;
                }
            }

            if ($dateField === 'admission_date') {
                // Registers copied forward sometimes keep an ancient admission
                // date ("14/01/2002" in a 2026 class). Keep the day/month and
                // move it into the class's academic year when it is implausible.
                $referenceYear = (int) ($data['academic_year'] ?: now()->year);
                $date = Carbon::parse($parsed);

                if ($referenceYear >= 1990 && $referenceYear <= 2100
                    && ($date->year < $referenceYear - 15 || $date->year > $referenceYear + 2)
                    && checkdate($date->month, $date->day, $referenceYear)) {
                    $parsed = sprintf('%04d-%02d-%02d', $referenceYear, $date->month, $date->day);
                }
            }

            $data[$dateField] = $parsed;
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

        $duplicateReason = null;

        if ($data['national_id'] !== '' && isset($existingNationalIds[static::normaliseIdentifier($data['national_id'])])) {
            $duplicateReason = 'National ID ['.$data['national_id'].'] is already registered for a student in this school.';
        }

        if ($duplicateReason === null && $data['student_id_number'] !== '' && isset($existingStudentIds[static::normaliseIdentifier($data['student_id_number'])])) {
            $duplicateReason = 'Student ID ['.$data['student_id_number'].'] is already registered for a student in this school.';
        }

        $fingerprint = static::studentFingerprint($data['first_name'], $data['last_name'], $data['gender'], $data['date_of_birth'] !== '' ? $data['date_of_birth'] : null);

        // Only use the name/gender/DOB fingerprint when a DOB is present; two
        // different children can share a name and gender, and silently
        // dropping one would be worse than importing both.
        if ($duplicateReason === null && $data['date_of_birth'] !== '' && isset($existingStudentFingerprints[$fingerprint])) {
            $duplicateReason = 'Duplicate student: '.$data['first_name'].' '.$data['last_name'].' ('.$data['gender'].', '.$data['date_of_birth'].') is already registered in this school.';
        }

        if ($duplicateReason !== null) {
            if (empty($errors)) {
                $data['_skip'] = true;
                $data['_skip_reason'] = $duplicateReason;

                return [];
            }

            $errors[] = $duplicateReason;
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

    /** @param array<int, string> $values */
    protected static function mostCommonValue(array $values): string
    {
        $values = array_values(array_filter(array_map(fn ($value): string => trim((string) $value), $values), fn (string $value): bool => $value !== ''));

        if ($values === []) {
            return '';
        }

        $counts = array_count_values($values);
        arsort($counts);

        return (string) array_key_first($counts);
    }

    /**
     * "Garde 1", "gGrade 5", "Grade5 5", "GRrade5", "4" and "ECD  B" all
     * occur in real registers. Collapse them to the names the school uses.
     */
    protected static function normaliseCourseName(string $value): string
    {
        $value = static::normaliseText($value);

        if ($value === '' || static::isPlaceholderValue($value)) {
            return '';
        }

        $upper = mb_strtoupper($value);
        $upper = (string) preg_replace('/\s+/', ' ', $upper);

        if (preg_match('/^E\s*C\s*D\s*([AB])?$/u', $upper, $m) === 1) {
            return trim('ECD '.($m[1] ?? ''));
        }

        if (preg_match('/^FORM\s*([1-6])$/u', $upper, $m) === 1) {
            return 'Form '.(string) (int) $m[1];
        }

        if (is_numeric($value)) {
            return 'Grade '.(string) (int) $value;
        }

        if (static::normaliseKey($value) === 'specialclass') {
            return 'Special Class';
        }

        // Tolerate spelling slips and a duplicated stream cell:
        // "Garde 1", "gGrade 5", "GRrade5", "Grade5 5", "Grade  6".
        if (preg_match('/^([A-Z\s]+?)\s*(\d+)?(?:\s+\d+)?$/u', $upper, $m) === 1) {
            $letters = (string) preg_replace('/[^A-Z]/', '', $m[1]);
            $looksLikeGrade = preg_match('/^G.*A.*D.*E$/', $letters) === 1
                || preg_match('/^G.*R.*A.*D.*E$/', $letters) === 1;

            if ($looksLikeGrade) {
                return isset($m[2]) && $m[2] !== '' ? 'Grade '.(string) (int) $m[2] : '';
            }
        }

        return $value;
    }

    /** Stream cell: trim, uppercase, and treat ECD numeric "1" as stream A. */
    protected static function normaliseSectionName(string $value, string $course = ''): string
    {
        $value = strtoupper(static::normaliseText($value));

        if ($value === '' || static::isPlaceholderValue($value)) {
            return '';
        }

        if (mb_strtolower($value) === 'special class') {
            return 'Special Class';
        }

        if (preg_match('/^[A-Z]$/u', $value) === 1) {
            return $value;
        }

        if (is_numeric($value) && str_starts_with(mb_strtoupper($course), 'ECD')) {
            return 'A';
        }

        return $value;
    }

    protected static function normaliseAcademicYearName(string $value): string
    {
        $value = trim($value);

        if ($value === '' || static::isPlaceholderValue($value)) {
            return '';
        }

        if (str_ends_with($value, '.0')) {
            $value = (string) (int) floatval($value);
        }

        return $value;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<string, string|null>  $columnMap
     * @return array<string, int|null>
     */
    protected static function mapColumnIndexes(array $headers, array $columnMap): array
    {
        $headerIndex = [];

        foreach ($headers as $index => $header) {
            $key = static::normaliseHeader((string) $header);

            if ($key !== '' && ! array_key_exists($key, $headerIndex)) {
                $headerIndex[$key] = $index;
            }
        }

        $mappedIndexes = [];

        foreach (static::columns() as $key => $column) {
            $header = $columnMap[$key] ?? null;
            $mappedIndexes[$key] = blank($header)
                ? null
                : ($headerIndex[static::normaliseHeader((string) $header)] ?? null);
        }

        return $mappedIndexes;
    }

    /** @param array<int, string|null> $row */
    protected static function valueForColumn(array $row, ?int $index): string
    {
        return $index !== null && isset($row[$index]) ? trim((string) $row[$index]) : '';
    }

    public static function streamKey(string $course, string $section): string
    {
        return static::normaliseKey($course).'|'.static::normaliseKey($section);
    }

    /**
     * Return every grade/stream pair referenced by the file that does not yet
     * exist for the school. The UI uses this to ask the user whether the
     * missing streams should be created before the import starts.
     *
     * @param  array<string, string|null>  $columnMap
     * @return array<string, array{course: string, section: string}>
     */
    public static function missingStreams(string $filePath, array $columnMap, int $schoolId): array
    {
        $path = static::resolveTempFilePath($filePath);
        $headers = static::readCsvHeaders($path);
        $delimiter = static::csvDelimiter($path);
        $mappedIndexes = static::mapColumnIndexes($headers, $columnMap);

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [];
        }

        static::skipHeaderBlock($handle, $delimiter, $headers);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter, escape: '\\')) !== false) {
            $row = array_map(fn ($value): string => trim((string) $value), $row);

            if (implode('', $row) === '') {
                continue;
            }

            $firstName = static::valueForColumn($row, $mappedIndexes['first_name'] ?? null);
            $lastName = static::valueForColumn($row, $mappedIndexes['last_name'] ?? null);

            if ($firstName === '' && $lastName === '') {
                continue;
            }

            $rows[] = [
                static::normaliseCourseName(static::valueForColumn($row, $mappedIndexes['course'] ?? null)),
                static::normaliseSectionName(static::valueForColumn($row, $mappedIndexes['section'] ?? null), static::valueForColumn($row, $mappedIndexes['course'] ?? null)),
            ];
        }

        fclose($handle);

        $defaultCourse = static::mostCommonValue(array_column($rows, 0));
        $defaultSection = static::mostCommonValue(array_column($rows, 1));

        $courses = Course::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn (Course $course): string => static::normaliseKey($course->name));

        $sectionsByCourse = Section::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->groupBy('course_id')
            ->map(fn (Collection $sections): Collection => $sections->keyBy(fn (Section $section): string => static::normaliseKey($section->name)));

        $missing = [];
        $seen = [];

        foreach ($rows as [$courseName, $sectionName]) {
            $courseName = $courseName !== '' ? $courseName : $defaultCourse;
            $sectionName = $sectionName !== '' ? $sectionName : $defaultSection;

            if ($courseName === '' && mb_strtolower($sectionName) === 'special class') {
                $courseName = 'Special Class';
            }

            if ($courseName === '' || $sectionName === '') {
                continue;
            }

            $key = static::streamKey($courseName, $sectionName);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $course = $courses->get(static::normaliseKey($courseName));

            if (! $course) {
                $missing[$key] = ['course' => $courseName, 'section' => $sectionName];

                continue;
            }

            $sections = $sectionsByCourse->get($course->id, collect());

            if (! $sections->has(static::normaliseKey($sectionName))) {
                $missing[$key] = ['course' => $courseName, 'section' => $sectionName];
            }
        }

        return $missing;
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
            'femal' => 'female',
            'famale' => 'female',
            'femail' => 'female',
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
     * 14/05/2013 (which PHP would otherwise reject). Ambiguous values like
     * 05/06/2013 are read day-first, matching the registers this is used for.
     */
    protected static function parseDateValue(string $raw): ?string
    {
        $raw = trim((string) $raw);

        if ($raw === '' || ctype_space($raw) || static::isPlaceholderValue($raw)) {
            return '';
        }

        // A full timestamp copied out of a spreadsheet ("2020-08-01 00:00:00")
        // only needs its date part.
        if (preg_match('/^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})(?:[ T]\d{1,2}:\d{2}(?::\d{2})?)?$/', $raw, $m) === 1) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];

            return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
        }

        // A stray leading letter is a common handwriting/typing slip
        // ("l12-05-2015"). Strip it only when digits follow immediately.
        $raw = (string) preg_replace('/^\s*[lIi|]+(?=\d)/', '', $raw);

        // Only apply letter-for-digit repairs to values that are otherwise a
        // date made of digits and separators, so "July" is never mangled.
        $dateCharacters = '0123456789 oOlIi|/\\.,-';

        if ($raw !== '' && strspn($raw, $dateCharacters) === strlen($raw)) {
            $raw = str_ireplace(['o', 'l', 'i', '|'], ['0', '1', '1', '1'], $raw);
        }

        // Backslashes, spaces, dashes and dots all act as separators; trailing
        // punctuation (e.g. "14/01/2002/") is ignored.
        $raw = str_replace(['\\', ' ', '-', '.'], '/', $raw);
        $raw = trim($raw, '/');
        $raw = (string) preg_replace('#/+#', '/', $raw);

        // Eight bare digits: YYYYMMDD, or DDMMYYYY when the first four digits
        // cannot be a year.
        if (preg_match('/^(\d{8})$/', $raw, $m) === 1) {
            $digits = $m[1];
            $year = (int) substr($digits, 0, 4);
            $month = (int) substr($digits, 4, 2);
            $day = (int) substr($digits, 6, 2);

            if ($year >= 1900 && $year <= 2100 && checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }

            $day = (int) substr($digits, 0, 2);
            $month = (int) substr($digits, 2, 2);
            $year = (int) substr($digits, 4, 4);

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

        // Two- or three-digit years: "13\01\218" means 2018, not year 218.
        if (preg_match('#^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{1,3})$#', $raw, $m) === 1) {
            $first = (int) $m[1];
            $second = (int) $m[2];
            $year = (int) $m[3];

            if ($year < 100) {
                $year = $year < 50 ? 2000 + $year : 1900 + $year;
            } elseif ($year < 1000) {
                $short = $year % 100;
                $year = $short < 50 ? 2000 + $short : 1900 + $short;
            }

            return static::makeDateFromParts($first, $second, $year);
        }

        // Four-digit year: day-first when unambiguous, and also for ambiguous
        // values because school registers in this region use DD/MM/YYYY.
        if (preg_match('#^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$#', $raw, $m) === 1) {
            return static::makeDateFromParts((int) $m[1], (int) $m[2], (int) $m[3]);
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function makeDateFromParts(int $first, int $second, int $year): ?string
    {
        if ($first > 12 && $second <= 12) {
            return checkdate($second, $first, $year)
                ? sprintf('%04d-%02d-%02d', $year, $second, $first)
                : null;
        }

        if ($second > 12 && $first <= 12) {
            return checkdate($first, $second, $year)
                ? sprintf('%04d-%02d-%02d', $year, $first, $second)
                : null;
        }

        if ($first > 0 && $second > 0 && checkdate($second, $first, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $second, $first);
        }

        if ($first > 0 && $second > 0 && checkdate($first, $second, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $first, $second);
        }

        return null;
    }
}
