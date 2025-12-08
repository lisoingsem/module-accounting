<?php

declare(strict_types=1);

namespace Modules\Accounting\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Accounting\Contracts\AccountContract;
use Modules\Accounting\Contracts\AccountingPeriodContract;
use Modules\Accounting\Contracts\JournalEntryContract;
use Modules\Accounting\Repositories\AccountingPeriodRepository;
use Modules\Accounting\Repositories\AccountRepository;
use Modules\Accounting\Repositories\JournalEntryRepository;

final class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repository bindings.
     */
    public function register(): void
    {
        $repositories = [
            AccountContract::class => AccountRepository::class,
            AccountingPeriodContract::class => AccountingPeriodRepository::class,
            JournalEntryContract::class => JournalEntryRepository::class,
        ];

        // Bind all repositories
        foreach ($repositories as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
