<?php

declare(strict_types=1);

namespace Modules\Accounting\Contracts;

use App\Contracts\BaseEloquentContract;
use Illuminate\Database\Eloquent\Collection;

interface JournalEntryContract extends BaseEloquentContract
{
    /**
     * Get next entry number.
     */
    public function getNextEntryNumber(): string;

    /**
     * Get posted entries.
     */
    public function getPostedEntries(): Collection;

    /**
     * Get draft entries.
     */
    public function getDraftEntries(): Collection;

    /**
     * Get entries for period.
     */
    public function getEntriesForPeriod(int $periodId): Collection;

    /**
     * Get entries for date range.
     */
    public function getEntriesForDateRange(string $startDate, string $endDate): Collection;
}
