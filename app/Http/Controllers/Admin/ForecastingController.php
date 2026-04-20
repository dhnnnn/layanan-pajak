<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tax\GetTaxForecastAction;
use App\Http\Controllers\Controller;
use App\Models\SimpaduTarget;
use App\Models\UptAdditionalTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForecastingController extends Controller
{
    public function index(Request $request): View
    {
        // Ambil semua ayat yang tersedia dari target
        $availableAyat = SimpaduTarget::query()
            ->select('no_ayat', 'keterangan', 'year')
            ->orderByDesc('year')
            ->get()
            ->unique('no_ayat')
            ->sortBy('no_ayat')
            ->mapWithKeys(fn ($t) => [$t->no_ayat => $t->keterangan]);

        $selectedAyat = $request->query('ayat', 'all');

        return view('admin.forecasting.index', [
            'availableAyat' => $availableAyat,
            'selectedAyat' => $selectedAyat,
        ]);
    }

    public function data(Request $request, GetTaxForecastAction $getForecast): JsonResponse
    {
        $ayat = $request->query('ayat');

        if (! $ayat) {
            return response()->json(['error' => 'Parameter ayat diperlukan.'], 422);
        }

        $label = $ayat === 'all'
            ? 'Semua Jenis Pajak'
            : SimpaduTarget::query()
                ->where('no_ayat', $ayat)
                ->orderByDesc('year')
                ->value('keterangan') ?? $ayat;

        $result = $getForecast($ayat, $label);

        if ($result === null) {
            return response()->json([
                'error' => 'Data tidak tersedia atau forecasting service tidak dapat dijangkau.',
            ], 503);
        }

        // Tambahkan data target bulanan (spread dari tribulan ke per-bulan)
        $targetData = $this->buildMonthlyTargetData($ayat);
        $targetTambahanData = $this->buildMonthlyAdditionalTargetData($ayat);
        $result['target_bulanan'] = $targetData;
        $result['target_tambahan_bulanan'] = $targetTambahanData;

        return response()->json($result);
    }

    /**
     * Spread target tribulan ke data per-bulan.
     * Setiap bulan dalam tribulan mendapat 1/3 dari target tribulan tersebut.
     *
     * @return array<int, array{periode: string, nilai: float}>
     */
    private function buildMonthlyTargetData(string $ayat): array
    {
        $quarterMonths = [
            'q1' => [1, 2, 3],
            'q2' => [4, 5, 6],
            'q3' => [7, 8, 9],
            'q4' => [10, 11, 12],
        ];

        $query = $ayat === 'all'
            ? SimpaduTarget::query()->selectRaw('year, SUM(total_target) as total_target, AVG(q1_pct) as q1_pct, AVG(q2_pct) as q2_pct, AVG(q3_pct) as q3_pct, AVG(q4_pct) as q4_pct')->groupBy('year')
            : SimpaduTarget::query()->where('no_ayat', $ayat);

        $targets = $query->orderBy('year')->get();

        $result = [];
        foreach ($targets as $t) {
            $total = (float) $t->total_target;
            $qTargets = [
                'q1' => $total * ((float) $t->q1_pct / 100),
                'q2' => $total * (((float) $t->q2_pct - (float) $t->q1_pct) / 100),
                'q3' => $total * (((float) $t->q3_pct - (float) $t->q2_pct) / 100),
                'q4' => $total * (((float) $t->q4_pct - (float) $t->q3_pct) / 100),
            ];

            foreach ($quarterMonths as $q => $months) {
                $perMonth = $qTargets[$q] / 3;
                foreach ($months as $m) {
                    $result[] = [
                        'periode' => sprintf('%d-%02d', $t->year, $m),
                        'nilai' => round($perMonth, 2),
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Spread target tambahan per tribulan ke data per-bulan.
     *
     * @return array<int, array{periode: string, nilai: float}>
     */
    private function buildMonthlyAdditionalTargetData(string $ayat): array
    {
        $currentYear = (int) now()->year;

        $quarterMonths = [
            'q1' => [1, 2, 3],
            'q2' => [4, 5, 6],
            'q3' => [7, 8, 9],
            'q4' => [10, 11, 12],
        ];

        $query = UptAdditionalTarget::query()
            ->where('year', $currentYear)
            ->selectRaw('year, SUM(q1_additional) as q1, SUM(q2_additional) as q2, SUM(q3_additional) as q3, SUM(q4_additional) as q4')
            ->groupBy('year');

        if ($ayat !== 'all') {
            $query->where('no_ayat', $ayat);
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($rows as $r) {
            $qAdditionals = [
                'q1' => (float) $r->q1,
                'q2' => (float) $r->q2,
                'q3' => (float) $r->q3,
                'q4' => (float) $r->q4,
            ];

            foreach ($quarterMonths as $q => $months) {
                $perMonth = $qAdditionals[$q] / 3;
                foreach ($months as $m) {
                    $result[] = [
                        'periode' => sprintf('%d-%02d', $r->year, $m),
                        'nilai' => round($perMonth, 2),
                    ];
                }
            }
        }

        return $result;
    }
}
