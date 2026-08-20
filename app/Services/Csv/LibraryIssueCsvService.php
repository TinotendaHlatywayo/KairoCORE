<?php

namespace App\Services\Csv;

use App\Models\User;
use Modules\Library\Models\LibraryBookCopy;
use Modules\Library\Models\LibraryIssue;
use Modules\Students\Models\Student;

class LibraryIssueCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'copy_barcode' => [
                'label' => __('Copy Barcode'),
                'required' => true,
                'guesses' => ['Barcode', 'Copy Barcode'],
                'example' => 'BC-1-0001',
            ],
            'student' => [
                'label' => __('Student Admission No.'),
                'required' => false,
                'guesses' => ['Student', 'Admission No', 'Student Admission Number'],
                'example' => 'STU-2026-001',
            ],
            'staff' => [
                'label' => __('Staff Name'),
                'required' => false,
                'guesses' => ['Staff', 'Staff Name', 'User'],
                'example' => 'Tendai Mutasa',
            ],
            'issued_at' => [
                'label' => __('Issue Date'),
                'required' => true,
                'guesses' => ['Issue Date', 'Date Issued'],
                'example' => '2026-07-01',
                'date' => true,
            ],
            'due_at' => [
                'label' => __('Due Date'),
                'required' => true,
                'guesses' => ['Due Date', 'Return Due Date'],
                'example' => '2026-07-15',
                'date' => true,
            ],
            'returned_at' => [
                'label' => __('Returned Date'),
                'required' => false,
                'guesses' => ['Returned Date', 'Date Returned'],
                'example' => '2026-07-14',
                'date' => true,
            ],
            'status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status'],
                'example' => 'issued',
                'default' => 'issued',
                'in' => ['issued', 'returned', 'overdue', 'lost', 'damaged'],
            ],
            'fine_amount' => [
                'label' => __('Fine Amount'),
                'required' => false,
                'guesses' => ['Fine Amount', 'Fine'],
                'example' => '0',
                'default' => '0',
            ],
            'fine_status' => [
                'label' => __('Fine Status'),
                'required' => false,
                'guesses' => ['Fine Status'],
                'example' => 'unpaid',
                'default' => 'unpaid',
                'in' => ['unpaid', 'paid', 'waived'],
            ],
            'notes' => [
                'label' => __('Notes'),
                'required' => false,
                'guesses' => ['Notes'],
                'example' => 'Copy returned in good condition.',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Copy Barcode', 'Student Admission No.', 'Staff Name', 'Issue Date',
            'Due Date', 'Returned Date', 'Status', 'Fine Amount', 'Fine Status', 'Notes',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = LibraryIssue::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['copy', 'student', 'borrowerUser'])
            ->orderBy('id');

        $lastId = 0;

        do {
            $issues = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($issues->isEmpty()) {
                break;
            }

            foreach ($issues as $issue) {
                yield [
                    $issue->copy?->barcode,
                    $issue->student?->admission_number,
                    $issue->borrowerUser?->name,
                    optional($issue->issued_at)->format('Y-m-d'),
                    optional($issue->due_at)->format('Y-m-d'),
                    optional($issue->returned_at)->format('Y-m-d'),
                    $issue->status,
                    $issue->fine_amount,
                    $issue->fine_status,
                    $issue->notes,
                ];
            }

            $lastId = $issues->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $lookups = [
            'copies' => LibraryBookCopy::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn (LibraryBookCopy $c): string => strtolower(trim($c->barcode))),
            'students' => Student::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn (Student $s): string => strtolower(trim($s->admission_number))),
            'users' => User::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn (User $u): string => strtolower(trim($u->name))),
            'issuerId' => User::withoutTenantScope()->where('school_id', $schoolId)->orderBy('id')->value('id'),
        ];

        return static::runImport(
            $filePath,
            $schoolId,
            $columnMap,
            $onProgress,
            $lookups,
            fn (array &$data, array $lookups): array => static::validateAndNormalize($data, $lookups),
            function (array $data, int $schoolId, array &$lookups): void {
                static::createRow($data, $schoolId, $lookups);
            },
        );
    }

    protected static function validateAndNormalize(array &$data, array $lookups): array
    {
        $errors = [];

        $data['copy_barcode'] = trim($data['copy_barcode'] ?? '');
        if ($data['copy_barcode'] === '') {
            $errors[] = 'Copy Barcode is required (column empty or not mapped).';
        }

        $data['student'] = trim($data['student'] ?? '');
        $data['staff'] = trim($data['staff'] ?? '');

        if (($data['student'] === '') === ($data['staff'] === '')) {
            $errors[] = 'Exactly one borrower is required: provide either Student Admission No. or Staff Name, not both and not neither.';
        }

        if (empty($errors) && $data['copy_barcode'] !== '') {
            $copy = $lookups['copies'][strtolower($data['copy_barcode'])] ?? null;

            if (! $copy) {
                $errors[] = 'Copy Barcode ['.$data['copy_barcode'].'] was not found in this school.';
            } else {
                $data['_copy'] = $copy;
            }
        }

        $data['_student'] = null;
        $data['_user'] = null;

        if ($data['student'] !== '') {
            $student = $lookups['students'][strtolower($data['student'])] ?? null;

            if (! $student) {
                $errors[] = 'Student ['.$data['student'].'] was not found in this school by Admission No.';
            } else {
                $data['_student'] = $student;
            }
        }

        if ($data['staff'] !== '') {
            $user = $lookups['users'][strtolower($data['staff'])] ?? null;

            if (! $user) {
                $errors[] = 'Staff ['.$data['staff'].'] does not match any user account in this school.';
            } else {
                $data['_user'] = $user;
            }
        }

        $data['issued_at'] = static::toDate($data['issued_at'] ?? '');
        if ($data['issued_at'] === null) {
            $errors[] = 'Issue Date is required (column empty, not mapped, or not a valid date).';
        }

        $data['due_at'] = static::toDate($data['due_at'] ?? '');
        if ($data['due_at'] === null) {
            $errors[] = 'Due Date is required (column empty, not mapped, or not a valid date).';
        }

        if ($data['issued_at'] !== null && $data['due_at'] !== null && $data['issued_at'] > $data['due_at']) {
            $errors[] = 'Issue Date ['.$data['issued_at'].'] cannot be after Due Date ['.$data['due_at'].'].';
        }

        $data['returned_at'] = static::toDate($data['returned_at'] ?? '');

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] !== '' && ! in_array($data['status'], ['issued', 'returned', 'overdue', 'lost', 'damaged'], true)) {
            $errors[] = 'Status must be one of: issued, returned, overdue, lost, damaged.';
        }

        $data['fine_status'] = strtolower(trim($data['fine_status'] ?? ''));
        if ($data['fine_status'] !== '' && ! in_array($data['fine_status'], ['unpaid', 'paid', 'waived'], true)) {
            $errors[] = 'Fine Status must be one of: unpaid, paid, waived.';
        }

        $data['fine_amount'] = trim($data['fine_amount'] ?? '');
        if ($data['fine_amount'] !== '' && ! is_numeric($data['fine_amount'])) {
            $errors[] = 'Fine Amount ['.$data['fine_amount'].'] must be a number.';
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        LibraryIssue::create([
            'school_id' => $schoolId,
            'library_book_copy_id' => $data['_copy']->id,
            'student_id' => $data['_student']?->id,
            'user_id' => $data['_user']?->id,
            'issued_by_id' => $lookups['issuerId'],
            'issued_at' => $data['issued_at'],
            'due_at' => $data['due_at'],
            'returned_at' => $data['returned_at'],
            'status' => $data['status'] !== '' ? $data['status'] : 'issued',
            'fine_amount' => $data['fine_amount'] !== '' ? (float) $data['fine_amount'] : 0,
            'fine_status' => $data['fine_status'] !== '' ? $data['fine_status'] : 'unpaid',
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ]);
    }
}
