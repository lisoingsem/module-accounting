<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

enum AccountType: string
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EQUITY = 'equity';
    case REVENUE = 'revenue';
    case EXPENSE = 'expense';

    /**
     * Get the normal balance side for this account type.
     */
    public function normalBalance(): string
    {
        return match ($this) {
            self::ASSET, self::EXPENSE => 'debit',
            self::LIABILITY, self::EQUITY, self::REVENUE => 'credit',
        };
    }

    /**
     * Check if this account type increases with debits.
     */
    public function increasesWithDebit(): bool
    {
        return 'debit' === $this->normalBalance();
    }

    /**
     * Check if this account type increases with credits.
     */
    public function increasesWithCredit(): bool
    {
        return 'credit' === $this->normalBalance();
    }
}
