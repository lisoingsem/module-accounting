<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Modules\Accounting\Contracts\AccountContract;
use Modules\Accounting\Contracts\JournalEntryContract;
use Modules\Accounting\Enums\AccountType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntryLine;

final class ReportService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private readonly AccountContract $accountRepository,
        private readonly JournalEntryContract $journalEntryRepository
    ) {}

    /**
     * Get trial balance for date range.
     *
     * @return array<string, mixed>
     */
    public function getTrialBalance(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate ??= now()->startOfYear()->toDateString();
        $endDate ??= now()->toDateString();

        $accounts = $this->accountRepository->getActiveAccounts();
        $entries = $this->journalEntryRepository->getEntriesForDateRange($startDate, $endDate);

        $trialBalance = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $debits = $this->getAccountDebits($account, $entries);
            $credits = $this->getAccountCredits($account, $entries);
            $balance = $this->calculateAccountBalance($account, $debits, $credits);

            if ($debits > 0 || $credits > 0 || abs($balance) > 0.01) {
                $trialBalance[] = [
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'account_type' => $account->type,
                    'debits' => $debits,
                    'credits' => $credits,
                    'balance' => $balance,
                ];

                $totalDebits += $debits;
                $totalCredits += $credits;
            }
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'accounts' => $trialBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
        ];
    }

    /**
     * Get Profit & Loss statement.
     *
     * @return array<string, mixed>
     */
    public function getProfitAndLoss(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate ??= now()->startOfYear()->toDateString();
        $endDate ??= now()->toDateString();

        // Revenue accounts (type: revenue)
        $revenueAccounts = $this->accountRepository->getByType(AccountType::REVENUE);
        $totalRevenue = $this->calculateAccountsTotal($revenueAccounts, $startDate, $endDate);

        // Expense accounts (type: expense)
        $expenseAccounts = $this->accountRepository->getByType(AccountType::EXPENSE);
        $totalExpenses = $this->calculateAccountsTotal($expenseAccounts, $startDate, $endDate);

        $netIncome = $totalRevenue - $totalExpenses;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'revenue' => [
                'accounts' => $this->formatAccountsForReport($revenueAccounts, $startDate, $endDate),
                'total' => $totalRevenue,
            ],
            'expenses' => [
                'accounts' => $this->formatAccountsForReport($expenseAccounts, $startDate, $endDate),
                'total' => $totalExpenses,
            ],
            'net_income' => $netIncome,
        ];
    }

    /**
     * Get Balance Sheet.
     *
     * @return array<string, mixed>
     */
    public function getBalanceSheet(?string $asOfDate = null): array
    {
        $asOfDate ??= now()->toDateString();

        // Assets
        $assetAccounts = $this->accountRepository->getByType(AccountType::ASSET);
        $totalAssets = $this->calculateAccountsBalance($assetAccounts, $asOfDate);

        // Liabilities
        $liabilityAccounts = $this->accountRepository->getByType(AccountType::LIABILITY);
        $totalLiabilities = $this->calculateAccountsBalance($liabilityAccounts, $asOfDate);

        // Equity
        $equityAccounts = $this->accountRepository->getByType(AccountType::EQUITY);
        $totalEquity = $this->calculateAccountsBalance($equityAccounts, $asOfDate);

        // Retained Earnings (Revenue - Expenses)
        $pnl = $this->getProfitAndLoss(now()->startOfYear()->toDateString(), $asOfDate);
        $retainedEarnings = Arr::get($pnl, 'net_income');
        $totalEquity += $retainedEarnings;

        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;

        return [
            'as_of_date' => $asOfDate,
            'assets' => [
                'accounts' => $this->formatAccountsBalanceForReport($assetAccounts, $asOfDate),
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'accounts' => $this->formatAccountsBalanceForReport($liabilityAccounts, $asOfDate),
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'accounts' => $this->formatAccountsBalanceForReport($equityAccounts, $asOfDate),
                'retained_earnings' => $retainedEarnings,
                'total' => $totalEquity,
            ],
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'is_balanced' => abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01,
        ];
    }

    /**
     * Get account ledger (all transactions with running balance).
     *
     * @return array<string, mixed>
     */
    public function getAccountLedger(int $accountId, ?string $startDate = null, ?string $endDate = null): array
    {
        $account = $this->accountRepository->find($accountId);

        throw_unless($account, new InvalidArgumentException("Account not found: {$accountId}"));

        $startDate ??= now()->startOfYear()->toDateString();
        $endDate ??= now()->toDateString();

        // Get opening balance (before start date)
        $openingEntries = $this->journalEntryRepository->getEntriesForDateRange(
            now()->startOfYear()->toDateString(),
            date('Y-m-d', strtotime($startDate . ' -1 day'))
        );
        $openingDebits = $this->getAccountDebits($account, $openingEntries);
        $openingCredits = $this->getAccountCredits($account, $openingEntries);
        $openingBalance = $this->calculateAccountBalance($account, $openingDebits, $openingCredits);
        $openingBalance += $account->opening_balance;

        // Get all journal entry lines for this account in date range
        $entries = $this->journalEntryRepository->getEntriesForDateRange($startDate, $endDate);
        $entryIds = $entries->pluck('id')->toArray();

        $lines = JournalEntryLine::query()->where('account_id', $accountId)
            ->whereIn('journal_entry_id', $entryIds)
            ->with(['journalEntry'])
            ->orderBy('journal_entry_id')
            ->orderBy('id')
            ->get();

        $ledgerEntries = [];
        $runningBalance = $openingBalance;

        foreach ($lines as $line) {
            $entry = $line->journalEntry;
            $amount = (float) $line->amount;

            // Calculate running balance
            if ($line->isDebit()) {
                if ($account->getTypeEnum()->increasesWithDebit()) {
                    $runningBalance += $amount;
                } else {
                    $runningBalance -= $amount;
                }
            } elseif ($account->getTypeEnum()->increasesWithCredit()) {
                $runningBalance += $amount;
            } else {
                $runningBalance -= $amount;
            }

            $ledgerEntries[] = [
                'date' => $entry->entry_date,
                'entry_number' => $entry->entry_number,
                'description' => $line->description ?? $entry->description,
                'reference' => $entry->reference,
                'type' => $line->type,
                'debit' => $line->isDebit() ? $amount : 0,
                'credit' => $line->isCredit() ? $amount : 0,
                'balance' => $runningBalance,
                'entry_id' => $entry->id,
            ];
        }

        // Calculate closing balance
        $periodDebits = $this->getAccountDebits($account, $entries);
        $periodCredits = $this->getAccountCredits($account, $entries);
        $periodBalance = $this->calculateAccountBalance($account, $periodDebits, $periodCredits);
        $closingBalance = $openingBalance + $periodBalance;

        return [
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
            ],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'period_debits' => $periodDebits,
            'period_credits' => $periodCredits,
            'entries' => $ledgerEntries,
        ];
    }

    /**
     * Get account debits for date range.
     */
    private function getAccountDebits(Account $account, Collection $entries): float
    {
        $entryIds = $entries->pluck('id')->toArray();

        if (empty($entryIds)) {
            return 0;
        }

        return (float) JournalEntryLine::query()->where('account_id', $account->id)
            ->where('type', 'debit')
            ->whereIn('journal_entry_id', $entryIds)
            ->sum('amount');
    }

    /**
     * Get account credits for date range.
     */
    private function getAccountCredits(Account $account, Collection $entries): float
    {
        $entryIds = $entries->pluck('id')->toArray();

        if (empty($entryIds)) {
            return 0;
        }

        return (float) JournalEntryLine::query()->where('account_id', $account->id)
            ->where('type', 'credit')
            ->whereIn('journal_entry_id', $entryIds)
            ->sum('amount');
    }

    /**
     * Calculate account balance based on type.
     */
    private function calculateAccountBalance(Account $account, float $debits, float $credits): float
    {
        $typeEnum = $account->getTypeEnum();

        if ($typeEnum->increasesWithDebit()) {
            return $debits - $credits;
        }

        return $credits - $debits;
    }

    /**
     * Calculate total for accounts (revenue/expenses).
     */
    private function calculateAccountsTotal(Collection $accounts, string $startDate, string $endDate): float
    {
        $entries = $this->journalEntryRepository->getEntriesForDateRange($startDate, $endDate);
        $entryIds = $entries->pluck('id')->toArray();

        if (empty($entryIds)) {
            return 0;
        }

        $accountIds = $accounts->pluck('id')->toArray();

        $total = 0;
        foreach ($accounts as $account) {
            $typeEnum = $account->getTypeEnum();
            $debits = $this->getAccountDebits($account, $entries);
            $credits = $this->getAccountCredits($account, $entries);

            if ($typeEnum->increasesWithDebit()) {
                $total += $debits - $credits;
            } else {
                $total += $credits - $debits;
            }
        }

        return $total;
    }

    /**
     * Calculate account balance as of date.
     */
    private function calculateAccountsBalance(Collection $accounts, string $asOfDate): float
    {
        $entries = $this->journalEntryRepository->getEntriesForDateRange(
            now()->startOfYear()->toDateString(),
            $asOfDate
        );

        $total = 0;
        foreach ($accounts as $account) {
            $debits = $this->getAccountDebits($account, $entries);
            $credits = $this->getAccountCredits($account, $entries);
            $balance = $this->calculateAccountBalance($account, $debits, $credits);
            $total += $balance;
        }

        return $total;
    }

    /**
     * Format accounts for P&L report.
     *
     * @return array<int, array<string, mixed>>
     */
    private function formatAccountsForReport(Collection $accounts, string $startDate, string $endDate): array
    {
        $entries = $this->journalEntryRepository->getEntriesForDateRange($startDate, $endDate);
        $formatted = [];

        foreach ($accounts as $account) {
            $debits = $this->getAccountDebits($account, $entries);
            $credits = $this->getAccountCredits($account, $entries);
            $balance = $this->calculateAccountBalance($account, $debits, $credits);

            if (abs($balance) > 0.01) {
                $formatted[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => $balance,
                ];
            }
        }

        return $formatted;
    }

    /**
     * Format accounts balance for Balance Sheet.
     *
     * @return array<int, array<string, mixed>>
     */
    private function formatAccountsBalanceForReport(Collection $accounts, string $asOfDate): array
    {
        $entries = $this->journalEntryRepository->getEntriesForDateRange(
            now()->startOfYear()->toDateString(),
            $asOfDate
        );
        $formatted = [];

        foreach ($accounts as $account) {
            $debits = $this->getAccountDebits($account, $entries);
            $credits = $this->getAccountCredits($account, $entries);
            $balance = $this->calculateAccountBalance($account, $debits, $credits);

            if (abs($balance) > 0.01) {
                $formatted[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => $balance,
                ];
            }
        }

        return $formatted;
    }
}
