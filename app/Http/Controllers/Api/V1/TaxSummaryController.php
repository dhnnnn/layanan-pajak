<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tax\GenerateTaxDashboardAction;
use App\Http\Controllers\Controller;
use App\Models\SimpaduMonthlyRealization;
use App\Models\SimpaduTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxSummaryController extends Controller
{
    /**
     * Total target dan realisasi pajak.
     *
     * Mengembalikan total target, realisasi, lebih/(kurang), dan persentase capaian.
     * Jika parameter year tidak diisi, mengembalikan semua tahun yang tersedia.
     *
     * @query int $year Tahun anggaran (contoh: 2026). Kosongkan untuk semua tahun.
     */
    public function __invoke(Request $request, GenerateTaxDashboardAction $generateDashboard): JsonResponse
    {
        $yearParam = $request->query('year');

        $availableYears = SimpaduTarget::query()->distinct()->pluck('year')
            ->merge(SimpaduMonthlyRealization::query()->distinct()->pluck('year'))
            ->unique()
            ->sortDesc()
            ->values();

        if ($yearParam && is_numeric($yearParam)) {
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
