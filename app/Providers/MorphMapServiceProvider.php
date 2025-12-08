<?php

declare(strict_types=1);

namespace Modules\Accounting\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AccountingPeriod;
use Modules\Accounting\Models\JournalEntry;

final class MorphMapServiceProvider extends ServiceProvider
{
    /**
     * Register polymorphic relation mappings.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'accounting-account' => Account::class,
            'accounting-period' => AccountingPeriod::class,
            'accounting-journal-entry' => JournalEntry::class,
        ]);
    }
}
