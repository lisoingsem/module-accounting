<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\Accounting\Services\ReportService;

final class ReportController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    /**
     * Get trial balance.
     */
    public function trialBalance(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $trialBalance = $this->reportService->getTrialBalance($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $trialBalance,
        ]);
    }

    /**
     * Get profit and loss statement.
     */
    public function profitAndLoss(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $pnl = $this->reportService->getProfitAndLoss($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $pnl,
        ]);
    }

    /**
     * Get balance sheet.
     */
    public function balanceSheet(Request $request): JsonResponse
    {
        $asOfDate = $request->input('as_of_date');

        $balanceSheet = $this->reportService->getBalanceSheet($asOfDate);

        return response()->json([
            'success' => true,
            'data' => $balanceSheet,
        ]);
    }

    /**
     * Get account ledger.
     */
    public function ledger(Request $request, int $accountId): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        try {
            $ledger = $this->reportService->getAccountLedger($accountId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $ledger,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}
