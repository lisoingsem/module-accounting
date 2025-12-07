# Accounting Module

Double-entry accounting system with general ledger, journal entries, chart of accounts, and financial reporting. Integrates with Payroll for automatic journal entry creation.

## Module Overview

The Accounting module provides complete accounting functionality including:
- Chart of Accounts management
- Journal Entries (double-entry bookkeeping)
- General Ledger
- Trial Balance
- Financial Statements (Balance Sheet, Income Statement)
- Account reconciliation
- Integration with Payroll module (receives journal entries)
- Integration with Finance module (shared concepts)

## What's Built

### Backend (PHP/Laravel)

**Structure:**
- ServiceProviders (AccountingServiceProvider, EventServiceProvider, RouteServiceProvider)
- Module configuration and routing setup
- Guard registration for Accounting module

**Planned Components:**
- Models: ChartOfAccount, JournalEntry, LedgerEntry, AccountType, FinancialPeriod
- Services: 
  - JournalEntryService (double-entry validation)
  - LedgerService (general ledger management)
  - FinancialReportService (trial balance, financial statements)
  - ReconciliationService (account reconciliation)
- Repositories: ChartOfAccountRepository, JournalEntryRepository, LedgerRepository

### Frontend (Vue/TypeScript)

**Planned Pages:**
- Chart of Accounts management
- Journal Entry creation and management
- General Ledger viewer
- Trial Balance report
- Financial Statements (Balance Sheet, Income Statement)
- Account reconciliation interface

### Translations

- Planned: `modules/Accounting/resources/lang/{en,km,zh}/` - Accounting-specific translations

## Features (Planned)

### Core Accounting Features

#### Chart of Accounts
- Account hierarchy (Assets, Liabilities, Equity, Income, Expenses)
- Account codes and numbering
- Account types and categories
- Account status (active/inactive)
- Parent-child account relationships

#### Journal Entries
- Double-entry bookkeeping (debits = credits)
- Entry validation (balanced entries)
- Entry types (manual, automatic, reversing)
- Entry numbering and references
- Entry descriptions and notes
- Multi-currency support (future)

#### General Ledger
- Account-level transaction history
- Running balances
- Period-based filtering
- Account reconciliation status

#### Trial Balance
- Period-based trial balance
- Debit/credit totals
- Account balances
- Export functionality

#### Financial Statements
- Balance Sheet (Assets, Liabilities, Equity)
- Income Statement (Revenue, Expenses, Net Income)
- Period-based reporting
- Comparative statements (future)
- Export to PDF/Excel

#### Account Reconciliation
- Bank reconciliation
- Account balance verification
- Reconciliation status tracking
- Outstanding items management

### Integration Features

#### Payroll Integration
Receives automatic journal entries from Payroll module:

```php
// Payroll module creates journal entries
Event::listen(PayrollProcessed::class, function ($event) {
    JournalEntryService::create([
        'debit_account' => 'Salary Expense',
        'credit_account' => 'Bank',
        'amount' => $event->totalSalary,
        'description' => 'Monthly payroll',
    ]);
});
```

**Typical Payroll Journal Entry:**
```
Dr Salary Expense          $X,XXX
Dr Employer NSSF Expense   $XXX
Cr Bank                   $X,XXX
Cr Employee NSSF          $XXX
Cr Tax Payable            $XXX
```

#### Finance Module Integration
Shares accounting concepts with Finance module:
- Account balances
- Transaction categorization
- Financial reporting

## Directory Structure

