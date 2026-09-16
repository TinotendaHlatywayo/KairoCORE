<?php

namespace Modules\Finance\Services;

use App\Services\Csv\CsvBulkService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Term;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceItem;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Students\Models\Student;

/**
 * Bulk migration of a school's historic financial records from Excel (XLSX)
 * or CSV. Charges are persisted as real Invoices so they flow through the
 * statement, invoicing and reporting engines unchanged, while payments,
 * refunds, waivers and carry-forward entries map onto the same records the
 * live billing flow uses.
 *
 * Accepted Transaction Type values:
 *   charge                -> new Invoice + InvoiceItem(s)
 *   payment               -> Payment via the shared settlement service
 *   refund                -> Payment (is_refund) that also reduces the bank balance
 *   waiver                -> discount applied onto a resolved invoice
 *   debit_carry_forward   -> "Balance Brought Forward" invoice (unpaid balance)
 *   credit_carry_forward  -> carried-over overpayment offset against open invoices
 */
class FinancialHistoryCsvService extends CsvBulkService
{
    public const TYPE_CHARGE = 'charge';

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_REFUND = 'refund';

    public const TYPE_WAIVER = 'waiver';

    public const TYPE_DEBIT_CARRY_FORWARD = 'debit_carry_forward';

    public const TYPE_CREDIT_CARRY_FORWARD = 'credit_carry_forward';

    /** Example student identifiers used by the downloadable template guide. */
    public const EXAMPLE_A = 'R260001A'; // Grade 7 learner (2026) — started ECD A in 2017

    public const EXAMPLE_B = 'R260001B'; // Grade 3 learner (2026) — started ECD A in 2022

    public const EXAMPLE_C = 'R260001C'; // Grade 1 learner (2026) — started ECD A in 2024

    public static function columns(): array
    {
        return [
            'student_id' => [
                'label' => __('Student ID'),
                'required' => true,
                'guesses' => ['Student ID', 'Student Id', 'Student Number', 'ID', 'Admission No', 'Admission Number'],
                'example' => 'R260001A',
            ],
            'type' => [
                'label' => __('Transaction Type'),
                'required' => true,
                'guesses' => ['Transaction Type', 'Record Type', 'Type'],
                'example' => 'payment',
                'in' => ['charge', 'payment', 'refund', 'waiver', 'debit_carry_forward', 'credit_carry_forward'],
            ],
            'date' => [
                'label' => __('Date'),
                'required' => true,
                'guesses' => ['Date', 'Transaction Date', 'Payment Date'],
                'example' => '2026-02-10',
                'date' => true,
            ],
            'description' => [
                'label' => __('Description / Fee Name'),
                'required' => false,
                'guesses' => ['Description', 'Fee Name', 'Narration', 'Details'],
                'example' => 'Tuition Fees',
            ],
            'amount' => [
                'label' => __('Amount (USD)'),
                'required' => true,
                'guesses' => ['Amount', 'Amount USD', 'Total', 'Value', 'Amount ($)'],
                'example' => '250.00',
            ],
            'receipt_number' => [
                'label' => __('Receipt Number'),
                'required' => false,
                'guesses' => ['Receipt Number', 'Receipt No', 'Receipt'],
                'example' => 'RCP-2026-0184',
            ],
            'reference_number' => [
                'label' => __('Reference Number'),
                'required' => false,
                'guesses' => ['Reference Number', 'Reference No', 'Reference'],
                'example' => '',
            ],
            'payment_method' => [
                'label' => __('Payment Method'),
                'required' => false,
                'guesses' => ['Payment Method', 'Method', 'Mode of Payment'],
                'example' => 'cash',
                'in' => ['cash', 'bank_transfer', 'mobile_money', 'card', 'other'],
            ],
            'invoice_number' => [
                'label' => __('Invoice Number'),
                'required' => false,
                'guesses' => ['Invoice Number', 'Invoice No', 'Invoice'],
                'example' => '',
            ],
            'academic_year' => [
                'label' => __('Academic Year'),
                'required' => false,
                'guesses' => ['Academic Year', 'Year'],
                'example' => '2026',
            ],
            'term' => [
                'label' => __('Term'),
                'required' => false,
                'guesses' => ['Term', 'Term Name'],
                'example' => 'Term 1',
            ],
            'notes' => [
                'label' => __('Notes'),
                'required' => false,
                'guesses' => ['Notes', 'Memo', 'Comment'],
                'example' => '',
            ],
        ];
    }

