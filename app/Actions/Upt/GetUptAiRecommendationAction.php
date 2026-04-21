<?php

namespace App\Actions\Upt;

use App\Actions\Tax\GetTaxForecastAction;
use App\Models\SimpaduTarget;

class GetUptAiRecommendationAction
{
    public function __construct(
        private readonly GetTaxForecastAction $getForecast,
    ) {}

    /**
     * Hitung rekomendasi target tambahan global per jenis pajak
     * berdasarkan prediksi SARIMA vs sisa target tahun ini.
     *
     * @return array{recommendation: int, model_used: string, horizon_months: int, detail: array, no_recommendation: bool}|array{error: string}
     */
    public function __invoke(string $noAyat): array
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

        $label = $target->keterangan ?? $noAyat;
        $result = ($this->getForecast)($noAyat, $label, 12);

        if (! $result || empty($result['forecast'])) {
            return ['error' => 'Data prediksi tidak tersedia untuk jenis pajak ini.'];
        }

        $totalTarget = (float) $target->total_target;

        // Realisasi yang sudah masuk tahun ini (sebelum bulan ini)
        $realisasiSudahMasuk = collect($result['historis'] ?? [])
            ->filter(fn ($h) => (int) substr($h['periode'], 0, 4) === $currentYear
                && (int) substr($h['periode'], 5, 2) < $currentMonth)
            ->sum(fn ($h) => max(0.0, (float) $h['nilai']));

        $sisaTarget = $totalTarget - $realisasiSudahMasuk;

        // Total prediksi dari bulan ini hingga Desember
        $prediksiSisaTahun = collect($result['forecast'])
            ->filter(fn ($f) => (int) substr($f['periode'], 0, 4) === $currentYear
                && (int) substr($f['periode'], 5, 2) >= $currentMonth
                && (int) substr($f['periode'], 5, 2) <= 12)
            ->sum(fn ($f) => max(0.0, (float) $f['nilai']));

        $selisih = $prediksiSisaTahun - $sisaTarget;
        $recommendation = max(0, round($selisih));

        return [
            'recommendation' => $recommendation,
            'model_used' => $result['model_used'] ?? 'SARIMA',
            'horizon_months' => 12 - $currentMonth + 1,
            'detail' => [
                'total_target' => round($totalTarget),
                'realisasi_sudah_masuk' => round($realisasiSudahMasuk),
                'sisa_target' => round($sisaTarget),
                'prediksi_sisa_tahun' => round($prediksiSisaTahun),
                'selisih' => round($selisih),
            ],
            'no_recommendation' => $recommendation <= 0,
        ];
    }
}
