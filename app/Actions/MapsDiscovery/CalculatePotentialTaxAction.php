<?php

namespace App\Actions\MapsDiscovery;

use App\Models\MapsDiscoveryResult;
use App\Models\MapsStatistic;
use App\Models\MonitoringReport;
use App\Models\PotentialCalculation;
use Illuminate\Support\Facades\DB;

class CalculatePotentialTaxAction
{
    /**
     * Hitung potensi pajak berdasarkan data monitoring dan statistik Maps.
     *
     * Formula dari Excel "ADA SI PAKDE":
     * - Jumlah Pengunjung 1 Minggu = (Total Maps Seminggu / Maps Jam Checker) × Hasil Checker / Durasi Kunjungan
     * - Potensi Pajak 1 Minggu = Jumlah Pengunjung × Avg Menu × 10%
     * - Potensi Pajak 1 Bulan = Potensi 1 Minggu × 30 / 7
     * - Min = 75%, Max = 125%
     *
     * @return array{success: bool, message: string, calculation?: PotentialCalculation}
     */
    public function __invoke(
        MapsDiscoveryResult $result,
        MonitoringReport $monitoring,
        float $avgMenuPrice,
        float $avgDurationHours = 2.5
    ): array {
        // Validasi: harus ada statistik Maps
        $mapsWeeklyTotal = MapsStatistic::where('maps_discovery_result_id', $result->id)
            ->sum('visitor_count');

        if ($mapsWeeklyTotal === 0) {
            return [
                'success' => false,
                'message' => 'Statistik Maps belum tersedia. Silakan scrape terlebih dahulu.',
            ];
        }

        // Ambil jumlah pengunjung dari Maps pada jam monitoring
        $mapsHourCount = MapsStatistic::where('maps_discovery_result_id', $result->id)
            ->where('hour_range', $monitoring->monitoring_hour)
            ->where('day_of_week', $monitoring->day_of_week)
            ->value('visitor_count') ?? 0;

        if ($mapsHourCount === 0) {
            return [
                'success' => false,
                'message' => 'Data Maps untuk jam monitoring tidak tersedia.',
            ];
        }

        // Hitung jumlah pengunjung 1 minggu
        // Formula: (c / b) × a / d
        // c = Total Maps Seminggu
        // b = Maps Jam Checker
        // a = Hasil Checker
        // d = Durasi Kunjungan
        $weeklyVisitors = (int) round(
            ($mapsWeeklyTotal / $mapsHourCount) * $monitoring->visitor_count / $avgDurationHours
        );

        // Hitung potensi pajak
        $taxRate = 0.10; // 10%
        $weeklyPotentialTax = $weeklyVisitors * $avgMenuPrice * $taxRate;
        $monthlyPotentialTax = $weeklyPotentialTax * 30 / 7;
        $minPotentialTax = $monthlyPotentialTax * 0.75;
        $maxPotentialTax = $monthlyPotentialTax * 1.25;

        // Simpan hasil perhitungan
        $calculation = DB::transaction(function () use (
            $result,
            $monitoring,
            $avgMenuPrice,
            $avgDurationHours,
            $mapsWeeklyTotal,
            $mapsHourCount,
            $weeklyVisitors,
            $weeklyPotentialTax,
            $monthlyPotentialTax,
            $minPotentialTax,
            $maxPotentialTax
        ): PotentialCalculation {
            return PotentialCalculation::create([
                'maps_discovery_result_id' => $result->id,
                'monitoring_report_id' => $monitoring->id,
                'checker_result' => $monitoring->visitor_count,
                'maps_hour_count' => $mapsHourCount,
                'maps_weekly_total' => $mapsWeeklyTotal,
                'avg_duration_hours' => $avgDurationHours,
                'avg_menu_price' => $avgMenuPrice,
                'weekly_visitors' => $weeklyVisitors,
                'weekly_potential_tax' => $weeklyPotentialTax,
                'monthly_potential_tax' => $monthlyPotentialTax,
                'min_potential_tax' => $minPotentialTax,
                'max_potential_tax' => $maxPotentialTax,
                'calculation_date' => now()->toDateString(),
            ]);
        });

        // Update avg_menu_price di MapsDiscoveryResult untuk referensi
        $result->update(['avg_menu_price' => $avgMenuPrice]);

        return [
            'success' => true,
            'message' => 'Perhitungan potensi pajak berhasil',
            'calculation' => $calculation,
        ];
    }
}
