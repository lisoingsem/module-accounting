<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use App\Repositories\BaseEloquentRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Contracts\AccountContract;
use Modules\Accounting\Enums\AccountType;
use Modules\Accounting\Models\Account;

final class AccountRepository extends BaseEloquentRepository implements AccountContract
{
    /**
     * Create a new instance of the repository.
     */
    public function __construct()
    {
        $this->model = new Account();
    }

    /**
     * Get accounts by type.
     */
    public function getByType(AccountType $type): Collection
    {
        return $this->model->where('type', $type->value)->get();
    }

    /**
     * Get root accounts (no parent).
     */
    public function getRootAccounts(): Collection
    {
        return $this->model->whereNull('parent_id')->orderBy('sort_order')->get();
    }

    /**
     * Get account by code.
     */
    public function findByCode(string $code): ?\Illuminate\Database\Eloquent\Model
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Get active accounts.
     */
    public function getActiveAccounts(): Collection
    {
        return $this->model->where('is_active', true)->orderBy('code')->get();
    }
}
