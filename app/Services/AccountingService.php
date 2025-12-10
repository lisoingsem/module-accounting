<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Contracts\AccountContract;
use Modules\Accounting\Contracts\AccountingPeriodContract;
use Modules\Accounting\Contracts\JournalEntryContract;
use Modules\Accounting\Enums\EntryStatus;
use Modules\Accounting\Enums\JournalEntryType;
use Modules\Accounting\Exceptions\UnbalancedJournalEntryException;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;

final class AccountingService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private readonly JournalEntryContract $journalEntryRepository,
        private readonly AccountContract $accountRepository,
        private readonly AccountingPeriodContract $periodRepository
    ) {}

    /**
     * Create a journal entry with lines.
     *
     * @param  array<string, mixed>  $entryData
     * @param  array<int, array<string, mixed>>  $linesData
     *
     * @throws UnbalancedJournalEntryException
     */
    public function createJournalEntry(array $entryData, array $linesData): JournalEntry
    {
        return DB::transaction(function () use ($entryData, $linesData): JournalEntry {
            // Validate balance
            $this->validateBalance($linesData);

            // Get or create period
            $period = $this->getOrCreatePeriod(Arr::get($entryData, 'entry_date', now()->toDateString()));

            // Create journal entry
            $entry = $this->journalEntryRepository->create([
                'entry_number' => Arr::get($entryData, 'entry_number', $this->journalEntryRepository->getNextEntryNumber()),
                'entry_date' => Arr::get($entryData, 'entry_date', now()->toDateString()),
                'type' => Arr::get($entryData, 'type', JournalEntryType::MANUAL->value),
                'status' => EntryStatus::DRAFT->value,
                'description' => Arr::get($entryData, 'description', ''),
                'reference' => Arr::get($entryData, 'reference', null),
                'period_id' => $period?->id,
                'created_by' => Auth::id(),
                'source_type' => Arr::get($entryData, 'source_type', null),
                'source_id' => Arr::get($entryData, 'source_id', null),
                'notes' => Arr::get($entryData, 'notes', null),
            ]);

            // Create journal entry lines
            $lineNumber = 1;
            foreach ($linesData as $lineData) {
                JournalEntryLine::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => Arr::get($lineData, 'account_id'),
                    'type' => Arr::get($lineData, 'type'), // 'debit' or 'credit'
                    'amount' => Arr::get($lineData, 'amount'),
                    'description' => Arr::get($lineData, 'description', null),
                    'reference' => Arr::get($lineData, 'reference', null),
                    'line_number' => $lineNumber++,
                ]);
            }

            return $entry->fresh(['lines.account']);
        });
    }

    /**
     * Post a journal entry (make it final).
     */
    public function postJournalEntry(JournalEntry $entry): JournalEntry
    {
        return DB::transaction(function () use ($entry): JournalEntry {
            if ($entry->isPosted()) {
                return $entry;
            }

            // Validate balance
            throw_unless($entry->isBalanced(), new UnbalancedJournalEntryException('Journal entry is not balanced. Debits must equal credits.'));

            // Update entry status
            $entry->update([
                'status' => EntryStatus::POSTED->value,
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);

            // Update account balances
            foreach ($entry->lines as $line) {
                $account = $line->account;
                if ($line->isDebit()) {
                    $account->increment('current_balance', $line->amount);
                } else {
                    $account->decrement('current_balance', $line->amount);
                }
            }

            return $entry->fresh(['lines.account']);
        });
    }

    /**
     * Reverse a posted journal entry.
     */
    public function reverseJournalEntry(JournalEntry $entry, ?string $description = null): JournalEntry
    {
        return DB::transaction(function () use ($entry, $description): JournalEntry {
            throw_unless($entry->isPosted(), new Exception('Only posted entries can be reversed.'));

            // Create reversing entry
            $reversingEntry = $this->createJournalEntry([
                'entry_date' => now()->toDateString(),
                'type' => JournalEntryType::MANUAL->value,
                'description' => $description ?? "Reversal of {$entry->entry_number}",
                'reference' => $entry->entry_number,
                'source_type' => JournalEntry::class,
                'source_id' => $entry->id,
            ], $this->buildReversingLines($entry));

            // Post the reversing entry
            $reversingEntry = $this->postJournalEntry($reversingEntry);

            // Mark original entry as reversed
            $entry->update([
                'status' => EntryStatus::REVERSED->value,
            ]);

            return $reversingEntry;
        });
    }

    /**
     * Create auto journal entry from Finance Income.
     */
    public function createEntryFromIncome(Model $income): JournalEntry
    {
        $account = $this->accountRepository->findByCode('4000'); // Revenue account
        $cashAccount = $this->accountRepository->findByCode('1000'); // Cash account

        throw_if( ! $account || ! $cashAccount, new Exception('Required accounts not found in chart of accounts.'));

        return $this->createJournalEntry([
            'entry_date' => $income->transaction_date ?? now()->toDateString(),
            'type' => JournalEntryType::AUTO->value,
            'description' => "Income: {$income->description}",
            'reference' => $income->reference ?? null,
            'source_type' => get_class($income),
            'source_id' => $income->id,
        ], [
            [
                'account_id' => $cashAccount->id,
                'type' => 'debit',
                'amount' => $income->amount,
                'description' => $income->description,
            ],
            [
                'account_id' => $account->id,
                'type' => 'credit',
                'amount' => $income->amount,
                'description' => $income->description,
            ],
        ]);
    }

    /**
     * Create auto journal entry from Finance Expense.
     */
    public function createEntryFromExpense(Model $expense): JournalEntry
    {
        $account = $this->accountRepository->findByCode('5000'); // Expense account
        $cashAccount = $this->accountRepository->findByCode('1000'); // Cash account

        throw_if( ! $account || ! $cashAccount, new Exception('Required accounts not found in chart of accounts.'));

        return $this->createJournalEntry([
            'entry_date' => $expense->transaction_date ?? now()->toDateString(),
            'type' => JournalEntryType::AUTO->value,
            'description' => "Expense: {$expense->description}",
            'reference' => $expense->reference ?? null,
            'source_type' => get_class($expense),
            'source_id' => $expense->id,
        ], [
            [
                'account_id' => $account->id,
                'type' => 'debit',
                'amount' => $expense->amount,
                'description' => $expense->description,
            ],
            [
                'account_id' => $cashAccount->id,
                'type' => 'credit',
                'amount' => $expense->amount,
                'description' => $expense->description,
            ],
        ]);
    }

    /**
     * Record income from Finance module (creates and posts entry).
     */
    public function recordIncome(Model $income): JournalEntry
    {
        $entry = $this->createEntryFromIncome($income);

        return $this->postJournalEntry($entry);
    }

    /**
     * Record expense from Finance module (creates and posts entry).
     */
    public function recordExpense(Model $expense): JournalEntry
    {
        $entry = $this->createEntryFromExpense($expense);

        return $this->postJournalEntry($entry);
    }

    /**
     * Validate that debits equal credits.
     *
     * @param  array<int, array<string, mixed>>  $linesData
     *
     * @throws UnbalancedJournalEntryException
     */
    private function validateBalance(array $linesData): void
    {
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($linesData as $line) {
            if ('debit' === Arr::get($line, 'type')) {
                $totalDebits += (float) $line['amount'];
            } else {
                $totalCredits += (float) $line['amount'];
            }
        }

        throw_if(abs($totalDebits - $totalCredits) >= 0.01, new UnbalancedJournalEntryException(
            "Journal entry is not balanced. Debits: {$totalDebits}, Credits: {$totalCredits}"
        ));
    }

    /**
     * Build reversing lines (swap debit/credit).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildReversingLines(JournalEntry $entry): array
    {
        $lines = [];

        foreach ($entry->lines as $line) {
            $lines[] = [
                'account_id' => $line->account_id,
                'type' => $line->isDebit() ? 'credit' : 'debit',
                'amount' => $line->amount,
                'description' => "Reversal: {$line->description}",
            ];
        }

        return $lines;
    }

    /**
     * Get or create period for date.
     */
    private function getOrCreatePeriod(string $date): ?Model
    {
        $period = $this->periodRepository->getPeriodForDate($date);

        if ( ! $period instanceof Model) {
            // Create a default period if none exists
            $startDate = now()->startOfYear()->toDateString();
            $endDate = now()->endOfYear()->toDateString();

            $period = $this->periodRepository->create([
                'name' => now()->format('Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_closed' => false,
            ]);
        }

        return $period;
    }
}
