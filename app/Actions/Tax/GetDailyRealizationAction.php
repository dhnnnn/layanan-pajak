<?php

namespace App\Actions\Tax;

use App\Models\SimpaduTarget;
use Illuminate\Support\Facades\DB;

class GetDailyRealizationAction
{
    private const PBJT_AYAT = ['41101', '41102', '41103', '41105', '41107'];

    /**
     * @return array{dates: array, rows: array, totals: array}
     */
    public function __invoke(int $year): array
    {
        $dates = DB::table('simpadu_sptpd_reports')
            ->whereYear('tgl_lapor', $year)
            ->distinct()
            ->orderByDesc('tgl_lapor')
            ->limit(6)
            ->pluck('tgl_lapor')
            ->sort()
            ->values();

        $ayatList = SimpaduTarget::query()
            ->where('year', $year)
            ->orderBy('no_ayat')
            ->get(['no_ayat', 'keterangan', 'total_target']);

        if ($dates->isEmpty() || $ayatList->isEmpty()) {
            return ['dates' => [], 'rows' => [], 'totals' => []];
        }

        $raw = DB::table('simpadu_sptpd_reports as s')
            ->join('simpadu_tax_payers as t', function ($j) {
                $j->on('t.npwpd', '=', 's.npwpd')
                    ->on('t.nop', '=', 's.nop')
                    ->on('t.year', '=', 's.year')
                    ->on('t.month', '=', 's.month');
            })
            ->whereIn('s.tgl_lapor', $dates)
            ->where('t.status', '1')
            ->selectRaw('s.tgl_lapor, t.ayat, SUM(s.jml_lapor) as total')
            ->groupBy('s.tgl_lapor', 't.ayat')
            ->get();

        $pivot = [];
        foreach ($raw as $r) {
            $pivot[$r->ayat][(string) $r->tgl_lapor] = (float) $r->total;
        }

        $ytd = DB::table('simpadu_monthly_realizations')
            ->where('year', $year)
            ->selectRaw('ayat, SUM(total_bayar) as total_ytd')
            ->groupBy('ayat')
            ->pluck('total_ytd', 'ayat');

        $rows = [];
        $grandTarget = 0;
        $grandDates = array_fill_keys($dates->toArray(), 0.0);
        $grandJumlah = 0.0;
        $grandSisa = 0.0;

        // Akumulasi PBJT parent
        $pbjtTarget = 0.0;
        $pbjtDates = array_fill_keys($dates->toArray(), 0.0);
        $pbjtJumlah = 0.0;
        $pbjtYtd = 0.0;
        $pbjtInserted = false;

        foreach ($ayatList as $ayat) {
            $isChild = in_array($ayat->no_ayat, self::PBJT_AYAT);

            // Sisipkan baris PBJT parent sebelum child pertama
            if ($isChild && ! $pbjtInserted) {
                $rows[] = ['__pbjt_placeholder' => true];
                $pbjtInserted = true;
            }

            $dateValues = [];
            $jumlah6Hari = 0.0;
            foreach ($dates as $d) {
                $val = $pivot[$ayat->no_ayat][$d] ?? 0.0;
                $dateValues[$d] = $val;
                $jumlah6Hari += $val;
                $grandDates[$d] += $val;
                if ($isChild) {
                    $pbjtDates[$d] += $val;
                }
            }

            $target = (float) $ayat->total_target;
            $ytdTotal = (float) ($ytd[$ayat->no_ayat] ?? 0);
            $sisa = $target - $ytdTotal;
            $persen = $target > 0 ? ($ytdTotal / $target) * 100 : 0;

            $grandTarget += $target;
            $grandJumlah += $jumlah6Hari;
            $grandSisa += $sisa;

            if ($isChild) {
                $pbjtTarget += $target;
                $pbjtJumlah += $jumlah6Hari;
                $pbjtYtd += $ytdTotal;
            }

            $rows[] = [
                'no_ayat' => $ayat->no_ayat,
                'keterangan' => $ayat->keterangan,
                'is_child' => $isChild,
                'is_parent' => false,
                'target_total' => $target,
                'dates' => $dateValues,
                'jumlah_6hari' => $jumlah6Hari,
                'sisa_target' => $sisa,
                'persen' => round($persen, 2),
            ];
        }

        // Isi placeholder PBJT parent
        $pbjtSisa = $pbjtTarget - $pbjtYtd;
        $pbjtPersen = $pbjtTarget > 0 ? ($pbjtYtd / $pbjtTarget) * 100 : 0;
        $rows = array_map(function ($row) use ($pbjtTarget, $pbjtDates, $pbjtJumlah, $pbjtSisa, $pbjtPersen) {
            if (isset($row['__pbjt_placeholder'])) {
                return [
                    'no_ayat' => '41100',
                    'keterangan' => 'Pajak (PBJT)',
                    'is_child' => false,
                    'is_parent' => true,
                    'target_total' => $pbjtTarget,
                    'dates' => $pbjtDates,
                    'jumlah_6hari' => $pbjtJumlah,
                    'sisa_target' => $pbjtSisa,
                    'persen' => round($pbjtPersen, 2),
                ];
            }

            return $row;
        }, $rows);

        return [
            'dates' => $dates->toArray(),
            'rows' => array_values($rows),
            'totals' => [
                'target' => $grandTarget,
                'dates' => $grandDates,
                'jumlah_6hari' => $grandJumlah,
                'sisa_target' => $grandSisa,
            ],
        ];
    }
}