    public static function templateHeaders(): array
    {
        return array_column(static::columns(), 'label');
    }

    protected static function templateRows(): array
    {
        $rows = [];
        $sampleReceipt = 0;
        $nextReceipt = function (int $year) use (&$sampleReceipt): string {
            return 'RCP-'.$year.'-'.str_pad((string) (++$sampleReceipt + ($year - 2017) * 30), 4, '0', STR_PAD_LEFT);
        };

        $method = fn (int $term): string => [1 => 'cash', 2 => 'mobile_money', 3 => 'bank_transfer'][$term] ?? 'cash';

        $date = fn (int $year, int $term, bool $payment = false): string => $payment
            ? match ($term) {
                1 => $year.'-01-28', 2 => $year.'-06-01', 3 => $year.'-10-05', default => $year.'-01-28'
            }
        : match ($term) {
            1 => $year.'-01-15', 2 => $year.'-05-15', 3 => $year.'-09-15', default => $year.'-01-15'
        };

        $row = fn (string $id, string $type, int $year, int $term, string $description, string $amount, array $opts = []): array => [
            $id, $type, $opts['date'] ?? $date($year, $term, in_array($type, [self::TYPE_PAYMENT, self::TYPE_REFUND], true)),
            $description, $amount,
            $opts['receipt'] ?? '', $opts['reference'] ?? '', $opts['method'] ?? '', $opts['invoice'] ?? '',
            (string) $year, 'Term '.$term, $opts['notes'] ?? '',
        ];

        // Charge + payment rows for a single term.
        $termRow = function (string $id, int $year, int $term, float $fee, array $override = []) use ($row, $nextReceipt, $method): array {
            $payment = $override['pay'] ?? $fee;

            return [
                $row($id, self::TYPE_CHARGE, $year, $term, 'Tuition Fees', number_format($fee, 2, '.', '')),
                $row($id, self::TYPE_PAYMENT, $year, $term, '', number_format($payment, 2, '.', ''), [
                    'receipt' => $nextReceipt($year),
                    'method' => $method($term),
                    'notes' => $override['notes'] ?? '',
                ]),
            ];
        };

        // All three terms for a given year.
        $yearRows = function (string $id, int $year, float $fee, array $termOverrides = []) use ($termRow): array {
            $rows = [];
            foreach ([1, 2, 3] as $term) {
                $rows = array_merge($rows, $termRow($id, $year, $term, $fee, $termOverrides[$term] ?? []));
            }

            return $rows;
        };

        /*
        | -------------------------------------------------------------------------
        | EXAMPLE 1 — R260001A: Grade 7 learner in 2026, started ECD A in 2017.
        | A full termly history spanning every year in school. 2024 Term 3 is
        | under-paid and the balance is carried into 2025 as a debit carry
        | forward. An end-of-year 2026 refund closes the example.
        | -------------------------------------------------------------------------
        */
        $id = self::EXAMPLE_A;

        foreach ([2017 => 60, 2018 => 65, 2019 => 70, 2020 => 80, 2021 => 90, 2022 => 100, 2023 => 110] as $year => $fee) {
            $rows = array_merge($rows, $yearRows($id, $year, $fee));
        }

        // 2024 Term 3 — under-paid, 34.00 left unpaid.
        $rows = array_merge($rows, $yearRows($id, 2024, 120, [
            3 => ['pay' => 86.00, 'notes' => 'Part payment — unpaid 34.00 balance carried forward'],
        ]));

        // 2025 Term 1 — carry the 34.00 unpaid balance as its own invoice,
        // then bill Term 1 and pay both.
        $rows[] = $row($id, self::TYPE_DEBIT_CARRY_FORWARD, 2025, 1, 'Balance Brought Forward', '34.00', [
            'date' => '2025-01-05',
            'notes' => 'Debit carried forward from 2024 Term 3',
        ]);
        $rows[] = $row($id, self::TYPE_CHARGE, 2025, 1, 'Tuition Fees', '130.00', ['date' => '2025-01-15']);
        $rows[] = $row($id, self::TYPE_PAYMENT, 2025, 1, '', '34.00', [
            'date' => '2025-01-28',
            'receipt' => $nextReceipt(2025),
            'method' => 'cash',
        ]);
        $rows[] = $row($id, self::TYPE_PAYMENT, 2025, 1, '', '130.00', [
            'date' => '2025-01-28',
            'receipt' => $nextReceipt(2025),
            'method' => 'mobile_money',
        ]);

        foreach ([2, 3] as $term) {
            $rows = array_merge($rows, $termRow($id, 2025, $term, 130.00));
        }

        // 2026 — three full-term charges and payments.
        $rows = array_merge($rows, $yearRows($id, 2026, 140));

        // 2026 Term 3 — end-of-year refund.
        $rows[] = $row($id, self::TYPE_REFUND, 2026, 3, 'Refund of overpaid fees', '40.00', [
            'date' => '2026-12-04',
            'receipt' => 'REF-2026-0001',
            'method' => 'cash',
            'notes' => 'Year-end refund to guardian after reconciliation',
        ]);

        /*
        | -------------------------------------------------------------------------
        | EXAMPLE 2 — R260001B: Grade 3 learner in 2026, started ECD A in 2022.
        | Full termly history from 2022 onward. 2026 Term 1 includes a bursary
        | waiver that is applied to the invoice before the remaining balance
        | is paid.
        | -------------------------------------------------------------------------
        */
        $id = self::EXAMPLE_B;

        foreach ([2022 => 100, 2023 => 110, 2024 => 120, 2025 => 130] as $year => $fee) {
            $rows = array_merge($rows, $yearRows($id, $year, $fee));
        }

        // 2026 Term 1 — charge, then waiver, then payment of the balance.
        $rows = array_merge($rows, [
            $row($id, self::TYPE_CHARGE, 2026, 1, 'Tuition Fees', '140.00'),
            $row($id, self::TYPE_WAIVER, 2026, 1, 'Community Bursary (10%)', '20.00', [
                'date' => '2026-01-20',
                'notes' => 'Waiver applied to the Term 1 invoice',
            ]),
            $row($id, self::TYPE_PAYMENT, 2026, 1, '', '120.00', [
                'date' => '2026-01-28',
                'receipt' => $nextReceipt(2026),
                'method' => 'cash',
            ]),
        ]);

        foreach ([2, 3] as $term) {
            $rows = array_merge($rows, $termRow($id, 2026, $term, 140.00));
        }

        /*
        | -------------------------------------------------------------------------
        | EXAMPLE 3 — R260001C: Grade 1 learner in 2026, started ECD A in 2024.
        | History starts 2024. 2025 Term 3 is over-paid and the excess credit
        | is carried forward into 2026 at the start of the new academic year.
        | -------------------------------------------------------------------------
        */
        $id = self::EXAMPLE_C;

        $rows = array_merge($rows, $yearRows($id, 2024, 120));

        // 2025 — Term 3 over-paid by 15.00 (credit carried forward).
        $rows = array_merge($rows, $yearRows($id, 2025, 130, [
            3 => ['pay' => 145.00, 'notes' => 'Overpaid — 15.00 credit carried to 2026'],
        ]));

        // 2026 — apply the carried credit, then bill all three terms.
        $rows[] = $row($id, self::TYPE_CREDIT_CARRY_FORWARD, 2026, 1, 'Overpayment Credit Brought Forward', '15.00', [
            'date' => '2026-01-10',
            'notes' => 'Credit balance carried forward from 2025 Term 3 overpayment',
        ]);

        $rows = array_merge($rows, $yearRows($id, 2026, 140));

        return $rows;
    }

