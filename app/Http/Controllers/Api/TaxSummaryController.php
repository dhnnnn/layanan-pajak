<?php

namespace App\Http\Controllers\Api;

use App\Actions\Tax\GenerateTaxDashboardAction;
use App\Http\Controllers\Controller;
use App\Models\SimpaduMonthlyRealization;
use App\Models\SimpaduTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxSummaryController extends Controller
{
    /**
     * GET /api/tax-summary
     *
     * Query params:
     *   - year: integer (optional, returns all years if omitted)
     *
     * Response: array of yearly summaries with total_target, total_realization,
     * more_less, percentage — konsisten dengan data di dashboard admin.
     */
    public function __invoke(Request $request, GenerateTaxDashboardAction $generateDashboard): JsonResponse
    {
        $yearParam = $request->query('year');

        // Kumpulkan semua tahun yang ada datanya
        $availableYears = SimpaduTarget::query()->distinct()->pluck('year')
            ->merge(SimpaduMonthlyRealization::query()->distinct()->pluck('year'))
            ->unique()
            ->sortDesc()
            ->values();

        if ($yearParam && is_numeric($yearParam)) {
            // Single year — gunakan action yang sama dengan dashboard
            $year = (int) $yearParam;
            $result = $generateDashboard($year);
            $totals = $result['totals'];

            return response()->json([
                'year' => $year,
                'total_target' => (float) $totals['target'],
                'total_realization' => (float) $totals['realization'],
                'more_less' => (float) $totals['more_less'],
                'percentage' => (float) $totals['percentage'],
            ]);
        }

        // All years
        $breakdown = $availableYears->map(function (int $year) use ($generateDashboard): array {
            $totals = $generateDashboard($year)['totals'];

            return [
                'year' => $year,
                'total_target' => (float) $totals['target'],
                'total_realization' => (float) $totals['realization'],
                'more_less' => (float) $totals['more_less'],
                'percentage' => (float) $totals['percentage'],
            ];
        });

        return response()->json($breakdown->values());
    }
}
