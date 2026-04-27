<?php

namespace App\Actions\Tax;

use App\Models\SimpaduTarget;
use Illuminate\Support\Facades\DB;

class GetWeeklyRealizationAction
{
    private const PBJT_AYAT = ['41101', '41102', '41103', '41105', '41107'];

    /**
     * @return array{month: int, month_name: string, year: int, rows: array, totals: array}
     */
    public function __invoke(int $year, int $month): array
    {
        $ayatList = SimpaduTarget::query()
            ->where('year', $year)
            ->orderBy('no_ayat')
            ->get(['no_ayat', 'keterangan', 'total_target']);

        // Tentukan minggu dalam bulan (Minggu I=1-7, II=8-14, III=15-21, IV=22-akhir)
        $weeks = [
            'I' => [1, 7],
            'II' => [8, 14],
            'III' => [15, 21],
            'IV' => [22, 31],
        ];

        $raw = DB::table('simpadu_sptpd_reports as s')
            ->join('simpadu_tax_payers as t', function ($j) {
                $j->on('t.npwpd', '=', 's.npwpd')
                    ->on('t.nop', '=', 's.nop')
                    ->on('t.year', '=', 's.year')
                    ->on('t.month', '=', 's.month');
            })
            ->whereYear('s.tgl_lapor', $year)
            ->whereMonth('s.tgl_lapor', $month)
            ->where('t.status', '1')
            ->selectRaw('DAY(s.tgl_lapor) as day_num, t.ayat, SUM(s.jml_lapor) as total')
            ->groupBy('day_num', 't.ayat')
            ->get();

        // Pivot: [ayat][week] = total
        $pivot = [];
        foreach ($raw as $r) {
            $day = (int) $r->day_num;
            $week = match (true) {
                $day <= 7 => 'I',
                $day <= 14 => 'II',
                $day <= 21 => 'III',
                default => 'IV',
            };
            $pivot[$r->ayat][$week] = ($pivot[$r->ayat][$week] ?? 0) + (float) $r->total;
        }

        $ytd = DB::table('simpadu_monthly_realizations')
            ->where('year', $year)
            ->selectRaw('ayat, SUM(total_bayar) as total_ytd')
            ->groupBy('ayat')
            ->pluck('total_ytd', 'ayat');

        $rows = [];
        $grandTarget = 0;
        $grandWeeks = ['I' => 0.0, 'II' => 0.0, 'III' => 0.0, 'IV' => 0.0];
        $grandTotal = 0.0;
        $grandSisa = 0.0;

        // Akumulasi PBJT parent
        $pbjtTarget = 0.0;
        $pbjtWeeks = ['I' => 0.0, 'II' => 0.0, 'III' => 0.0, 'IV' => 0.0];
        $pbjtTotal = 0.0;
        $pbjtYtd = 0.0;
        $pbjtInserted = false;

        foreach ($ayatList as $ayat) {
            $isChild = in_array($ayat->no_ayat, self::PBJT_AYAT);

            if ($isChild && ! $pbjtInserted) {
                $rows[] = ['__pbjt_placeholder' => true];
                $pbjtInserted = true;
            }

            $weekValues = [];
            $totalBulan = 0.0;
            foreach (array_keys($weeks) as $w) {
                $val = $pivot[$ayat->no_ayat][$w] ?? 0.0;
                $weekValues[$w] = $val;
                $totalBulan += $val;
                $grandWeeks[$w] += $val;
                if ($isChild) {
                    $pbjtWeeks[$w] += $val;
                }
            }

            $target = (float) $ayat->total_target;
            $ytdTotal = (float) ($ytd[$ayat->no_ayat] ?? 0);
            $sisa = $target - $ytdTotal;
            $persen = $target > 0 ? ($ytdTotal / $target) * 100 : 0;

            $grandTarget += $target;
            $grandTotal += $totalBulan;
            $grandSisa += $sisa;

            if ($isChild) {
                $pbjtTarget += $target;
                $pbjtTotal += $totalBulan;
                $pbjtYtd += $ytdTotal;
            }

            $rows[] = [
                'no_ayat' => $ayat->no_ayat,
                'keterangan' => $ayat->keterangan,
                'is_child' => $isChild,
                'is_parent' => false,
                'target_total' => $target,
                'weeks' => $weekValues,
                'total_bulan' => $totalBulan,
                'total_realisasi' => $ytdTotal,
                'sisa_target' => $sisa,
                'persen' => round($persen, 2),
            ];
        }

        $pbjtSisa = $pbjtTarget - $pbjtYtd;
        $pbjtPersen = $pbjtTarget > 0 ? ($pbjtYtd / $pbjtTarget) * 100 : 0;
        $rows = array_map(function ($row) use ($pbjtTarget, $pbjtWeeks, $pbjtTotal, $pbjtYtd, $pbjtSisa, $pbjtPersen) {
            if (isset($row['__pbjt_placeholder'])) {
                return [
                    'no_ayat' => '41100',
                    'keterangan' => 'Pajak (PBJT)',
                    'is_child' => false,
                    'is_parent' => true,
                    'target_total' => $pbjtTarget,
                    'weeks' => $pbjtWeeks,
                    'total_bulan' => $pbjtTotal,
                    'total_realisasi' => $pbjtYtd,
                    'sisa_target' => $pbjtSisa,
                    'persen' => round($pbjtPersen, 2),
                ];
            }

            return $row;
        }, $rows);

        $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return [
            'month' => $month,
            'month_name' => $monthNames[$month],
            'year' => $year,
            'rows' => array_values($rows),
            'totals' => [
                'target' => $grandTarget,
                'weeks' => $grandWeeks,
                'total_bulan' => $grandTotal,
                'sisa_target' => $grandSisa,
            ],
        ];
    }
}
