<?php

namespace App\Actions\Simpadu;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetWpDetailAction
{
    /** @return array<string, mixed> */
    public function __invoke(string $npwpd, string $nop, int $year, int $monthFrom, int $monthTo, int $multiYear = 1): array
    {
        // Base WP info from annual record
        $wpInfo = DB::table('simpadu_tax_payers as s')
            ->leftJoin('tax_types', 'tax_types.simpadu_code', '=', 's.ayat')
            ->select(['s.npwpd', 's.nop', 's.nm_wp', 's.nm_op', 's.almt_op', 's.kd_kecamatan', 's.status', 'tax_types.name as tax_type_name'])
            ->where('s.npwpd', $npwpd)
            ->where('s.nop', $nop)
            ->where('s.year', $year)
            ->where('s.month', 0)
            ->first();

        // Fallback: try any year if current year not found
        if (! $wpInfo) {
            $wpInfo = DB::table('simpadu_tax_payers as s')
                ->leftJoin('tax_types', 'tax_types.simpadu_code', '=', 's.ayat')
                ->select(['s.npwpd', 's.nop', 's.nm_wp', 's.nm_op', 's.almt_op', 's.kd_kecamatan', 's.status', 'tax_types.name as tax_type_name'])
                ->where('s.npwpd', $npwpd)
                ->where('s.nop', $nop)
                ->where('s.month', 0)
                ->orderByDesc('s.year')
                ->first();
        }

        $years = $multiYear > 1 ? array_map(fn ($i) => $year - $i, range(0, $multiYear - 1)) : [$year];
        $months = range($monthFrom, $monthTo);

        $bulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Build monthly table data per year
        $tableData = [];
        foreach ($years as $y) {
            $rows = DB::table('simpadu_tax_payers')
                ->where('year', $y)
                ->where('npwpd', $npwpd)
                ->where('nop', $nop)
                ->whereBetween('month', [$monthFrom, $monthTo])
                ->orderBy('month')
                ->get(['month', 'total_ketetapan', 'total_bayar', 'total_tunggakan'])
                ->keyBy('month');

            $sptpdRows = DB::table('simpadu_sptpd_reports')
                ->where('year', $y)
                ->where('npwpd', $npwpd)
                ->where('nop', $nop)
                ->whereBetween('month', [$monthFrom, $monthTo])
                ->get(['month', 'tgl_lapor', 'jml_lapor'])
                ->keyBy('month');

            $yearRows = [];
            foreach ($months as $m) {
                $row = $rows->get($m);
                $sptpd = $sptpdRows->get($m);
                $yearRows[] = [
                    'month' => $m,
                    'month_name' => $bulanIndo[$m],
                    'tgl_lapor' => $sptpd?->tgl_lapor ? Carbon::parse($sptpd->tgl_lapor)->format('d/m/Y') : '-',
                    'total_ketetapan' => (float) ($row?->total_ketetapan ?? 0),
                    'total_bayar' => (float) ($row?->total_bayar ?? 0),
                    'total_tunggakan' => (float) max($row?->total_tunggakan ?? 0, 0),
                ];
            }

            $tableData[$y] = $yearRows;
        }

        // Chart datasets
        $colors = [
            ['border' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.08)'],
            ['border' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.08)'],
            ['border' => '#10b981', 'bg' => 'rgba(16,185,129,0.08)'],
        ];

        $labels = array_map(fn ($m) => substr($bulanIndo[$m], 0, 3), $months);
        $datasets = [];

        foreach ($years as $i => $y) {
            $c = $colors[$i] ?? $colors[0];
            $rows = DB::table('simpadu_tax_payers')
                ->where('year', $y)->where('npwpd', $npwpd)->where('nop', $nop)
                ->whereBetween('month', [$monthFrom, $monthTo])
                ->orderBy('month')->get(['month', 'total_ketetapan', 'total_bayar'])->keyBy('month');

            $sptpd = [];
            $bayar = [];
            foreach ($months as $m) {
                $r = $rows->get($m);
                $sptpd[] = $r ? (float) $r->total_ketetapan : 0;
                $bayar[] = $r ? (float) $r->total_bayar : 0;
            }

            $datasets[] = ['label' => "SPTPD {$y}", 'data' => $sptpd, 'borderColor' => $c['border'],
                'backgroundColor' => $c['bg'], 'tension' => 0.4, 'fill' => true, 'pointRadius' => 4, 'borderWidth' => 2];
            $datasets[] = ['label' => "Bayar {$y}", 'data' => $bayar, 'borderColor' => $c['border'],
                'backgroundColor' => 'transparent', 'borderDash' => [5, 4], 'tension' => 0.4,
                'fill' => false, 'pointRadius' => 3, 'borderWidth' => 1.5];
        }

        // District name from local districts table
        $districtName = DB::table('districts')
            ->where('simpadu_code', $wpInfo?->kd_kecamatan)
            ->value('name') ?? '-';

        return [
            'wpInfo' => $wpInfo,
            'districtName' => $districtName,
            'tableData' => $tableData,
            'chartLabels' => $labels,
            'chartDatasets' => $datasets,
            'years' => $years,
            'months' => $months,
            'bulanIndo' => $bulanIndo,
        ];
    }
}
