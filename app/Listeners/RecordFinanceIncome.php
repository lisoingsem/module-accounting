<?php

declare(strict_types=1);

namespace Modules\Accounting\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Services\AccountingService;
use Modules\Finance\Events\IncomeCreated;
use Throwable;

final class RecordFinanceIncome implements ShouldQueue
{
    public string $queue = 'accounting';

    public int $tries = 3;

    /**
     * Create a new listener instance.
     */
    public function __construct(
        private readonly AccountingService $accountingService
    ) {}

    /**
     * Handle the event.
     *
     * Records income from Finance module in Accounting journal entries.
     */
    public function handle(IncomeCreated $event): void
    {
        try {
            $income = $event->income;

            // Record income (creates and posts journal entry)
            $entry = $this->accountingService->recordIncome($income);

            Log::info('Finance income recorded in Accounting', [
                'income_id' => $income->id,
                'amount' => $income->amount,
                'currency' => $income->currency,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to record Finance income in Accounting', [
                'income_id' => $event->income->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }
}
