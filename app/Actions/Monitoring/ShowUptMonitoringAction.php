<?php

namespace App\Actions\Monitoring;

use App\Models\TaxTarget;
use App\Models\TaxType;
use App\Models\Upt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ShowUptMonitoringAction
{
    /**
     * @return array{
     *     upt: Upt,
     *     employeeData: Collection,
     *     uptSptpd: float,
     *     uptPay: float,
     *     availableYears: Collection,
     *     months: array<int, string>,
     *     year: int,
     *     month: int,
     *     taxTypes: Collection,
     * }
     */
    public function __invoke(Upt $upt, int $year, int $month): array
    {
        $cacheKey = "monitoring:upt:{$upt->id}:{$year}";

        $result = Cache::remember($cacheKey, now()->addHours(3), function () use ($upt, $year, $month) {
            return $this->build($upt, $year, $month);
        });

        $result['availableYears'] = TaxTarget::query()->select('year')->distinct()->pluck('year')
            ->merge(DB::table('simpadu_tax_payers')->distinct()->pluck('year'))
            ->unique()
            ->sortDesc()
            ->values();

        $result['taxTypes'] = TaxType::query()
            ->whereNull('parent_id')
            ->whereNotNull('simpadu_code')
            ->where('simpadu_code', 'not like', '41407%')
            ->orderBy('name')
            ->get(['id', 'name', 'simpadu_code']);

        return $result;
    }

    private function build(Upt $upt, int $year, int $month): array
    {
        $upt->load([
            'districts',
            'users' => fn ($q) => $q->role('pegawai')->with('districts'),
        ]);

        $uptDistrictCodes = $upt->districts->pluck('simpadu_code')->filter()->toArray();

        // Fetch data from LOCAL simpadu_tax_payers table (Filtered by Status: 1/Active)
        // Jika ada data month=0 (summary tahunan), gunakan itu. Jika tidak (data historis),
        // aggregate dari semua bulan (1-12).
        $hasMonthZero = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->where('status', '1')
            ->where('month', 0)
            ->exists();

        $districtStats = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->where('status', '1')
            ->when($hasMonthZero, fn ($q) => $q->where('month', 0), fn ($q) => $q->where('month', '>', 0))
            ->whereIn('kd_kecamatan', $uptDistrictCodes)
            ->selectRaw('kd_kecamatan, SUM(total_ketetapan) as total_sptpd, SUM(total_bayar) as total_pay')
            ->groupBy('kd_kecamatan')
            ->get();

        $districtSptpd = $districtStats->pluck('total_sptpd', 'kd_kecamatan');
        $districtPay = $districtStats->pluck('total_pay', 'kd_kecamatan');

        // 2. Map metrics to Officers
        $employeeData = $upt->users->map(function ($employee) use ($districtSptpd, $districtPay) {
            $codes = $employee->districts->pluck('simpadu_code')->filter();

            $sptpd = (float) $codes->sum(fn ($code) => (float) $districtSptpd->get($code) ?? 0);
            $pay = (float) $codes->sum(fn ($code) => (float) $districtPay->get($code) ?? 0);
            $pct = $sptpd > 0 ? ($pay / $sptpd) * 100 : 0;

            return [
                'employee' => $employee,
                'sptpd_total' => $sptpd,
                'pay_total' => $pay,
                'attainment_pct' => $pct,
                'districts_count' => $codes->count(),
            ];
        })->sortByDesc('attainment_pct')->values();

        $uptSptpd = (float) collect($uptDistrictCodes)->sum(fn ($code) => (float) $districtSptpd->get($code) ?? 0);
        $uptPay = (float) collect($uptDistrictCodes)->sum(fn ($code) => (float) $districtPay->get($code) ?? 0);

        $availableYears = collect();

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return [
            'upt' => $upt,
            'employeeData' => $employeeData,
            'uptSptpd' => $uptSptpd,
            'uptPay' => $uptPay,
            'availableYears' => $availableYears,
            'months' => $months,
            'year' => $year,
            'month' => $month,
        ];
    }
}
