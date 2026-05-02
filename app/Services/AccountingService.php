<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AccountingService
{
    public static function post(array $lines, $reference)
    {
        DB::transaction(function () use ($lines, $reference) {

            $entry = JournalEntry::create([
                'date' => now(),
                'reference_type' => $reference['type'],
                'reference_id' => $reference['id'],
                'created_by' => auth()->id()
            ]);

            foreach ($lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                ]);
            }
        });
    }
}
