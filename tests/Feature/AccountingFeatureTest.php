<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\AccountType;
use Modules\Accounting\Exceptions\UnbalancedJournalEntryException;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AccountingPeriod;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Services\AccountingService;
use Modules\Accounting\Services\ReportService;

test('can create chart of accounts', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $cashAccount = Account::create([
        'code' => '1000',
        'name' => 'Cash',
        'type' => AccountType::ASSET->value,
        'description' => 'Cash and cash equivalents',
        'is_active' => true,
        'opening_balance' => 0,
        'current_balance' => 0,
        'currency' => 'USD',
    ]);

    expect($cashAccount)->toBeInstanceOf(Account::class);
    expect($cashAccount->code)->toBe('1000');
    expect($cashAccount->type)->toBe(AccountType::ASSET->value);
    expect($cashAccount->getTypeEnum())->toBe(AccountType::ASSET);
    expect($cashAccount->getTypeEnum()->normalBalance())->toBe('debit');
})->uses(RefreshDatabase::class);

test('can create accounting period', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $period = AccountingPeriod::create([
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'is_closed' => false,
    ]);

    expect($period)->toBeInstanceOf(AccountingPeriod::class);
    expect($period->name)->toBe('2025');
    expect($period->isOpen())->toBeTrue();
    expect($period->containsDate('2025-06-15'))->toBeTrue();
    expect($period->containsDate('2024-12-31'))->toBeFalse();
})->uses(RefreshDatabase::class);

test('can create balanced journal entry', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $cashAccount = Account::create([
        'code' => '1000',
        'name' => 'Cash',
        'type' => AccountType::ASSET->value,
        'is_active' => true,
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);

    $revenueAccount = Account::create([
        'code' => '4000',
        'name' => 'Revenue',
        'type' => AccountType::REVENUE->value,
        'is_active' => true,
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);

    $accountingService = app(AccountingService::class);

    $entry = $accountingService->createJournalEntry([
        'entry_date' => now()->toDateString(),
        'description' => 'Test entry',
    ], [
        [
            'account_id' => $cashAccount->id,
            'type' => 'debit',
            'amount' => 1000.00,
            'description' => 'Cash received',
        ],
        [
            'account_id' => $revenueAccount->id,
            'type' => 'credit',
            'amount' => 1000.00,
            'description' => 'Revenue earned',
        ],
    ]);

    expect($entry)->toBeInstanceOf(JournalEntry::class);
    expect($entry->isDraft())->toBeTrue();
    expect($entry->isBalanced())->toBeTrue();
    expect($entry->lines)->toHaveCount(2);
})->uses(RefreshDatabase::class);

test('throws exception for unbalanced journal entry', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $cashAccount = Account::create([
        'code' => '1000',
        'name' => 'Cash',
        'type' => AccountType::ASSET->value,
        'is_active' => true,
    ]);

    $accountingService = app(AccountingService::class);

    expect(fn () => $accountingService->createJournalEntry([
        'entry_date' => now()->toDateString(),
        'description' => 'Unbalanced entry',
    ], [
        [
            'account_id' => $cashAccount->id,
            'type' => 'debit',
            'amount' => 1000.00,
        ],
        [
            'account_id' => $cashAccount->id,
            'type' => 'credit',
            'amount' => 500.00, // Unbalanced!
        ],
    ]))->toThrow(UnbalancedJournalEntryException::class);
})->uses(RefreshDatabase::class);

test('can post journal entry and update account balances', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $cashAccount = Account::create([
        'code' => '1000',
        'name' => 'Cash',
        'type' => AccountType::ASSET->value,
        'is_active' => true,
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);

    $revenueAccount = Account::create([
        'code' => '4000',
        'name' => 'Revenue',
        'type' => AccountType::REVENUE->value,
        'is_active' => true,
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);

    $accountingService = app(AccountingService::class);

    $entry = $accountingService->createJournalEntry([
        'entry_date' => now()->toDateString(),
        'description' => 'Revenue entry',
    ], [
        [
            'account_id' => $cashAccount->id,
            'type' => 'debit',
            'amount' => 1000.00,
        ],
        [
            'account_id' => $revenueAccount->id,
            'type' => 'credit',
            'amount' => 1000.00,
        ],
    ]);

    $postedEntry = $accountingService->postJournalEntry($entry);

    expect($postedEntry->isPosted())->toBeTrue();
    expect($postedEntry->posted_at)->not->toBeNull();

    $cashAccount->refresh();
    $revenueAccount->refresh();

    // Asset increases with debit
    expect($cashAccount->current_balance)->toBe(1000.00);
    // Revenue increases with credit
    expect($revenueAccount->current_balance)->toBe(-1000.00); // Credit balance is negative
})->uses(RefreshDatabase::class);

test('can generate trial balance', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $cashAccount = Account::create([
        'code' => '1000',
        'name' => 'Cash',
        'type' => AccountType::ASSET->value,
        'is_active' => true,
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);

    $revenueAccount = Account::create([
        'code' => '4000',
        'name' => 'Revenue',
        'type' => AccountType::REVENUE->value,
        'is_active' => true,
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);

    $accountingService = app(AccountingService::class);
    $reportService = app(ReportService::class);

    // Create and post entry
    $entry = $accountingService->createJournalEntry([
        'entry_date' => now()->toDateString(),
        'description' => 'Test revenue',
    ], [
        [
            'account_id' => $cashAccount->id,
            'type' => 'debit',
            'amount' => 1000.00,
        ],
        [
            'account_id' => $revenueAccount->id,
            'type' => 'credit',
            'amount' => 1000.00,
        ],
    ]);

    $accountingService->postJournalEntry($entry);

    $trialBalance = $reportService->getTrialBalance();

    expect($trialBalance)->toBeArray();
    expect($trialBalance['is_balanced'])->toBeTrue();
    expect($trialBalance['total_debits'])->toBe(1000.00);
    expect($trialBalance['total_credits'])->toBe(1000.00);
})->uses(RefreshDatabase::class);

test('can generate profit and loss statement', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $revenueAccount = Account::create([
        'code' => '4000',
        'name' => 'Sales Revenue',
        'type' => AccountType::REVENUE->value,
        'is_active' => true,
    ]);

    $expenseAccount = Account::create([
        'code' => '5000',
        'name' => 'Operating Expenses',
        'type' => AccountType::EXPENSE->value,
        'is_active' => true,
    ]);

    $reportService = app(ReportService::class);
    $pnl = $reportService->getProfitAndLoss();

    expect($pnl)->toBeArray();
    expect($pnl)->toHaveKey('revenue');
    expect($pnl)->toHaveKey('expenses');
    expect($pnl)->toHaveKey('net_income');
})->uses(RefreshDatabase::class);

test('can generate balance sheet', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $assetAccount = Account::create([
        'code' => '1000',
        'name' => 'Cash',
        'type' => AccountType::ASSET->value,
        'is_active' => true,
    ]);

    $liabilityAccount = Account::create([
        'code' => '2000',
        'name' => 'Accounts Payable',
        'type' => AccountType::LIABILITY->value,
        'is_active' => true,
    ]);

    $reportService = app(ReportService::class);
    $balanceSheet = $reportService->getBalanceSheet();

    expect($balanceSheet)->toBeArray();
    expect($balanceSheet)->toHaveKey('assets');
    expect($balanceSheet)->toHaveKey('liabilities');
    expect($balanceSheet)->toHaveKey('equity');
    expect($balanceSheet)->toHaveKey('is_balanced');
})->uses(RefreshDatabase::class);
