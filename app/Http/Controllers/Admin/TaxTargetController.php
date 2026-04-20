<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tax\GenerateTaxDashboardAction;
use App\Actions\Tax\ShowTaxTargetDetailAction;
use App\Exports\TaxTargetExport;
use App\Http\Controllers\Controller;
use App\Models\SimpaduMonthlyRealization;
use App\Models\SimpaduTarget;
use App\Models\TaxType;
use App\Models\UptAdditionalTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TaxTargetController extends Controller
{
    public function report(
        Request $request,
        GenerateTaxDashboardAction $generateDashboard,
    ): View {
        $availableYears = SimpaduTarget::query()
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->merge(
                SimpaduMonthlyRealization::query()
                    ->distinct()
                    ->pluck('year')
            )
            ->unique()
            ->sortDesc()
            ->values();

        $selectedYear = (int) $request->query('year', date('Y'));

        $search = $request->query('search');

        $result = $generateDashboard(
            year: $selectedYear,
            search: $search
        );

        $additionalTargets = UptAdditionalTarget::query()
            ->with('creator')
            ->where('year', $selectedYear)
            ->orderBy('no_ayat')
            ->get();

        $ayatLabels = SimpaduTarget::query()
            ->where('year', $selectedYear)
            ->pluck('keterangan', 'no_ayat');

        return view('admin.tax-targets.report', [
            'dashboard' => $result['data'],
            'totals' => $result['totals'],
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
            'additionalTargets' => $additionalTargets,
            'ayatLabels' => $ayatLabels,
        ]);
    }

    public function show(
        TaxType $taxType,
        Request $request,
        ShowTaxTargetDetailAction $showDetail
    ): View {
        $year = $request->filled('year') ? (int) $request->year : (int) date('Y');
        $search = $request->query('search');
        $selectedDistrict = $request->query('district');

        $result = $showDetail($taxType, $year, $search, $selectedDistrict);

        return view('admin.tax-targets.show', [
            'taxType' => $result['taxType'],
            'year' => $year,
            'summary' => $result['summary'],
            'payers' => $result['payers'],
            'districts' => $result['districts'],
            'search' => $search,
            'selectedDistrict' => $selectedDistrict,
        ]);
    }

    public function export(): BinaryFileResponse
    {
        $year = request()->integer('year', (int) date('Y'));
        $filename = "target-pajak-{$year}.xlsx";

        return Excel::download(new TaxTargetExport($year), $filename);
    }

    /**
     * Data penerimaan 6 hari terakhir per ayat (AJAX).
     */
    public function dailyRealization(Request $request): JsonResponse
    {
        $year = (int) $request->query('year', date('Y'));

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
            return response()->json(['dates' => [], 'rows' => [], 'totals' => []]);
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

        $pbjtAyat = ['41101', '41102', '41103', '41105', '41107'];

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
            $isChild = in_array($ayat->no_ayat, $pbjtAyat);

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

        return response()->json([
            'dates' => $dates->toArray(),
            'rows' => array_values($rows),
            'totals' => [
                'target' => $grandTarget,
                'dates' => $grandDates,
                'jumlah_6hari' => $grandJumlah,
                'sisa_target' => $grandSisa,
            ],
        ]);
    }

    /**
     * Data realisasi bulan berjalan per minggu per ayat (AJAX).
     */
    public function weeklyRealization(Request $request): JsonResponse
    {
        $year = (int) $request->query('year', date('Y'));
        $month = (int) $request->query('month', date('n'));

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

        $pbjtAyat = ['41101', '41102', '41103', '41105', '41107'];

        // Akumulasi PBJT parent
        $pbjtTarget = 0.0;
        $pbjtWeeks = ['I' => 0.0, 'II' => 0.0, 'III' => 0.0, 'IV' => 0.0];
        $pbjtTotal = 0.0;
        $pbjtYtd = 0.0;
        $pbjtInserted = false;

        foreach ($ayatList as $ayat) {
            $isChild = in_array($ayat->no_ayat, $pbjtAyat);

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

        return response()->json([
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
        ]);
    }
}
