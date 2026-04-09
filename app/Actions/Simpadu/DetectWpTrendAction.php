<?php

namespace App\Actions\Simpadu;

class DetectWpTrendAction
{
    /**
     * Deteksi trend pembayaran WP dari data yang sudah di-load (sesuai filter aktif).
     *
     * Menerima array nilai total_bayar yang sudah diurutkan ascending (bulan terlama → terbaru),
     * lalu menghitung slope regresi linear untuk menentukan arah trend.
     *
     * @param  float[]  $values  Nilai total_bayar per bulan, ascending
     * @return array{
     *     label: string,
     *     direction: string,
     *     slope: float,
     *     change_pct: float,
     *     data_points: int,
     *     is_inactive: bool,
     * }
     */
    public function __invoke(array $values): array
    {
        $n = count($values);

        if ($n === 0) {
            return $this->buildResult('Tidak Ada Data', 'no_data', 0, 0, 0, true);
        }

        $totalBayar = array_sum($values);

        if ($totalBayar <= 0) {
            return $this->buildResult('Tidak Aktif', 'inactive', 0, 0, $n, true);
        }

        $slope = $this->calculateSlope($values);

        // Persentase perubahan: rata-rata paruh pertama vs paruh kedua
        $half = max(1, (int) ($n / 2));
        $avgFirst = array_sum(array_slice($values, 0, $half)) / $half;
        $lastHalf = array_slice($values, $half);
        $avgLast = count($lastHalf) > 0 ? array_sum($lastHalf) / count($lastHalf) : $avgFirst;
        $changePct = $avgFirst > 0 ? (($avgLast - $avgFirst) / $avgFirst) * 100 : 0;

        // Threshold stagnan: 5% dari rata-rata keseluruhan
        $avgValue = $totalBayar / $n;
        $threshold = $avgValue * 0.05;

        if ($slope > $threshold) {
            return $this->buildResult('Naik', 'up', $slope, $changePct, $n, false);
        } elseif ($slope < -$threshold) {
            return $this->buildResult('Turun', 'down', $slope, $changePct, $n, false);
        } else {
            return $this->buildResult('Stagnan', 'stable', $slope, $changePct, $n, false);
        }
    }

    /**
     * Hitung slope regresi linear sederhana (Ordinary Least Squares).
     *
     * @param  float[]  $values
     */
    private function calculateSlope(array $values): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0.0;
        }

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumX += $i;
            $sumY += $values[$i];
            $sumXY += $i * $values[$i];
            $sumX2 += $i * $i;
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) {
            return 0.0;
        }

        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }

    /** @return array{label: string, direction: string, slope: float, change_pct: float, data_points: int, is_inactive: bool} */
    private function buildResult(
        string $label,
        string $direction,
        float $slope,
        float $changePct,
        int $dataPoints,
        bool $isInactive,
    ): array {
        return [
            'label' => $label,
            'direction' => $direction,
            'slope' => round($slope, 2),
            'change_pct' => round($changePct, 1),
            'data_points' => $dataPoints,
            'is_inactive' => $isInactive,
        ];
    }
}
