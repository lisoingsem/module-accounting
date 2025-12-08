<?php

declare(strict_types=1);

namespace Modules\Accounting\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Accounting\Listeners\RecordFinanceExpense;
use Modules\Accounting\Listeners\RecordFinanceIncome;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    /**
     * The event handler mappings for the application.
     *
     * @return array<string, array<int, string>>
     */
    protected function listen(): array
    {
        $listeners = [];

        // Only register Finance listeners if Finance module is enabled
        if (class_exists(\Modules\Finance\Events\IncomeCreated::class)) {
            $listeners[\Modules\Finance\Events\IncomeCreated::class] = [
                RecordFinanceIncome::class,
            ];
        }

        if (class_exists(\Modules\Finance\Events\ExpenseCreated::class)) {
            $listeners[\Modules\Finance\Events\ExpenseCreated::class] = [
                RecordFinanceExpense::class,
            ];
        }

        return $listeners;
    }
}
