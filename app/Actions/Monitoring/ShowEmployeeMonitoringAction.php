<?php

namespace App\Actions\Monitoring;

use App\Models\TaxTarget;
use App\Models\Upt;
use App\Models\User;
use App\Models\TaxType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShowEmployeeMonitoringAction
{
    /**
     * @return array{
     *     upt: Upt,
     *     employee: User,
     *     wpData: LengthAwarePaginator,
     *     summary: array{
     *         total_sptpd: float,
     *         total_bayar: float,
     *         total_tunggakan: float,
     *         attainment: float
     *     },
     *     availableYears: Collection,
     *     year: int,
     *     month: int,
     *     sortBy: string,
     *     sortDir: string,
     * }
     */
    public function __invoke(
        Upt $upt, 
        User $employee, 
        int $year, 
        int $month, 
        ?string $search = null,
        ?string $sortBy = 'tunggakan',
        ?string $sortDir = 'desc',
        ?string $taxTypeId = null,
        string $statusFilter = '1'  // '1' = aktif, '0' = non aktif, 'all' = semua
    ): array {
        $employee->load('districts');

        $assignedDistrictCodes = $employee->districts->pluck('simpadu_code')->filter()->toArray();

        if (empty($assignedDistrictCodes)) {
            return $this->returnEmpty($upt, $employee, $year, $month);
        }

        // 1. Fetch Primary Tax Types for filter (Level 1 & 2 only)
        $taxTypes = TaxType::query()
            ->whereNull('parent_id')
            ->whereNotNull('simpadu_code')
            ->orderBy('name')
            ->get(['id', 'name', 'simpadu_code']);

        $selectedTaxType = $taxTypeId ? $taxTypes->firstWhere('id', $taxTypeId) : null;

        // 2. Calculate Summary (Sensitive to tax type filter)
        $summaryQuery = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->whereIn('kd_kecamatan', $assignedDistrictCodes);

        if ($statusFilter !== 'all') {
            $summaryQuery->where('status', $statusFilter);
        }
        
        if ($selectedTaxType) {
            $summaryQuery->where('ayat', $selectedTaxType->simpadu_code);
        }

        $summaryResults = $summaryQuery
            ->selectRaw('SUM(total_ketetapan) as total_sptpd, SUM(total_bayar) as total_bayar, SUM(CASE WHEN total_tunggakan > 0 THEN total_tunggakan ELSE 0 END) as total_tunggakan')
            ->first();

        $summary = [
            'total_sptpd' => (float) ($summaryResults->total_sptpd ?? 0),
            'total_bayar' => (float) ($summaryResults->total_bayar ?? 0),
            'total_tunggakan' => (float) ($summaryResults->total_tunggakan ?? 0),
            'attainment' => ($summaryResults->total_sptpd ?? 0) > 0 
                ? ($summaryResults->total_bayar / $summaryResults->total_sptpd) * 100 
                : 0
        ];

        // 3. Fetch Paginated, Filtered & Sorted WP Data
        // Aggregate by npwpd+nop (SUM across all months) to avoid duplicate rows per month
        $orderDir = in_array(strtolower($sortDir), ['asc', 'desc']) ? $sortDir : 'desc';

        $rawSortCols = [
            'selisih' => '(SUM(stp.total_bayar) - SUM(stp.total_ketetapan))',
        ];
        $plainSortCols = [
            'name'     => 'nm_wp',
            'sptpd'    => 'total_ketetapan',
            'bayar'    => 'total_bayar',
            'tunggakan'=> 'total_tunggakan',
        ];

        $query = DB::table('simpadu_tax_payers as stp')
            ->leftJoin('tax_types', 'tax_types.simpadu_code', '=', 'stp.ayat')
            ->where('stp.year', $year)
            ->whereIn('stp.kd_kecamatan', $assignedDistrictCodes)
            ->when($statusFilter !== 'all', fn($q) => $q->where('stp.status', $statusFilter))
            ->when($selectedTaxType, fn($q) => $q->where('stp.ayat', $selectedTaxType->simpadu_code))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('stp.nm_wp', 'like', "%{$search}%")
                      ->orWhere('stp.npwpd', 'like', "%{$search}%")
                      ->orWhere('stp.nop', 'like', "%{$search}%");
                });
            })
            ->groupBy('stp.npwpd', 'stp.nop', 'stp.nm_wp', 'stp.nm_op', 'stp.almt_op',
                      'stp.kd_kecamatan', 'stp.ayat', 'stp.status', 'tax_types.name')
            ->selectRaw('
                stp.npwpd, stp.nop, stp.nm_wp, stp.nm_op, stp.almt_op,
                stp.kd_kecamatan, stp.ayat, stp.status,
                tax_types.name as tax_type_name,
                SUM(stp.total_ketetapan) as total_ketetapan,
                LEAST(SUM(stp.total_bayar), SUM(stp.total_ketetapan)) as total_bayar,
                GREATEST(SUM(stp.total_ketetapan) - SUM(stp.total_bayar), 0) as total_tunggakan
            ');

        if (isset($rawSortCols[$sortBy])) {
            $query->orderByRaw($rawSortCols[$sortBy] . ' ' . $orderDir);
        } else {
            $col = $plainSortCols[$sortBy] ?? 'total_ketetapan';
            $query->orderBy($col, $orderDir);
        }

        $wpData = $query->paginate(15)->through(function ($row) {
            $statusStr = (string) $row->status;
            $isActive = $statusStr === '1';

            return [
                'npwpd' => $row->npwpd,
                'nop' => $row->nop,
                'nm_wp' => $row->nm_wp,
                'tax_type_name' => $row->tax_type_name,
                'status' => $isActive ? 'AKTIF' : 'NON AKTIF',
                'status_code' => $statusStr,
                'total_sptpd' => (float) $row->total_ketetapan,
                'total_bayar' => (float) $row->total_bayar,
                'selisih' => (float) ($row->total_bayar - $row->total_ketetapan),
                'tunggakan' => (float) ($row->total_tunggakan > 0 ? $row->total_tunggakan : 0),
            ];
        });

        $availableYears = TaxTarget::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return [
            'upt' => $upt,
            'employee' => $employee,
            'wpData' => $wpData,
            'summary' => $summary,
            'availableYears' => $availableYears,
            'taxTypes' => $taxTypes,
            'year' => $year,
            'month' => $month,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'taxTypeId' => $taxTypeId,
            'statusFilter' => $statusFilter,
        ];
    }

    private function returnEmpty(Upt $upt, User $employee, int $year, int $month): array
    {
        return [
            'upt' => $upt,
            'employee' => $employee,
            'wpData' => collect(),
            'summary' => [
                'total_sptpd' => 0,
                'total_bayar' => 0,
                'total_tunggakan' => 0,
                'attainment' => 0,
            ],
            'availableYears' => collect([$year]),
            'taxTypes' => collect(),
            'year' => $year,
            'month' => $month,
            'sortBy' => 'tunggakan',
            'sortDir' => 'desc',
            'taxTypeId' => null,
            'statusFilter' => '1',
        ];
    }
}
