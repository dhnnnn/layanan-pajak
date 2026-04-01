<?php

namespace App\Actions\Monitoring;

use App\Models\TaxTarget;
use App\Models\Upt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListUptMonitoringAction
{
    /**
     * @return array{
     *     upts: Collection,
     *     uptSptpdTotals: Collection,
     *     uptPayTotals: Collection,
     *     totalSptpd: float,
     *     totalPay: float,
     *     availableYears: Collection,
     *     year: int,
     * }
     */
    public function __invoke(int $year): array
    {
        $upts = Upt::query()
            ->withCount('employees')
            ->orderBy('code')
            ->get();

        $districtCodesByUpt = $upts->mapWithKeys(
            fn (Upt $upt) => [$upt->id => $upt->districts->pluck('simpadu_code')->filter()]
        );

        // Fetch data from LOCAL simpadu_tax_payers table
        $districtStats = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->selectRaw('kd_kecamatan, SUM(total_ketetapan) as total_sptpd, SUM(total_bayar) as total_pay')
            ->groupBy('kd_kecamatan')
            ->get();

        $districtSptpd = $districtStats->pluck('total_sptpd', 'kd_kecamatan');
        $districtPay = $districtStats->pluck('total_pay', 'kd_kecamatan');

        $uptSptpdTotals = $districtCodesByUpt->map(function ($codes) use ($districtSptpd) {
            return (float) $codes->sum(fn ($code) => (float) $districtSptpd->get($code) ?? 0);
        });

        $uptPayTotals = $districtCodesByUpt->map(function ($codes) use ($districtPay) {
            return (float) $codes->sum(fn ($code) => (float) $districtPay->get($code) ?? 0);
        });

        $uptsWithMetrics = $upts->map(function ($upt) use ($uptSptpdTotals, $uptPayTotals) {
            $sptpd = $uptSptpdTotals->get($upt->id) ?? 0;
            $pay = $uptPayTotals->get($upt->id) ?? 0;
            $pct = $sptpd > 0 ? ($pay / $sptpd) * 100 : 0;

            $upt->sptpd_total = $sptpd;
            $upt->pay_total = $pay;
            $upt->attainment_pct = $pct;

            return $upt;
        })->sortByDesc('attainment_pct')->values();

        $totalSptpd = (float) $uptSptpdTotals->sum();
        $totalPay = (float) $uptPayTotals->sum();

        $availableYears = TaxTarget::query()->select('year')->distinct()->orderByDesc('year')->pluck('year');

        return [
            'upts' => $uptsWithMetrics,
            'uptSptpdTotals' => $uptSptpdTotals,
            'uptPayTotals' => $uptPayTotals,
            'totalSptpd' => $totalSptpd,
            'totalPay' => $totalPay,
            'availableYears' => $availableYears,
            'year' => $year,
        ];
    }
}