```
modules/Accounting/
├── app/
│   ├── Http/Controllers/
│   │   └── Dashboard/          # Planned: ChartOfAccount, JournalEntry, Report controllers
│   ├── Models/
│   │   ├── ChartOfAccount.php
│   │   ├── JournalEntry.php
│   │   ├── LedgerEntry.php
│   │   ├── AccountType.php
│   │   └── FinancialPeriod.php
│   ├── Services/
│   │   ├── JournalEntryService.php
│   │   ├── LedgerService.php
│   │   ├── FinancialReportService.php
│   │   └── ReconciliationService.php
│   ├── Repositories/            # Planned: ChartOfAccountRepository, JournalEntryRepository
│   └── Providers/
│       ├── AccountingServiceProvider.php
│       ├── EventServiceProvider.php
│       └── RouteServiceProvider.php
├── resources/
│   ├── lang/                    # Planned: Translations (en, km, zh)
│   └── pages/                   # Planned: Vue pages
├── routes/
│   ├── api.php
│   └── dashboard.php            # Planned: Dashboard routes
├── database/
│   ├── migrations/              # Planned: Accounting tables
│   ├── factories/              # Planned: Model factories
│   └── seeders/                # Planned: AccountingDatabaseSeeder
└── tests/
    ├── Feature/
    └── Unit/
```

## Integration with Other Modules

### Payroll Module

Accounting receives journal entries from Payroll:

```php
// Payroll module processes payroll
$payrollRun = $payrollService->processPayroll($period);

// Accounting module automatically receives journal entries
// via event listener
Event::listen(PayrollProcessed::class, function ($event) {
    $accountingService->createJournalEntry([
        'entries' => [
            ['account' => 'Salary Expense', 'debit' => $event->totalSalary],
            ['account' => 'Employer NSSF', 'debit' => $event->employerNSSF],
            ['account' => 'Bank', 'credit' => $event->netPay],
            ['account' => 'Employee NSSF', 'credit' => $event->employeeNSSF],
            ['account' => 'Tax Payable', 'credit' => $event->tax],
        ],
        'description' => 'Payroll for ' . $event->period->name,
        'reference' => 'PAYROLL-' . $event->payrollRun->id,
    ]);
});
```

### Finance Module

Accounting shares financial data with Finance module:

```php
// Get account balance from Accounting
$bankBalance = ChartOfAccount::where('code', '1000')
    ->first()
    ->currentBalance();

// Finance module can use this for expense tracking
Finance::setAccountBalance('bank', $bankBalance);
```

## Double-Entry Bookkeeping

### Principles

**Every transaction must have equal debits and credits:**

```php
// Valid journal entry
JournalEntry::create([
    'entries' => [
        ['account' => 'Cash', 'debit' => 1000],
        ['account' => 'Revenue', 'credit' => 1000],
    ],
    'description' => 'Cash sale',
]);

// Invalid (unbalanced)
// ❌ This will be rejected
JournalEntry::create([
    'entries' => [
        ['account' => 'Cash', 'debit' => 1000],
        ['account' => 'Revenue', 'credit' => 500], // Doesn't balance!
    ],
]);
```

### Account Types

**Debit Normal Accounts (increase with debit):**
- Assets
- Expenses

**Credit Normal Accounts (increase with credit):**
- Liabilities
- Equity
- Revenue

## Standards Compliance

- ✅ PHP: Strict types, final classes
- ✅ Repository-Service pattern (planned)
- ✅ Module guard registration
- ✅ Translation support structure
- ✅ Clean folder structure

## Development

```bash
# Module is created and ready for development
# Next steps:
# 1. Create migrations for ChartOfAccount, JournalEntry, LedgerEntry
# 2. Create models with relationships
# 3. Create JournalEntryService with double-entry validation
# 4. Create LedgerService for general ledger
# 5. Create FinancialReportService for reports
# 6. Create repositories and contracts
# 7. Create controllers and routes
# 8. Create Vue pages for frontend
# 9. Add translations
# 10. Write tests
```

## Status

**Current Status**: ✅ Module structure created, ready for implementation

**Next Steps**:
- [ ] Create database migrations
- [ ] Create models with relationships
- [ ] Implement double-entry validation
- [ ] Create repositories and services
- [ ] Create integration listeners for Payroll
- [ ] Create controllers
- [ ] Create Vue frontend pages
- [ ] Add translations
- [ ] Write tests

---

**Last Updated**: January 2025
**Status**: 🚧 In Development - Structure Ready

