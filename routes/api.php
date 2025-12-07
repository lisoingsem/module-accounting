<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\AccountingController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function (): void {
    Route::apiResource('accountings', AccountingController::class)->names('accounting');
});
