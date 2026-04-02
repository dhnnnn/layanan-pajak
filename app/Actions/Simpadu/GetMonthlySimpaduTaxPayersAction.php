<?php

namespace App\Actions\Simpadu;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class GetMonthlySimpaduTaxPayersAction
{
    /**
     * Fetch Tax Payers monitoring data for a specific year and month.
     * Uses logic from DOKUMENTASI_QUERY_LAPSPTPD.md
     */
    public function __invoke(int $year, int $month, ?string $districtCode = null, ?string $search = null)
    {
        $monthStr = $month < 10 ? "0$month" : (string) $month;
        $periodFormat = "{$year}{$monthStr}"; // YYYYMM

        // 1. SPTPD Subquery (Union of 5 tables)
        $sptpdAt = DB::connection('simpadunew')->table('dat_sptpd_at')
            ->select(['nop', 'npwpd', 'jmlsptpd as jml_lapor'])
            ->whereRaw("DATE_FORMAT(masa_awal, '%Y%m') = ?", [$periodFormat]);

        $sptpdReklame = DB::connection('simpadunew')->table('dat_sptpd_reklame')
            ->select(['nop', 'npwpd', 'total as jml_lapor'])
            ->whereRaw("DATE_FORMAT(tgl_awal, '%Y%m') = ?", [$periodFormat]);

        $sptpdMinerba = DB::connection('simpadunew')->table('dat_sptpd_minerba')
            ->select(['nop', 'npwpd', 'pajak as jml_lapor'])
            ->whereRaw("DATE_FORMAT(masa_awal, '%Y%m') = ?", [$periodFormat]);

        $sptpdPpj = DB::connection('simpadunew')->table('dat_sptpd_ppj')
            ->select(['nop', 'npwpd', 'pajak as jml_lapor'])
            ->whereRaw("DATE_FORMAT(masa_awal, '%Y%m') = ?", [$periodFormat]);

        $sptpdSelf = DB::connection('simpadunew')->table('dat_sptpd_self')
            ->select(['nop', 'npwpd', 'pajak as jml_lapor'])
            ->whereRaw("DATE_FORMAT(masa, '%Y%m') = ?", [$periodFormat]);

        $sptpdUnion = $sptpdAt->unionAll($sptpdReklame)
            ->unionAll($sptpdMinerba)
            ->unionAll($sptpdPpj)
            ->unionAll($sptpdSelf);

        $sptpdSummary = DB::connection('simpadunew')->table(DB::raw("({$sptpdUnion->toSql()}) as x"))
            ->mergeBindings($sptpdUnion)
            ->select(['nop', 'npwpd', DB::raw('SUM(jml_lapor) as total_ketetapan')])
            ->groupBy(['nop', 'npwpd']);

        // 2. Payment Subquery
        $paymentSummary = DB::connection('simpadunew')->table('pembayaran')
            ->select(['nop', 'npwpd', DB::raw('SUM(jml_byr_pokok + lainlain) as total_bayar')])
            ->whereYear('tgl_bayar', $year)
            ->whereMonth('tgl_bayar', $month)
            ->groupBy(['nop', 'npwpd']);

        // 3. Main Query
        $mainQuery = DB::connection('simpadunew')->table('dat_objek_pajak as s')
            ->leftJoin('dat_subjek_pajak as sj', 'sj.npwpd', '=', 's.npwpd')
            ->leftJoinSub($sptpdSummary, 'sptpd', function ($join) {
                $join->on(DB::raw('TRIM(sptpd.nop)'), '=', DB::raw('TRIM(s.nop)'))
                     ->on(DB::raw('TRIM(sptpd.npwpd)'), '=', DB::raw('TRIM(s.npwpd)'));
            })
            ->leftJoinSub($paymentSummary, 'byr', function ($join) {
                $join->on(DB::raw('TRIM(byr.nop)'), '=', DB::raw('TRIM(s.nop)'))
                     ->on(DB::raw('TRIM(byr.npwpd)'), '=', DB::raw('TRIM(s.npwpd)'));
            })
            ->select([
                DB::raw('TRIM(s.npwpd) as npwpd'),
                DB::raw('TRIM(s.nop) as nop'),
                's.name as nm_op',
                DB::raw('COALESCE(sj.nm_wp, s.name) as nm_wp'),
                's.jalan_op as almt_op',
                's.kd_kecamatan',
                's.status',
                DB::raw('COALESCE(sptpd.total_ketetapan, 0) as total_ketetapan'),
                DB::raw('COALESCE(byr.total_bayar, 0) as total_bayar'),
                DB::raw('(COALESCE(sptpd.total_ketetapan, 0) - COALESCE(byr.total_bayar, 0)) as total_tunggakan')
            ])
            ->where('s.status', '1')
            ->when($districtCode, fn ($q) => $q->where('s.kd_kecamatan', $districtCode))
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('sj.nm_wp', 'like', "%{$search}%")
                  ->orWhere('s.name', 'like', "%{$search}%")
                  ->orWhere('s.npwpd', 'like', "%{$search}%")
                  ->orWhere('s.nop', 'like', "%{$search}%");
            }))
            ->orderBy('nm_wp');

        return $mainQuery->paginate(20)->withQueryString();
    }
}
