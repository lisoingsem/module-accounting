<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\AccountingController;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::resource('accountings', AccountingController::class)->names('accounting');
});
