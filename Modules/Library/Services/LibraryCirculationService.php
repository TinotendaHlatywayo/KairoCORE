<?php

declare(strict_types=1);

namespace Modules\Library\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Library\Models\LibraryBookCopy;
use Modules\Library\Models\LibraryIssue;

class LibraryCirculationService
{
    public function issueCopy(
        int $copyId,
        ?int $studentId,
        ?int $userId,
        int $issuerId,
        int $periodDays = 14
    ): LibraryIssue {
        if ($studentId === null && $userId === null) {
            throw new Exception('An issue checkout must be assigned to either a student or a staff user.');
        }

        return DB::transaction(function () use ($copyId, $studentId, $userId, $issuerId, $periodDays) {
            $copy = LibraryBookCopy::where('id', $copyId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($copy->status !== 'available') {
                throw new Exception("Book copy matches a '{$copy->status}' status context and is unavailable for issue.");
            }

            $issue = LibraryIssue::create([
                'library_book_copy_id' => $copy->id,
                'student_id' => $studentId,
                'user_id' => $userId,
                'issued_by_id' => $issuerId,
                'issued_at' => Carbon::today(),
                'due_at' => Carbon::today()->addDays($periodDays),
                'status' => 'issued',
            ]);

            $copy->update(['status' => 'issued']);

            return $issue;
        });
    }

    public function processReturn(int $issueId, ?string $notes = null): LibraryIssue
    {
        return DB::transaction(function () use ($issueId, $notes) {
            $issue = LibraryIssue::where('id', $issueId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($issue->status === 'returned') {
                throw new Exception('This loan record is already catalogued as returned.');
            }

            $today = Carbon::today();
            $fine = 0.00;

            if ($today->greaterThan($issue->due_at)) {
                $daysOverdue = $today->diffInDays($issue->due_at);
                $school = app('current_tenant');

                $dailyRate = isset($school->settings['daily_fine_rate'])
                    ? (float) $school->settings['daily_fine_rate']
                    : 0.50;

                $fine = $daysOverdue * $dailyRate;
            }

            $issue->update([
                'returned_at' => $today,
                'status' => 'returned',
                'fine_amount' => $fine,
                'fine_status' => $fine > 0.00 ? 'unpaid' : 'waived',
                'notes' => $notes,
            ]);

            $issue->copy->update(['status' => 'available']);

            return $issue;
        });
    }

    public function renewLoan(int $issueId, int $extendDays = 7): LibraryIssue
    {
        return DB::transaction(function () use ($issueId, $extendDays) {
            $issue = LibraryIssue::where('id', $issueId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($issue->status, ['issued', 'overdue'])) {
                throw new Exception('Only active or overdue status items qualify for standard renewal processing.');
            }

            $newDueDate = Carbon::parse($issue->due_at)->addDays($extendDays);

            $issue->update([
                'due_at' => $newDueDate,
                'renewals_count' => $issue->renewals_count + 1,
                'status' => 'issued',
            ]);

            return $issue;
        });
    }
}
