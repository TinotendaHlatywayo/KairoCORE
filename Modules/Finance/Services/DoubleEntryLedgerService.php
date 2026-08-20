<?php

namespace Modules\Finance\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\JournalEntry;
use Modules\Finance\Models\JournalLineItem;

class DoubleEntryLedgerService
{
    /**
     * Post a balanced double-entry journal entry.
     *
     * @param  string  $entryDate  (Y-m-d)
     * @param  array  $lines  [['account_id' => X, 'debit' => Y, 'credit' => Z, 'memo' => '...'], ...]
     *
     * @throws Exception
     */
    public function postJournalEntry(int $schoolId, string $entryDate, string $narration, array $lines, ?string $referenceNumber = null): JournalEntry
    {
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        // Verify mathematical balance (allowing tiny rounding tolerance)
        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new Exception("Unbalanced journal entry: Total Debits ({$totalDebit}) must equal Total Credits ({$totalCredit}).");
        }

        return DB::transaction(function () use ($schoolId, $entryDate, $narration, $lines, $referenceNumber) {
            $entry = JournalEntry::create([
                'school_id' => $schoolId,
                'entry_date' => $entryDate,
                'reference_number' => $referenceNumber,
                'narration' => $narration,
                'status' => 'posted',
                'user_id' => auth()->id(),
            ]);

            foreach ($lines as $line) {
                JournalLineItem::create([
                    'school_id' => $schoolId,
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            return $entry;
        });
    }
}
