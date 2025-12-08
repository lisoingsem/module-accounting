<?php

declare(strict_types=1);

use Modules\Accounting\Enums\AccountType;

test('account type enum returns correct normal balance', function (): void {
    expect(AccountType::ASSET->normalBalance())->toBe('debit');
    expect(AccountType::EXPENSE->normalBalance())->toBe('debit');
    expect(AccountType::LIABILITY->normalBalance())->toBe('credit');
    expect(AccountType::EQUITY->normalBalance())->toBe('credit');
    expect(AccountType::REVENUE->normalBalance())->toBe('credit');
});

test('account type enum correctly identifies increase direction', function (): void {
    expect(AccountType::ASSET->increasesWithDebit())->toBeTrue();
    expect(AccountType::EXPENSE->increasesWithDebit())->toBeTrue();
    expect(AccountType::LIABILITY->increasesWithCredit())->toBeTrue();
    expect(AccountType::EQUITY->increasesWithCredit())->toBeTrue();
    expect(AccountType::REVENUE->increasesWithCredit())->toBeTrue();
});
