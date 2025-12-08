# Accounting Module - Double-Entry Ledger System

## ✅ Status: Production Ready

A comprehensive double-entry accounting system with full financial reporting capabilities.

---

## Features

### Core Functionality

- ✅ **Chart of Accounts** - Hierarchical account structure with standard codes
- ✅ **Journal Entries** - Manual and automatic journal entry creation
- ✅ **Double-Entry Ledger** - Automatic balance validation (debits = credits)
- ✅ **Accounting Periods** - Period management with open/closed status
- ✅ **Trial Balance** - Real-time trial balance generation
- ✅ **Profit & Loss** - Income statement generation
- ✅ **Balance Sheet** - Financial position statement
- ✅ **Account Ledger** - Detailed transaction history with running balances
- ✅ **Auto-Entry Integration** - Automatic journal entries from Finance module

---

## Installation

### 1. Run Migrations

```bash
php artisan module:migrate Accounting
```

### 2. Seed Chart of Accounts

```bash
php artisan module:seed Accounting --class=ChartOfAccountsSeeder
```

This will create 22 standard accounts:

- **Assets (1000s)**: Cash, Accounts Receivable, Equipment
- **Liabilities (2000s)**: Accounts Payable, Accrued Expenses
- **Equity (3000s)**: Capital, Retained Earnings
- **Revenue (4000s)**: Sales Revenue, Service Revenue
- **Expenses (5000s)**: COGS, Operating Expenses, Salaries, Rent, Utilities

---

## Usage

### Creating a Journal Entry

```php
use Modules\Accounting\Services\AccountingService;

$accountingService = app(AccountingService::class);

$entry = $accountingService->createJournalEntry([
    'entry_date' => now()->toDateString(),
    'description' => 'Revenue from sales',
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
        'description' => 'Sales revenue',
    ],
]);

// Post the entry (updates account balances)
$postedEntry = $accountingService->postJournalEntry($entry);
```

### Generating Financial Reports

```php
use Modules\Accounting\Services\ReportService;

$reportService = app(ReportService::class);

// Trial Balance
$trialBalance = $reportService->getTrialBalance(
    startDate: '2025-01-01',
    endDate: '2025-12-31'
);

// Profit & Loss
$pnl = $reportService->getProfitAndLoss(
    startDate: '2025-01-01',
    endDate: '2025-12-31'
);

// Balance Sheet
$balanceSheet = $reportService->getBalanceSheet(
    asOfDate: '2025-12-31'
);

// Account Ledger
$ledger = $reportService->getAccountLedger(
    accountId: $cashAccount->id,
    startDate: '2025-01-01',
    endDate: '2025-12-31'
);
```

### Reversing a Posted Entry

```php
$reversedEntry = $accountingService->reverseJournalEntry(
    $postedEntry,
    description: 'Reversal of incorrect entry'
);
```

---

## Auto-Entry Integration

The module automatically creates journal entries when Finance module events fire:

- **Income Created** → Auto-creates journal entry (Debit: Cash, Credit: Revenue)
- **Expense Created** → Auto-creates journal entry (Debit: Expense, Credit: Cash)

Entries are automatically posted and account balances are updated.

---

## Account Types

| Type      | Normal Balance | Increases With |
| --------- | -------------- | -------------- |
| Asset     | Debit          | Debit          |
| Liability | Credit         | Credit         |
| Equity    | Credit         | Credit         |
| Revenue   | Credit         | Credit         |
| Expense   | Debit          | Debit          |

---

## Database Structure

### Tables

- `accounting_accounts` - Chart of accounts
- `accounting_periods` - Accounting periods
- `accounting_journal_entries` - Journal entry headers
- `accounting_journal_entry_lines` - Debit/Credit lines

### Key Relationships

- Accounts have parent/child relationships (hierarchy)
- Journal entries belong to periods
- Journal entry lines belong to entries and accounts
- Entries can be linked to source models (polymorphic)

---

## API Endpoints

### Reports Controller

- `GET /api/accounting/reports/trial-balance` - Get trial balance
- `GET /api/accounting/reports/profit-loss` - Get P&L statement
- `GET /api/accounting/reports/balance-sheet` - Get balance sheet
- `GET /api/accounting/reports/ledger/{accountId}` - Get account ledger

---

## Testing

### Run Tests

```bash
# Unit tests
php artisan test modules/Accounting/tests/Unit

# Feature tests
php artisan test modules/Accounting/tests/Feature
```

### Verification

All core functionality has been verified:

- ✅ Chart of accounts creation
- ✅ Journal entry creation and posting
- ✅ Balance validation
- ✅ Financial report generation
- ✅ Auto-entry from Finance module

---

## Module Structure

```
modules/Accounting/
├── app/
│   ├── Contracts/          # Repository interfaces
│   ├── Enums/              # AccountType, JournalEntryType, EntryStatus
│   ├── Exceptions/         # UnbalancedJournalEntryException
│   ├── Http/Controllers/   # ReportController
│   ├── Listeners/          # Auto-entry listeners
│   ├── Models/             # Account, AccountingPeriod, JournalEntry, JournalEntryLine
│   ├── Providers/          # Service providers
│   ├── Repositories/       # Repository implementations
│   └── Services/           # AccountingService, ReportService
├── database/
│   ├── migrations/         # 4 migrations
│   └── seeders/            # ChartOfAccountsSeeder
└── tests/
    ├── Feature/            # Feature tests
    └── Unit/               # Unit tests
```

---

## Verification Status

✅ **All systems verified and operational:**

- Database migrations: ✅ Validated
- Chart of accounts: ✅ 22 accounts seeded
- Journal entries: ✅ Creating and posting working
- Balance validation: ✅ Working
- Financial reports: ✅ All reports generating correctly
- Auto-entry: ✅ Finance integration working
- Code quality: ✅ No linting errors, formatted

**The Accounting module is production-ready!**
