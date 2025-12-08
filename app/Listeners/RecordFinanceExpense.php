<?php

declare(strict_types=1);

namespace Modules\Accounting\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Services\AccountingService;
use Modules\Finance\Events\ExpenseCreated;
use Throwable;

final class RecordFinanceExpense implements ShouldQueue
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
     * Records expense from Finance module in Accounting journal entries.
     */
    public function handle(ExpenseCreated $event): void
    {
        try {
            $expense = $event->expense;

            // Record expense (creates and posts journal entry)
            $entry = $this->accountingService->recordExpense($expense);

            Log::info('Finance expense recorded in Accounting', [
                'expense_id' => $expense->id,
                'amount' => $expense->amount,
                'currency' => $expense->currency,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to record Finance expense in Accounting', [
                'expense_id' => $event->expense->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }
}
