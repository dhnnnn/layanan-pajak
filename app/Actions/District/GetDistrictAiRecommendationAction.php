<?php

namespace App\Actions\District;

use App\Models\District;
use App\Models\SimpaduTarget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetDistrictAiRecommendationAction
{
    /**
     * Hitung rekomendasi target tambahan per kecamatan per jenis pajak
     * berdasarkan prediksi SARIMA dari data historis simpadu_tax_payers.
     *
     * @return array{recommendation: int, model_used: string, horizon_months: int, detail: array, no_recommendation: bool}|array{error: string}
     */
    public function __invoke(District $district, string $noAyat): array
    {
        $currentYear = (int) now()->year;
        $currentMonth = (int) now()->month;

        $target = SimpaduTarget::query()
            ->where('no_ayat', $noAyat)
            ->where('year', $currentYear)
            ->first();

        if (! $target) {
            return ['error' => 'Data target tidak ditemukan.'];
        }

        // Ambil data historis realisasi per kecamatan + ayat
        $rows = DB::table('simpadu_tax_payers')
            ->where('kd_kecamatan', $district->simpadu_code)
            ->where('ayat', $noAyat)
            ->where('status', '1')
            ->where('month', '>', 0)
            ->selectRaw('year, month, SUM(total_bayar) as total_bayar')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Ambil semua baris yang punya nilai > 0, tanpa syarat berurutan
        // (data kecamatan wajar punya bulan kosong antar periode)
        $validRows = $rows->filter(fn ($r) => (float) $r->total_bayar > 0)->values();

        if ($validRows->count() < 2) {
            return ['error' => 'Data historis tidak cukup untuk kecamatan dan jenis pajak ini.'];
        }

        $historisData = $validRows->map(fn ($r) => [
            'periode' => sprintf('%d-%02d', $r->year, $r->month),
            'nilai' => (float) $r->total_bayar,
        ])->values()->toArray();

        $selisihSarima = 0.0;
        $prediksiSisaTahun = 0.0;
        $sisaTarget = 0.0;
        $modelUsed = 'Gap+Anomali';

        try {
            $response = Http::timeout(config('forecasting.timeout', 60))
                ->post(config('forecasting.url').'/forecast/from-data', [
                    'jenis_pajak' => "district_{$district->simpadu_code}_{$noAyat}",
                    'data' => $historisData,
                    'horizon' => 12,
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $forecast = $result['forecast'] ?? [];

                $realisasiSudahMasuk = collect($historisData)
                    ->filter(fn ($h) => (int) substr($h['periode'], 0, 4) === $currentYear
                        && (int) substr($h['periode'], 5, 2) < $currentMonth)
                    ->sum(fn ($h) => max(0.0, (float) $h['nilai']));

                $totalTarget = (float) $target->total_target;
                $sisaTarget = $totalTarget - $realisasiSudahMasuk;

                $prediksiSisaTahun = collect($forecast)
                    ->filter(fn ($f) => (int) substr($f['periode'], 0, 4) === $currentYear
                        && (int) substr($f['periode'], 5, 2) >= $currentMonth)
                    ->sum(fn ($f) => max(0.0, (float) $f['nilai']));

                $selisihSarima = $prediksiSisaTahun - $sisaTarget;
                $modelUsed = $result['model_used'] ?? 'SARIMA';
            }
        } catch (\Exception $e) {
            Log::error('District AI recommendation error', [
                'district' => $district->name,
                'no_ayat' => $noAyat,
                'error' => $e->getMessage(),
            ]);
        }

        $gapResult = $this->analyzeGap($district->simpadu_code, $noAyat, $currentYear, $currentMonth);
        $anomalyResult = $this->detectAnomalies($district->simpadu_code, $noAyat, $currentYear);

        // === Adjustment Logic ===
        $adjustment = $this->calculateAdjustment(
            $selisihSarima,
            $gapResult['total_potensi_gap'],
            $anomalyResult['total_potensi_anomali'],
            (float) $target->total_target,
        );

        $rekomendasiTotal = $adjustment['recommendation'];

        return [
            'recommendation' => $rekomendasiTotal,
            'model_used' => $modelUsed,
            'horizon_months' => 12 - $currentMonth + 1,
            'no_recommendation' => $rekomendasiTotal <= 0,
            'detail' => [
                'prediksi_sisa_tahun' => round($prediksiSisaTahun),
                'sisa_target' => round($sisaTarget),
                'selisih_sarima' => round($selisihSarima),
                'total_potensi_gap' => round($gapResult['total_potensi_gap']),
                'total_potensi_anomali' => round($anomalyResult['total_potensi_anomali']),
                'rekomendasi_total' => $rekomendasiTotal,
                'adjustment' => $adjustment['breakdown'],
            ],
            'gap_detail' => $gapResult['gap_detail'],
            'anomaly_detail' => [
                'wp_belum_bayar_count' => $anomalyResult['wp_belum_bayar_count'],
                'wp_anomali_count' => $anomalyResult['wp_anomali_count'],
                'total_potensi_anomali' => round($anomalyResult['total_potensi_anomali']),
            ],
        ];
    }

    /**
     * Hitung rekomendasi dengan adjustment: confidence weighting, overlap control, dan cap.
     *
     * Step 1: Confidence — bobot sesuai tingkat ketertagihan
     *   - Gap (WP tidak lapor): 40% — estimasi, belum tentu bisa ditagih
     *   - Anomali (WP kurang bayar): 70% — kewajiban riil, lebih pasti tertagih
     *
     * Step 2: Overlap Control — jika SARIMA sudah negatif (prediksi < target),
     *   sebagian potensi anomali mungkin sudah "tercermin" di gap SARIMA.
     *   Kurangi kontribusi anomali sebesar overlap agar tidak double counting.
     *
     * Step 3: Gabungkan — SARIMA positif + gap adjusted + anomali adjusted
     *
     * Step 4: Cap — batasi maksimum 30% dari total target agar realistis
     *
     * @return array{recommendation: int, breakdown: array}
     */
    private function calculateAdjustment(
        float $selisihSarima,
        float $potensiGap,
        float $potensiAnomali,
        float $totalTarget,
    ): array {
        // Step 1: Confidence weighting
        $gapConfidence = 0.4;
        $anomalyConfidence = 0.7;

        $gapWeighted = $potensiGap * $gapConfidence;
        $anomalyWeighted = $potensiAnomali * $anomalyConfidence;

        // Step 2: Overlap control
        // Jika SARIMA negatif, sebagian anomali mungkin sudah tercermin di sana.
        // Overlap dihitung proporsional: seberapa besar gap SARIMA relatif terhadap target.
        // Semakin besar gap-nya, semakin kecil kemungkinan anomali bisa tertagih penuh.
        // Tapi overlap maksimal 30% dari anomali — sisanya tetap dianggap bisa dikejar.
        $overlapReduction = 0.0;
        if ($selisihSarima < 0 && $totalTarget > 0) {
            $gapRatio = min(1.0, abs($selisihSarima) / $totalTarget);
            $overlapReduction = $anomalyWeighted * $gapRatio * 0.3;
        }
        $anomalyAdjusted = $anomalyWeighted - $overlapReduction;

        // Step 3: Gabungkan — SARIMA hanya berkontribusi jika positif
        $sarimaPart = max(0, $selisihSarima);
        $rawTotal = $sarimaPart + $gapWeighted + $anomalyAdjusted;

        // Step 4: Cap — maksimum 30% dari total target
        $cap = $totalTarget * 0.3;
        $capped = ($cap > 0 && $rawTotal > $cap) ? $cap : $rawTotal;

        $recommendation = max(0, (int) round($capped));

        return [
            'recommendation' => $recommendation,
            'breakdown' => [
                'sarima_part' => round($sarimaPart),
                'gap_raw' => round($potensiGap),
                'gap_confidence' => $gapConfidence,
                'gap_weighted' => round($gapWeighted),
                'anomaly_raw' => round($potensiAnomali),
                'anomaly_confidence' => $anomalyConfidence,
                'anomaly_weighted' => round($anomalyWeighted),
                'overlap_reduction' => round($overlapReduction),
                'anomaly_adjusted' => round($anomalyAdjusted),
                'raw_total' => round($rawTotal),
                'cap' => round($cap),
                'is_capped' => $cap > 0 && $rawTotal > $cap,
            ],
        ];
    }

    /**
     * Deteksi anomali ketetapan vs bayar untuk tahun berjalan.
     *
     * @return array{wp_belum_bayar_count: int, wp_anomali_count: int, total_potensi_anomali: float}
     */
    private function detectAnomalies(string $kdKecamatan, string $noAyat, int $currentYear): array
    {
        $rows = DB::table('simpadu_tax_payers')
            ->where('kd_kecamatan', $kdKecamatan)
            ->where('ayat', $noAyat)
            ->where('year', $currentYear)
            ->where('status', '1')
            ->where('total_ketetapan', '>', 0)
            ->selectRaw('npwpd, SUM(total_ketetapan) as total_ketetapan, SUM(total_bayar) as total_bayar')
            ->groupBy('npwpd')
            ->get();

        if ($rows->isEmpty()) {
            return [
                'wp_belum_bayar_count' => 0,
                'wp_anomali_count' => 0,
                'total_potensi_anomali' => 0.0,
            ];
        }

        $wpBelumBayar = $rows->filter(fn ($row) => (float) $row->total_bayar === 0.0);
        $wpAnomali = $rows->filter(
            fn ($row) => (float) $row->total_bayar > 0
                && (float) $row->total_bayar < (float) $row->total_ketetapan * 0.5
        );

        $anomaliRows = $wpBelumBayar->merge($wpAnomali);
        $totalPotensiAnomali = $anomaliRows->sum(
            fn ($row) => (float) $row->total_ketetapan - (float) $row->total_bayar
        );

        return [
            'wp_belum_bayar_count' => $wpBelumBayar->count(),
            'wp_anomali_count' => $wpAnomali->count(),
            'total_potensi_anomali' => $totalPotensiAnomali,
        ];
    }

    /**
     * Analisis gap historis WP: bandingkan WP tahun lalu vs tahun ini per bulan.
     *
     * @return array{total_potensi_gap: float, gap_detail: array}
     */
    private function analyzeGap(string $kdKecamatan, string $noAyat, int $currentYear, int $currentMonth): array
    {
        // Query 1: data tahun lalu per bulan per npwpd
        $lastYearData = DB::table('simpadu_tax_payers')
            ->where('kd_kecamatan', $kdKecamatan)
            ->where('ayat', $noAyat)
            ->where('year', $currentYear - 1)
            ->where('status', '1')
            ->where('month', '>', 0)
            ->selectRaw('month, npwpd, SUM(total_bayar) as total_bayar')
            ->groupBy('month', 'npwpd')
            ->get();

        if ($lastYearData->isEmpty()) {
            return ['total_potensi_gap' => 0.0, 'gap_detail' => []];
        }

        // Query 2: npwpd distinct tahun ini per bulan
        $thisYearData = DB::table('simpadu_tax_payers')
            ->where('kd_kecamatan', $kdKecamatan)
            ->where('ayat', $noAyat)
            ->where('year', $currentYear)
            ->where('status', '1')
            ->where('month', '>', 0)
            ->selectRaw('DISTINCT month, npwpd')
            ->get();

        // Group data per bulan
        $lastYearByMonth = $lastYearData->groupBy('month');
        $thisYearByMonth = $thisYearData->groupBy('month');

        $gapDetail = [];
        $totalPotensiGap = 0.0;

        // Proses hanya bulan >= currentMonth
        for ($month = $currentMonth; $month <= 12; $month++) {
            $lastYearNpwpds = $lastYearByMonth->get($month, collect());
            $thisYearNpwpds = $thisYearByMonth->get($month, collect())->pluck('npwpd')->toArray();

            if ($lastYearNpwpds->isEmpty()) {
                continue;
            }

            // WP hilang: ada di tahun lalu tapi tidak di tahun ini
            $wpHilang = $lastYearNpwpds->filter(function ($row) use ($thisYearNpwpds) {
                return ! in_array($row->npwpd, $thisYearNpwpds);
            });

            $wpHilangCount = $wpHilang->count();

            if ($wpHilangCount === 0) {
                continue;
            }

            // Rata-rata bayar per WP di tahun lalu untuk bulan ini
            $avgBayarPerWp = $lastYearNpwpds->avg(fn ($row) => (float) $row->total_bayar);

            // Potensi gap untuk bulan ini
            $potensiGap = $wpHilangCount * $avgBayarPerWp;
            $totalPotensiGap += $potensiGap;

            $gapDetail[] = [
                'month' => $month,
                'wp_hilang_count' => $wpHilangCount,
                'avg_bayar_per_wp' => $avgBayarPerWp,
                'potensi_gap' => $potensiGap,
            ];
        }

        return [
            'total_potensi_gap' => $totalPotensiGap,
            'gap_detail' => $gapDetail,
        ];
    }
}
