<?php

declare(strict_types=1);

namespace Modules\Accounting\Contracts;

use App\Contracts\BaseEloquentContract;
use Illuminate\Database\Eloquent\Collection;

interface AccountingPeriodContract extends BaseEloquentContract
{
    /**
     * Get open periods.
     */
    public function getOpenPeriods(): Collection;

    /**
     * Get closed periods.
     */
    public function getClosedPeriods(): Collection;

    /**
     * Get period for a specific date.
     */
    public function getPeriodForDate(string $date): ?\Illuminate\Database\Eloquent\Model;

    /**
     * Get current period.
     */
    public function getCurrentPeriod(): ?\Illuminate\Database\Eloquent\Model;
}
