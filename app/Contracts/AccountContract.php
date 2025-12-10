<?php

declare(strict_types=1);

namespace Modules\Accounting\Contracts;

use App\Contracts\BaseEloquentContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Enums\AccountType;

interface AccountContract extends BaseEloquentContract
{
    /**
     * Get accounts by type.
     */
    public function getByType(AccountType $type): Collection;

    /**
     * Get root accounts (no parent).
     */
    public function getRootAccounts(): Collection;

    /**
     * Get account by code.
     */
    public function findByCode(string $code): ?Model;

    /**
     * Get active accounts.
     */
    public function getActiveAccounts(): Collection;
}
