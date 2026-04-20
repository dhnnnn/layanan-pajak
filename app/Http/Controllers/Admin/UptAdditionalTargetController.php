<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tax\GetTaxForecastAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUptAdditionalTargetRequest;
use App\Models\SimpaduTarget;
use App\Models\UptAdditionalTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class UptAdditionalTargetController extends Controller
{
    public function create(Request $request): View
    {
        $availableAyat = SimpaduTarget::query()
            ->select('no_ayat', 'keterangan', 'year')
            ->orderByDesc('year')
            ->get()
            ->unique('no_ayat')
            ->sortBy('no_ayat')
            ->mapWithKeys(fn ($t) => [$t->no_ayat => $t->keterangan]);

        $currentYear = (int) now()->year;
        $currentQuarter = (int) ceil(now()->month / 3);

        // Prefill jika edit
        $existing = null;
        if ($request->filled('no_ayat')) {
            $existing = UptAdditionalTarget::query()
                ->where('no_ayat', $request->query('no_ayat'))
                ->where('year', $currentYear)
                ->first();
        }

        // Ambil pct per tribulan untuk jenis pajak yang dipilih (default rata)
        $pctPerQ = [1 => 25.0, 2 => 25.0, 3 => 25.0, 4 => 25.0];
        $selectedAyat = $request->query('no_ayat', $existing?->no_ayat);
        if ($selectedAyat) {
            $targetData = SimpaduTarget::query()
                ->where('no_ayat', $selectedAyat)
                ->where('year', $currentYear)
                ->first();
            if ($targetData) {
                $q1 = (float) $targetData->q1_pct;
                $q2 = (float) $targetData->q2_pct;
                $q3 = (float) $targetData->q3_pct;
                $q4 = (float) $targetData->q4_pct;
                $pctPerQ = [
                    1 => $q1,
                    2 => $q2 - $q1,
                    3 => $q3 - $q2,
                    4 => $q4 - $q3,
                ];
            }
        }

        // Ambil target awal untuk jenis pajak yang dipilih (untuk kalkulasi % kenaikan)
        $baseTargetForAyat = 0.0;
        if ($selectedAyat) {
            $baseTargetForAyat = (float) (SimpaduTarget::query()
                ->where('no_ayat', $selectedAyat)
                ->where('year', $currentYear)
                ->value('total_target') ?? 0);
        }

        return view('admin.upt-additional-targets.create', compact(
            'availableAyat',
            'currentYear',
            'currentQuarter',
            'existing',
            'pctPerQ',
            'baseTargetForAyat',
        ));
    }

    public function store(StoreUptAdditionalTargetRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $currentYear = (int) now()->year;
        $currentQuarter = (int) ceil(now()->month / 3);

        $total = (float) $data['additional_target'];
        $startQ = (int) $data['start_quarter'];

        $target = SimpaduTarget::query()
            ->where('no_ayat', $data['no_ayat'])
            ->where('year', $currentYear)
            ->first();

        // Distribusi ikut proporsi pct per tribulan aktif (bukan rata)
        $quarters = $this->distributeByPct($total, $startQ, $target);

        UptAdditionalTarget::query()->updateOrCreate(
            ['no_ayat' => $data['no_ayat'], 'year' => $currentYear],
            [
                'additional_target' => $total,
                'start_quarter' => $startQ,
                'q1_additional' => $quarters[1],
                'q2_additional' => $quarters[2],
                'q3_additional' => $quarters[3],
                'q4_additional' => $quarters[4],
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]
        );

        Cache::forget("dashboard:tax:{$currentYear}");

        return redirect()
            ->route('admin.tax-targets.report', ['year' => $currentYear])
            ->with('success', 'Target tambahan APBD berhasil disimpan.');
    }

    public function preview(Request $request): JsonResponse
    {
        $noAyat = $request->query('no_ayat');
        $additionalTarget = (float) $request->query('additional_target', 0);
        $currentYear = (int) now()->year;
        $currentQuarter = (int) ceil(now()->month / 3);

        if (! $noAyat || $additionalTarget <= 0) {
            return response()->json(['error' => 'Parameter tidak lengkap.'], 422);
        }

        $target = SimpaduTarget::query()
            ->where('no_ayat', $noAyat)
            ->where('year', $currentYear)
            ->first();

        if (! $target) {
            return response()->json(['error' => 'Data target tidak ditemukan.'], 404);
        }

        $totalTarget = (float) $target->total_target;

        // q_pct adalah kumulatif, hitung target per tribulan dari selisih
        $pcts = [
            1 => (float) $target->q1_pct,
            2 => (float) $target->q2_pct,
            3 => (float) $target->q3_pct,
            4 => (float) $target->q4_pct,
        ];
        $originalTargets = [
            1 => $totalTarget * ($pcts[1] / 100),
            2 => $totalTarget * (($pcts[2] - $pcts[1]) / 100),
            3 => $totalTarget * (($pcts[3] - $pcts[2]) / 100),
            4 => $totalTarget * (($pcts[4] - $pcts[3]) / 100),
        ];

        $additionalPerQ = $this->distributeByPct($additionalTarget, $currentQuarter, $target);

        $quarters = [];
        for ($q = 1; $q <= 4; $q++) {
            $quarters[$q] = [
                'target_awal' => $originalTargets[$q],
                'tambahan' => $additionalPerQ[$q],
                'target_baru' => $originalTargets[$q] + $additionalPerQ[$q],
            ];
        }

        return response()->json([
            'no_ayat' => $noAyat,
            'keterangan' => $target->keterangan,
            'year' => $currentYear,
            'start_quarter' => $currentQuarter,
            'total_target_awal' => $totalTarget,
            'total_tambahan' => $additionalTarget,
            'total_target_baru' => $totalTarget + $additionalTarget,
            'quarters' => $quarters,
        ]);
    }

    public function aiRecommendation(Request $request, GetTaxForecastAction $getForecast): JsonResponse
    {
        $noAyat = $request->query('no_ayat');
        $currentYear = (int) now()->year;
        $currentMonth = (int) now()->month;

        if (! $noAyat) {
            return response()->json(['error' => 'Parameter no_ayat diperlukan.'], 422);
        }

        $target = SimpaduTarget::query()
            ->where('no_ayat', $noAyat)
            ->where('year', $currentYear)
            ->first();

        if (! $target) {
            return response()->json(['error' => 'Data target tidak ditemukan.'], 404);
        }

        $label = $target->keterangan ?? $noAyat;
        $result = $getForecast($noAyat, $label, 12);

        if (! $result || empty($result['forecast'])) {
            return response()->json(['error' => 'Data prediksi tidak tersedia untuk jenis pajak ini.'], 503);
        }

        $totalTarget = (float) $target->total_target;

        // Realisasi yang sudah masuk (bulan-bulan sebelum bulan ini di tahun ini)
        $realisasiSudahMasuk = collect($result['historis'] ?? [])
            ->filter(function ($h) use ($currentYear, $currentMonth) {
                $hYear = (int) substr($h['periode'], 0, 4);
                $hMonth = (int) substr($h['periode'], 5, 2);

                return $hYear === $currentYear && $hMonth < $currentMonth;
            })
            ->sum(fn ($h) => max(0.0, (float) $h['nilai']));

        // Sisa target yang belum tercapai
        $sisaTarget = $totalTarget - $realisasiSudahMasuk;

        // Total prediksi dari bulan ini hingga Desember tahun ini
        $prediksiSisaTahun = collect($result['forecast'])
            ->filter(function ($f) use ($currentYear, $currentMonth) {
                $fYear = (int) substr($f['periode'], 0, 4);
                $fMonth = (int) substr($f['periode'], 5, 2);

                return $fYear === $currentYear && $fMonth >= $currentMonth && $fMonth <= 12;
            })
            ->sum(fn ($f) => max(0.0, (float) $f['nilai']));

        // Rekomendasi = selisih prediksi vs sisa target
        // Hanya rekomendasikan jika prediksi MELEBIHI sisa target (ada potensi kelebihan)
        $selisih = $prediksiSisaTahun - $sisaTarget;
        $recommendation = max(0, round($selisih));

        $remainingMonths = 12 - $currentMonth + 1;

        return response()->json([
            'recommendation' => $recommendation,
            'model_used' => $result['model_used'] ?? 'SARIMA',
            'horizon_months' => $remainingMonths,
            'detail' => [
                'total_target' => round($totalTarget),
                'realisasi_sudah_masuk' => round($realisasiSudahMasuk),
                'sisa_target' => round($sisaTarget),
                'prediksi_sisa_tahun' => round($prediksiSisaTahun),
                'selisih' => round($selisih),
            ],
            'no_recommendation' => $recommendation <= 0,
        ]);
    }

    /**
     * Distribusi target tambahan mengikuti proporsi pct per tribulan aktif.
     * q_pct bersifat kumulatif, jadi proporsi per tribulan = selisih antar pct.
     *
     * @return array<int, float>
     */
    private function distributeByPct(float $total, int $startQ, ?SimpaduTarget $target): array
    {
        $result = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0];

        if (! $target) {
            // Fallback: rata jika tidak ada data pct
            $active = 4 - $startQ + 1;
            $per = round($total / $active, 2);
            $distributed = 0.0;
            for ($q = $startQ; $q <= 4; $q++) {
                $result[$q] = $q === 4 ? round($total - $distributed, 2) : $per;
                $distributed += $result[$q];
            }

            return $result;
        }

        $pcts = [
            1 => (float) $target->q1_pct,
            2 => (float) $target->q2_pct,
            3 => (float) $target->q3_pct,
            4 => (float) $target->q4_pct,
        ];

        // Proporsi per tribulan dari selisih kumulatif
        $perQPct = [
            1 => $pcts[1],
            2 => $pcts[2] - $pcts[1],
            3 => $pcts[3] - $pcts[2],
            4 => $pcts[4] - $pcts[3],
        ];

        // Hanya tribulan aktif yang dapat bagian
        $activePctSum = 0.0;
        for ($q = $startQ; $q <= 4; $q++) {
            $activePctSum += $perQPct[$q];
        }

        if ($activePctSum <= 0) {
            $activePctSum = 4 - $startQ + 1; // fallback equal weight
            for ($q = $startQ; $q <= 4; $q++) {
                $perQPct[$q] = 1.0;
            }
        }

        $distributed = 0.0;
        for ($q = $startQ; $q <= 4; $q++) {
            if ($q === 4) {
                $result[$q] = round($total - $distributed, 2);
            } else {
                $result[$q] = round($total * ($perQPct[$q] / $activePctSum), 2);
                $distributed += $result[$q];
            }
        }

        return $result;
    }

    public function getPct(Request $request): JsonResponse
    {
        $noAyat = $request->query('no_ayat');
        $currentYear = (int) now()->year;

        if (! $noAyat) {
            return response()->json(['pcts' => [1 => 25, 2 => 25, 3 => 25, 4 => 25]]);
        }

        $target = SimpaduTarget::query()
            ->where('no_ayat', $noAyat)
            ->where('year', $currentYear)
            ->first();

        if (! $target) {
            return response()->json(['pcts' => [1 => 25, 2 => 25, 3 => 25, 4 => 25]]);
        }

        $q1 = (float) $target->q1_pct;
        $q2 = (float) $target->q2_pct;
        $q3 = (float) $target->q3_pct;
        $q4 = (float) $target->q4_pct;

        return response()->json([
            'pcts' => [
                1 => $q1,
                2 => $q2 - $q1,
                3 => $q3 - $q2,
                4 => $q4 - $q3,
            ],
            'base_target' => (float) $target->total_target,
        ]);
    }

    public function destroy(UptAdditionalTarget $uptAdditionalTarget): RedirectResponse
    {
        $year = $uptAdditionalTarget->year;
        $uptAdditionalTarget->delete();

        Cache::forget("dashboard:tax:{$year}");

        return redirect()
            ->route('admin.tax-targets.report', ['year' => $year])
            ->with('success', 'Target tambahan APBD berhasil dihapus.');
    }
}
