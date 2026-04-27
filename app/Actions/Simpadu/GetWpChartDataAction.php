<?php

namespace App\Actions\Simpadu;

use Illuminate\Support\Facades\DB;

class GetWpChartDataAction
{
    /**
     * @return array{labels: list<string>, datasets: list<array>}
     */
    public function __invoke(
        string $npwpd,
        string $nop,
        int $year,
        int $monthFrom,
        int $monthTo,
        bool $multiYear = false,
    ): array {
        $years = $multiYear ? [$year, $year - 1, $year - 2] : [$year];

        $bulanIndo = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $labels = array_map(fn ($m) => $bulanIndo[$m], range($monthFrom, $monthTo));

        $datasets = [];
        $colors = [
            ['border' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.08)'],
            ['border' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.08)'],
            ['border' => '#10b981', 'bg' => 'rgba(16,185,129,0.08)'],
        ];

        foreach ($years as $i => $y) {
            $rows = DB::table('simpadu_tax_payers')
                ->where('year', $y)
                ->where('npwpd', $npwpd)
                ->where('nop', $nop)
                ->whereBetween('month', [$monthFrom, $monthTo])
                ->orderBy('month')
                ->get(['month', 'total_ketetapan', 'total_bayar'])
                ->keyBy('month');

            $sptpd = [];
            $bayar = [];
            for ($m = $monthFrom; $m <= $monthTo; $m++) {
                $row = $rows->get($m);
                $sptpd[] = $row ? (float) $row->total_ketetapan : 0;
                $bayar[] = $row ? (float) $row->total_bayar : 0;
            }

            $c = $colors[$i] ?? $colors[0];
            $datasets[] = [
                'label' => "SPTPD {$y}",
                'data' => $sptpd,
                'borderColor' => $c['border'],
                'backgroundColor' => $c['bg'],
                'tension' => 0.4,
                'fill' => true,
                'pointRadius' => 4,
                'borderWidth' => 2,
            ];
            $datasets[] = [
                'label' => "Bayar {$y}",
                'data' => $bayar,
                'borderColor' => $c['border'],
                'backgroundColor' => 'transparent',
                'borderDash' => [5, 4],
                'tension' => 0.4,
                'fill' => false,
                'pointRadius' => 3,
                'borderWidth' => 1.5,
            ];
        }

        return ['labels' => $labels, 'datasets' => $datasets];
    }
}
