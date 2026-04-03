<?php

namespace App\Actions\Monitoring;

use App\Models\TaxTarget;
use App\Models\Upt;
use App\Models\User;
use App\Models\TaxType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ShowEmployeeMonitoringAction
{
    /**
     * @return array
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
        string $statusFilter = '1',
        ?string $districtId = null
    ): array {
        $employee->load('districts');

        $assignedDistricts = $employee->districts;
        $allAssignedDistrictCodes = $assignedDistricts->pluck('simpadu_code')->filter()->toArray();

        // Determine which district codes to filter by
        $selectedDistrict = $districtId ? $assignedDistricts->firstWhere('id', $districtId) : null;
        $activeDistrictCodes = $selectedDistrict 
            ? [$selectedDistrict->simpadu_code] 
            : $allAssignedDistrictCodes;

        if (empty($allAssignedDistrictCodes)) {
            return $this->returnEmpty($upt, $employee, $year, $month);
        }

        // 1. Fetch Primary Tax Types for filter
        $taxTypes = TaxType::query()
            ->whereNull('parent_id')
            ->whereNotNull('simpadu_code')
            ->orderBy('name')
            ->get(['id', 'name', 'simpadu_code']);

        $selectedTaxType = $taxTypeId ? $taxTypes->firstWhere('id', $taxTypeId) : null;

        // 2. Calculate Summary Statistics
        $summaryQuery = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->where('month', 0)
            ->whereIn('kd_kecamatan', $activeDistrictCodes);

        if ($statusFilter !== 'all') {
            $summaryQuery->where('status', (string) $statusFilter);
        }
        
        if ($selectedTaxType) {
            $summaryQuery->where('ayat', $selectedTaxType->simpadu_code);
        }

        $summaryResults = $summaryQuery
            ->selectRaw('
                SUM(total_ketetapan) as total_sptpd, 
                SUM(total_bayar) as total_bayar, 
                SUM(CASE WHEN total_tunggakan > 0 THEN total_tunggakan ELSE 0 END) as total_tunggakan
            ')
            ->first();

        $totalSptpd = (float) ($summaryResults->total_sptpd ?? 0);
        $totalBayar = (float) ($summaryResults->total_bayar ?? 0);
        $totalTunggakan = (float) ($summaryResults->total_tunggakan ?? 0);

        $summary = [
            'total_sptpd' => $totalSptpd,
            'total_bayar' => $totalBayar,
            'total_tunggakan' => $totalTunggakan,
            'attainment' => $totalSptpd > 0 ? ($totalBayar / $totalSptpd) * 100 : 0
        ];

        // 3. WP Data Query
        $orderDir = in_array(strtolower($sortDir ?? ''), ['asc', 'desc']) ? $sortDir : 'desc';

        // Map sort columns to database expressions
        if ($sortBy === 'selisih') {
            $orderBy = DB::raw('(SUM(stp.total_bayar) - SUM(stp.total_ketetapan)) ' . $orderDir);
        } elseif ($sortBy === 'name') {
            $orderBy = 'stp.nm_wp';
        } elseif ($sortBy === 'sptpd') {
            $orderBy = 'total_ketetapan';
        } elseif ($sortBy === 'bayar') {
            $orderBy = 'total_bayar';
        } elseif ($sortBy === 'tunggakan') {
            $orderBy = 'total_tunggakan';
        } else {
            $orderBy = 'total_tunggakan'; // Default sort
        }

        $query = DB::table('simpadu_tax_payers as stp')
            ->leftJoin('tax_types', 'tax_types.simpadu_code', '=', 'stp.ayat')
            ->where('stp.year', $year)
            ->where('stp.month', 0)
            ->whereIn('stp.kd_kecamatan', $activeDistrictCodes)
            ->when($statusFilter !== 'all', fn($q) => $q->where('stp.status', (string) $statusFilter))
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
                (CASE WHEN SUM(stp.total_bayar) > SUM(stp.total_ketetapan) THEN SUM(stp.total_ketetapan) ELSE SUM(stp.total_bayar) END) as total_bayar,
                (CASE WHEN SUM(stp.total_ketetapan) > SUM(stp.total_bayar) THEN SUM(stp.total_ketetapan) - SUM(stp.total_bayar) ELSE 0 END) as total_tunggakan
            ');

        if ($orderBy instanceof \Illuminate\Database\Query\Expression) {
            $query->orderByRaw($orderBy);
        } else {
            $query->orderBy($orderBy, $orderDir);
        }

        $wpData = $query->paginate(15)->through(function ($row) {
            $statusStr = (string) ($row->status ?? '0');
            return [
                'npwpd' => $row->npwpd,
                'nop' => $row->nop,
                'nm_wp' => $row->nm_wp,
                'tax_type_name' => $row->tax_type_name,
                'status' => $statusStr === '1' ? 'AKTIF' : 'NON AKTIF',
                'status_code' => $statusStr,
                'total_sptpd' => (float) $row->total_ketetapan,
                'total_bayar' => (float) $row->total_bayar,
                'selisih' => (float) ($row->total_bayar - $row->total_ketetapan),
                'tunggakan' => (float) $row->total_tunggakan,
            ];
        });

        $availableYears = DB::table('simpadu_tax_payers')
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
            'assignedDistricts' => $assignedDistricts,
            'year' => $year,
            'month' => $month,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'taxTypeId' => $taxTypeId,
            'statusFilter' => $statusFilter,
            'districtId' => $districtId,
        ];
    }

    private function returnEmpty(Upt $upt, User $employee, int $year, int $month): array
    {
        return [
            'upt' => $upt,
            'employee' => $employee,
            'wpData' => new LengthAwarePaginator([], 0, 15),
            'summary' => [
                'total_sptpd' => 0,
                'total_bayar' => 0,
                'total_tunggakan' => 0,
                'attainment' => 0,
            ],
            'availableYears' => collect([$year]),
            'taxTypes' => collect(),
            'assignedDistricts' => $employee->districts ?? collect(),
            'year' => $year,
            'month' => $month,
            'sortBy' => 'tunggakan',
            'sortDir' => 'desc',
            'taxTypeId' => null,
            'statusFilter' => '1',
            'districtId' => null,
        ];
    }
}
