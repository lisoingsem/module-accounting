<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use App\Repositories\BaseEloquentRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Contracts\AccountingPeriodContract;
use Modules\Accounting\Models\AccountingPeriod;

final class AccountingPeriodRepository extends BaseEloquentRepository implements AccountingPeriodContract
{
    /**
     * Create a new instance of the repository.
     */
    public function __construct()
    {
        $this->model = new AccountingPeriod();
    }

    /**
     * Get open periods.
     */
    public function getOpenPeriods(): Collection
    {
        return $this->model->where('is_closed', false)->orderBy('start_date', 'desc')->get();
    }

    /**
     * Get closed periods.
     */
    public function getClosedPeriods(): Collection
    {
        return $this->model->where('is_closed', true)->orderBy('end_date', 'desc')->get();
    }

    /**
     * Get period for a specific date.
     */
    public function getPeriodForDate(string $date): ?Model
    {
        return $this->model
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    /**
     * Get current period.
     */
    public function getCurrentPeriod(): ?Model
    {
        $today = now()->toDateString();

        return $this->model
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('is_closed', false)
            ->first();
    }
}
