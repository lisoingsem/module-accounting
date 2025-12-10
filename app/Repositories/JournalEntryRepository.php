<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use App\Repositories\BaseEloquentRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Modules\Accounting\Contracts\JournalEntryContract;
use Modules\Accounting\Enums\EntryStatus;
use Modules\Accounting\Models\JournalEntry;

final class JournalEntryRepository extends BaseEloquentRepository implements JournalEntryContract
{
    /**
     * Create a new instance of the repository.
     */
    public function __construct()
    {
        $this->model = new JournalEntry();
    }

    /**
     * Get next entry number.
     */
    public function getNextEntryNumber(): string
    {
        $year = now()->format('Y');
        $pattern = 'JE-' . $year . '-%';

        // Get all entries for this year
        $entries = $this->model
            ->where('entry_number', 'like', $pattern)
            ->pluck('entry_number')
            ->toArray();

        $maxSequence = 0;

        foreach ($entries as $entryNumber) {
            if (preg_match('/JE-(\d{4})-(\d+)/', $entryNumber, $matches)) {
                $sequence = (int) Arr::get($matches, 2);
                $maxSequence = max($maxSequence, $sequence);
            }
        }

        $nextSequence = $maxSequence + 1;

        return 'JE-' . $year . '-' . mb_str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get posted entries.
     */
    public function getPostedEntries(): Collection
    {
        return $this->model
            ->where('status', EntryStatus::POSTED->value)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Get draft entries.
     */
    public function getDraftEntries(): Collection
    {
        return $this->model
            ->where('status', EntryStatus::DRAFT->value)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Get entries for period.
     */
    public function getEntriesForPeriod(int $periodId): Collection
    {
        return $this->model
            ->where('period_id', $periodId)
            ->where('status', EntryStatus::POSTED->value)
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Get entries for date range.
     */
    public function getEntriesForDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->where('status', EntryStatus::POSTED->value)
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }
}