    public static function exportHeaders(): array
    {
        return [
            'Student ID', 'Full Name', 'Transaction Type', 'Date', 'Description',
            'Amount (USD)', 'Receipt Number', 'Reference Number', 'Payment Method',
            'Invoice Number', 'Academic Year', 'Term', 'Notes',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $invoices = Invoice::withoutTenantScope()
            ->with(['student', 'items', 'term'])
            ->where('school_id', $schoolId)
            ->orderBy('created_at')
            ->get();

        foreach ($invoices as $invoice) {
            if ($invoice->items->isEmpty()) {
                yield self::exportRow($invoice->student, static::TYPE_CHARGE, $invoice->created_at, $invoice->invoice_number, $invoice->subtotal_amount, null);

                continue;
            }

            foreach ($invoice->items as $item) {
                yield self::exportRow(
                    $invoice->student,
                    static::TYPE_CHARGE,
                    $invoice->created_at,
                    $invoice->invoice_number,
                    $item->amount,
                    $item->name,
                    $invoice->term?->name
                );
            }
        }
    }

    protected static function exportRow(?Student $student, string $type, $date, ?string $invoiceNumber, ?float $amount, ?string $description, ?string $term = null): array
    {
        return [
            $student?->student_id_number ?? '',
            $student?->full_name ?? '',
            $type,
            $date ? Carbon::parse($date)->toDateString() : '',
            $description ?? '',
            number_format((float) $amount, 2, '.', ''),
            '',
            '',
            '',
            $invoiceNumber ?? '',
            '',
            $term ?? '',
            '',
        ];
    }

    /**
     * @param  array<string, string|null>  $columnMap
     * @param  array{requester_id?: int|null}  $options
     * @return array{success: int, total: int, failures: array}
     */
    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null, array $options = []): array
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

        static::skipHeaderBlock($handle);

        $total = 0;
        while (fgetcsv($handle, 0, ',', escape: '\\') !== false) {
            $total++;
        }

        rewind($handle);
        static::skipHeaderBlock($handle);

        $students = Student::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn (Student $s): string => strtolower(trim((string) $s->student_id_number)));

        $studentsByAdmission = Student::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn (Student $s): string => strtolower(trim((string) $s->admission_number)));

        $academicYears = AcademicYear::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn (AcademicYear $y): string => strtolower(trim((string) $y->name)));

        $terms = Term::withoutTenantScope()->where('school_id', $schoolId)->get();

        $activeYear = AcademicYear::withoutTenantScope()->where('school_id', $schoolId)->where('is_active', true)->first();

        $columns = static::columns();
        $success = 0;
        $failures = [];
        $processed = 0;
        $rowNumber = 1;
        $requesterId = $options['requester_id'] ?? auth()->id();

        while (($row = fgetcsv($handle, 0, ',', escape: '\\')) !== false) {
            $rowNumber++;
            $processed++;
            $row = array_map('trim', $row);

            $data = array_fill_keys(array_keys($columns), '');

            foreach ($mappedIndexes as $key => $index) {
                $data[$key] = ($index !== null && isset($row[$index])) ? $row[$index] : '';
            }

            if (implode('', $data) === '') {
                $onProgress !== null && $onProgress($processed, $total, false, []);

                continue;
            }

            $errors = static::validateAndNormalize($data, $students, $studentsByAdmission, $academicYears, $terms, $activeYear);

            if (! empty($errors)) {
                $failures[] = ['row' => $rowNumber, 'errors' => $errors, 'data' => $data];
                $onProgress !== null && $onProgress($processed, $total, true, $errors);

                continue;
            }

            try {
                DB::transaction(function () use (&$data, $schoolId, $requesterId) {
                    static::persist($data, $schoolId, $requesterId);
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
     * Validate + normalise one row. Lookups are attached to $data as
     * '_student', '_academic_year', '_term', '_date', '_amount'.
     *
     * @return array<int, string>
     */
    protected static function validateAndNormalize(
        array &$data,
        Collection $students,
        Collection $studentsByAdmission,
        Collection $academicYears,
        Collection $terms,
        ?AcademicYear $activeYear,
    ): array {
        $errors = [];

        $data['student_id'] = trim($data['student_id']);
        $data['type'] = static::normaliseType($data['type'] ?? '');
        $data['description'] = trim($data['description']);
        $data['receipt_number'] = trim($data['receipt_number']);
        $data['reference_number'] = trim($data['reference_number']);
        $data['payment_method'] = strtolower(trim($data['payment_method']));
        $data['invoice_number'] = trim($data['invoice_number']);
        $data['academic_year'] = trim($data['academic_year']);
        $data['term'] = trim($data['term']);
        $data['notes'] = trim($data['notes']);

        if ($data['student_id'] === '') {
            $errors[] = 'Student ID is required (column empty or not mapped).';

            return $errors;
        }

        if (! in_array($data['type'], [static::TYPE_CHARGE, static::TYPE_PAYMENT, static::TYPE_REFUND, static::TYPE_WAIVER, static::TYPE_DEBIT_CARRY_FORWARD, static::TYPE_CREDIT_CARRY_FORWARD], true)) {
            $errors[] = 'Transaction Type must be one of: charge, payment, refund, waiver, debit_carry_forward, credit_carry_forward.';

            return $errors;
        }

        $student = $students[strtolower($data['student_id'])]
            ?? $studentsByAdmission[strtolower($data['student_id'])]
            ?? null;

        if (! $student) {
            $errors[] = 'No student was found with Student ID / Admission Number ['.$data['student_id'].'].';

            return $errors;
        }

        $data['date'] = static::toDate($data['date'] ?? '') ?? now()->toDateString();
        $data['amount'] = static::toDecimal($data['amount'], -1);

        if ($data['amount'] <= 0) {
            $errors[] = 'Amount must be a positive number (column empty or not mapped).';
        }

        if ($data['type'] === static::TYPE_CHARGE || $data['type'] === static::TYPE_DEBIT_CARRY_FORWARD) {
            if ($data['description'] === '') {
                $errors[] = 'Description / Fee Name is required for charge and debit_carry_forward records.';
            }
        }

        if ($data['type'] === static::TYPE_PAYMENT && empty($data['payment_method'])) {
            $data['payment_method'] = 'cash';
        }

        if ($data['payment_method'] !== '' && ! in_array($data['payment_method'], ['cash', 'bank_transfer', 'mobile_money', 'card', 'other', 'credit'], true)) {
            $errors[] = 'Payment Method must be one of: cash, bank_transfer, mobile_money, card, other.';
        }

        if (! empty($errors)) {
            return $errors;
        }

        $data['_student'] = $student;

        $year = null;
        if ($data['academic_year'] !== '') {
            $year = $academicYears[strtolower($data['academic_year'])] ?? null;
            if (! $year) {
                $errors[] = 'Academic Year ['.$data['academic_year'].'] was not found in this school. Available years: '.($academicYears->pluck('name')->implode(', ') ?: 'none').'.';
            }
        } else {
            $year = $activeYear;
        }

        if (! empty($errors)) {
            return $errors;
        }

        $data['_academic_year'] = $year;

        $term = null;
        if ($data['term'] !== '') {
            $resolved = $terms->filter(fn (Term $t): bool => strtolower(trim((string) $t->name)) === strtolower($data['term']));
            if ($year) {
                $resolved = $resolved->first(fn (Term $t): bool => (int) $t->academic_year_id === (int) $year->id);
            } else {
                $resolved = $resolved->first();
            }

            if ($resolved) {
                $term = $resolved;
            } else {
                $errors[] = 'Term ['.$data['term'].'] was not found'.($year ? ' for Academic Year ['.$year->name.']' : '').'. Available terms: '.($terms->pluck('name')->unique()->values()->implode(', ') ?: 'none').'.';
            }
        }

        if (! empty($errors)) {
            return $errors;
        }

        $data['_term'] = $term;

        return $errors;
    }

    /**
     * Public entry point for programmatic/manual entry. Accepts the same
     * normalised row the file import produces (with '_student', '_academic_year',
     * '_term', '_date', '_amount' attached) and persists it through the shared
     * transaction handlers so audit logging, ledger labels and money rules stay
     * identical whether a record came from a file upload or a form.
     */
    public static function persistRow(array $data, int $schoolId, ?int $requesterId = null, string $auditPrefix = 'import'): void
    {
        static::persist($data, $schoolId, $requesterId, $auditPrefix);
    }

    /**
     * Persist one normalised row as the matched record type.
     */
    protected static function persist(array $data, int $schoolId, ?int $requesterId, string $auditPrefix = 'import'): void
    {
        /** @var Student $student */
        $student = $data['_student'];
        $date = $data['date'];
        $amount = round((float) $data['amount'], 2);
        $year = $data['_academic_year'];
        $term = $data['_term'];

        $invoice = static::resolveInvoice($student, $data['invoice_number'], $data['type']);

        switch ($data['type']) {
            case static::TYPE_CHARGE:
                $invoice = static::createCharge($student, $schoolId, $year, $term, $data, $amount, $date, $auditPrefix);
                break;

            case static::TYPE_DEBIT_CARRY_FORWARD:
                $invoice = static::createDebitCarryForward($student, $schoolId, $year, $term, $data, $amount, $date, $auditPrefix);
                break;

            case static::TYPE_PAYMENT:
                static::recordPayment($student, $schoolId, $requesterId, $data, $amount, $date, $invoice, $auditPrefix);
                break;

            case static::TYPE_REFUND:
                static::recordRefund($student, $schoolId, $requesterId, $data, $amount, $date, $invoice, $auditPrefix);
                break;

            case static::TYPE_WAIVER:
                static::applyWaiver($student, $data, $amount, $invoice, $auditPrefix);
                break;

            case static::TYPE_CREDIT_CARRY_FORWARD:
                static::recordCreditCarryForward($student, $schoolId, $requesterId, $data, $amount, $date, $invoice, $auditPrefix);
                break;
        }
    }

    protected static function createCharge(Student $student, int $schoolId, ?AcademicYear $year, ?Term $term, array $data, float $amount, string $date, string $auditPrefix = 'import'): Invoice
    {
        $invoiceNumber = $data['invoice_number'] !== ''
            ? $data['invoice_number']
            : static::nextInvoiceNumber($schoolId, 'INV-', $date);

        $invoice = new Invoice([
            'school_id' => $schoolId,
            'student_id' => $student->id,
            'academic_year_id' => $year?->id,
            'term_id' => $term?->id,
            'invoice_number' => $invoiceNumber,
            'currency' => 'USD',
            'subtotal_amount' => $amount,
            'discount_amount' => 0,
            'waiver_details' => null,
            'total_amount' => $amount,
            'paid_amount' => 0,
            'balance_amount' => $amount,
            'status' => 'unpaid',
            'due_date' => Carbon::parse($date),
        ]);
        $invoice->created_at = Carbon::parse($date);
        $invoice->save();

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'fee_structure_id' => null,
            'name' => $data['description'] ?: 'Fees',
            'amount' => $amount,
        ]);

        FinanceAuditService::record(
            $student->id,
            $auditPrefix.'.charge',
            $invoice,
            null,
            ['invoice_number' => $invoiceNumber, 'amount' => $amount, 'date' => $date, 'description' => $data['description'], 'notes' => $data['notes']],
            $data['notes'] ?: null
        );

        return $invoice;
    }

    protected static function createDebitCarryForward(Student $student, int $schoolId, ?AcademicYear $year, ?Term $term, array $data, float $amount, string $date, string $auditPrefix = 'import'): Invoice
    {
        $invoiceNumber = $data['invoice_number'] !== ''
            ? $data['invoice_number']
            : static::nextInvoiceNumber($schoolId, 'CF-', $date);

        $invoice = new Invoice([
            'school_id' => $schoolId,
            'student_id' => $student->id,
            'academic_year_id' => $year?->id,
            'term_id' => $term?->id,
            'invoice_number' => $invoiceNumber,
            'currency' => 'USD',
            'subtotal_amount' => $amount,
            'discount_amount' => 0,
            'waiver_details' => null,
            'total_amount' => $amount,
            'paid_amount' => 0,
            'balance_amount' => $amount,
            'status' => 'unpaid',
            'due_date' => Carbon::parse($date),
        ]);
        $invoice->created_at = Carbon::parse($date);
        $invoice->save();

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'fee_structure_id' => null,
            'name' => $data['description'] ?: 'Balance Brought Forward',
            'amount' => $amount,
        ]);

        FinanceAuditService::record(
            $student->id,
            $auditPrefix.'.debit_carry_forward',
            $invoice,
            null,
            ['invoice_number' => $invoiceNumber, 'amount' => $amount, 'date' => $date, 'notes' => $data['notes']],
            $data['notes'] ?: null
        );

        return $invoice;
    }

    protected static function recordPayment(Student $student, int $schoolId, ?int $requesterId, array $data, float $amount, string $date, ?Invoice $invoice, string $auditPrefix = 'import'): void
    {
        if (! $invoice) {
            throw new \RuntimeException('No invoice could be resolved for this payment. Add an Invoice Number column, or ensure the student has an open (unpaid) invoice.');
        }

        PaymentSettlementService::settle($invoice, $amount, [
            'received_by_id' => $requesterId,
            'receipt_number' => $data['receipt_number'] ?: null,
            'reference_number' => $data['reference_number'] ?: null,
            'payment_method' => $data['payment_method'] ?: 'cash',
            'payment_date' => $date,
            'currency' => 'USD',
        ], PaymentSettlementService::MODE_CREDIT, self::defaultBank($schoolId)?->id);

        $latest = Payment::withoutTenantScope()->where('school_id', $schoolId)->latest('id')->first();

        FinanceAuditService::record(
            $student->id,
            $auditPrefix.'.payment',
            $latest,
            null,
            ['invoice_number' => $invoice->invoice_number, 'amount' => $amount, 'date' => $date, 'receipt_number' => $data['receipt_number'], 'method' => $data['payment_method'], 'notes' => $data['notes']],
            $data['notes'] ?: null
        );
    }

    protected static function recordRefund(Student $student, int $schoolId, ?int $requesterId, array $data, float $amount, string $date, ?Invoice $invoice, string $auditPrefix = 'import'): void
    {
        if (! $invoice) {
            throw new \RuntimeException('No invoice could be resolved for this refund. Add an Invoice Number column, or ensure the student has an invoice with payments recorded against it.');
        }

        $refundAmount = round(min($amount, max(0, (float) $invoice->paid_amount)), 2);

        if ($refundAmount <= 0) {
            throw new \RuntimeException('Refund could not be recorded: invoice ['.$invoice->invoice_number.'] has no paid amount to refund.');
        }

        $payment = Payment::create([
            'school_id' => $schoolId,
            'bank_account_id' => self::defaultBank($schoolId)?->id,
            'received_by_id' => $requesterId,
            'invoice_id' => $invoice->id,
            'receipt_number' => $data['receipt_number'] ?: 'REF-'.mt_rand(10000, 99999),
            'reference_number' => $data['reference_number'] ?: null,
            'amount' => -$refundAmount,
            'currency' => 'USD',
            'payment_method' => $data['payment_method'] ?: 'cash',
            'payment_date' => $date,
            'is_refund' => true,
            'excess_handling' => PaymentSettlementService::MODE_REFUND,
        ]);

        $invoice->paid_amount = round(max(0, (float) $invoice->paid_amount - $refundAmount), 2);
        $invoice->save();

        $bank = self::defaultBank($schoolId);
        if ($bank) {
            $bank->decrement('balance', $refundAmount);
        }

        FinanceAuditService::record(
            $student->id,
            $auditPrefix.'.refund',
            $payment,
            null,
            ['invoice_number' => $invoice->invoice_number, 'amount' => $refundAmount, 'date' => $date, 'receipt_number' => $payment->receipt_number, 'notes' => $data['notes']],
            $data['notes'] ?: null
        );
    }

    protected static function applyWaiver(Student $student, array $data, float $amount, ?Invoice $invoice, string $auditPrefix = 'import'): void
    {
        if (! $invoice) {
            throw new \RuntimeException('No invoice could be resolved for this waiver. Add an Invoice Number column, or ensure the student has an open (unpaid) invoice.');
        }

        $before = $invoice->refresh()->toArray();
        $newDiscount = round(min(
            (float) $invoice->subtotal_amount,
            (float) $invoice->discount_amount + $amount
        ), 2);

        $invoice->discount_amount = $newDiscount;
        $invoice->waiver_details = $data['description'] ?: ($invoice->waiver_details ?: 'Scholarship / Discount');
        $invoice->save();

        FinanceAuditService::record(
            $student->id,
            $auditPrefix.'.waiver',
            $invoice,
            self::auditSlice($before),
            self::auditSlice($invoice->refresh()->toArray()),
            $data['notes'] ?: null
        );
    }

    protected static function recordCreditCarryForward(Student $student, int $schoolId, ?int $requesterId, array $data, float $amount, string $date, ?Invoice $invoice, string $auditPrefix = 'import'): void
    {
        if (! $invoice) {
            throw new \RuntimeException('No invoice could be resolved for this carry-forward credit. Add an Invoice Number column, or ensure the student has at least one invoice.');
        }

        $open = static::resolveInvoice($student, '', static::TYPE_PAYMENT);
        $target = $open ?? $invoice;
        $balance = max(0, (float) $target->balance_amount);
        $applied = round(min($amount, $balance), 2);
        $remainder = round($amount - $applied, 2);

        $payment = Payment::create([
            'school_id' => $schoolId,
            'bank_account_id' => null,
            'received_by_id' => $requesterId,
            'invoice_id' => $target->id,
            'receipt_number' => $data['receipt_number'] ?: 'CREDIT-'.mt_rand(10000, 99999),
            'reference_number' => $data['reference_number'] ?: 'CARRY-FWD-'.$target->invoice_number,
            'amount' => $applied,
            'currency' => 'USD',
            'payment_method' => 'credit',
            'payment_date' => $date,
            'is_refund' => false,
            'excess_handling' => PaymentSettlementService::MODE_CREDIT,
        ]);

        if ($applied > 0) {
            $target->paid_amount = round((float) $target->paid_amount + $applied, 2);
            $target->save();
        }

        if ($remainder > 0) {
            $student->increment('credit_balance', $remainder);
        }

        FinanceAuditService::record(
            $student->id,
            $auditPrefix.'.credit_carry_forward',
            $payment,
            null,
            ['invoice_number' => $target->invoice_number, 'amount' => $amount, 'applied' => $applied, 'credit_carried' => $remainder, 'date' => $date, 'notes' => $data['notes']],
            $data['notes'] ?: null
        );
    }

    /**
     * Resolve which invoice a transaction line refers to. An explicitly given
     * invoice number wins; otherwise the student's oldest open (unpaid/partial)
     * invoice is picked so FIFO ordering is preserved.
     */
    protected static function resolveInvoice(Student $student, string $invoiceNumber, string $type): ?Invoice
    {
        if ($invoiceNumber !== '') {
            return Invoice::withoutTenantScope()
                ->where('student_id', $student->id)
                ->where('invoice_number', $invoiceNumber)
                ->first();
        }

        if ($type === static::TYPE_REFUND) {
            return Invoice::withoutTenantScope()
                ->where('student_id', $student->id)
                ->where('paid_amount', '>', 0)
                ->orderBy('id')
                ->first();
        }

        if ($type === static::TYPE_CREDIT_CARRY_FORWARD) {
            return Invoice::withoutTenantScope()
                ->where('student_id', $student->id)
                ->orderBy('id')
                ->first();
        }

        return Invoice::withoutTenantScope()
            ->where('student_id', $student->id)
            ->whereRaw('balance_amount > 0.001')
            ->orderBy('id')
            ->first();
    }

    public static function nextInvoiceNumber(int $schoolId, string $prefix, string $date): string
    {
        $count = Invoice::withoutTenantScope()->where('school_id', $schoolId)->count() + 1;

        // Make sure the number is unique even when the same file imports twice.
        do {
            $candidate = $prefix.Carbon::parse($date)->format('Y').'-'.str_pad($count, 5, '0', STR_PAD_LEFT);
            $count++;
        } while (Invoice::withoutTenantScope()->where('school_id', $schoolId)->where('invoice_number', $candidate)->exists());

        return $candidate;
    }

    public static function defaultBank(int $schoolId): ?SchoolBankAccount
    {
        return PaymentSettlementService::defaultBankAccount($schoolId);
    }

    protected static function normaliseType(string $value): string
    {
        $normalised = strtolower(trim($value));

        return match ($normalised) {
            'charge', 'invoice', 'bill', 'fees', 'fee', 'charge as per fee structure' => static::TYPE_CHARGE,
            'payment', 'pay', 'paid' => static::TYPE_PAYMENT,
            'refund', 'reversal', 'refunded' => static::TYPE_REFUND,
            'waiver', 'discount', 'concession', 'bursary', 'scholarship' => static::TYPE_WAIVER,
            'debit carry forward', 'debit', 'unpaid balance', 'balance brought forward', 'debit_carry_forward' => static::TYPE_DEBIT_CARRY_FORWARD,
            'credit carry forward', 'credit', 'overpayment', 'credit_carry_forward' => static::TYPE_CREDIT_CARRY_FORWARD,
            default => $normalised,
        };
    }

    public static function auditSlice(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'subtotal_amount', 'discount_amount', 'total_amount', 'paid_amount', 'balance_amount', 'status', 'waiver_details',
        ]));
    }
}
