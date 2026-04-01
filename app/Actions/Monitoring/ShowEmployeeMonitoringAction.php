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
        ?string $sortBy = 'sptpd',
        ?string $sortDir = 'desc',
        ?string $taxTypeId = null
    ): array {
        $employee->load('districts');

        $assignedDistrictCodes = $employee->districts->pluck('simpadu_code')->filter()->toArray();

        if (empty($assignedDistrictCodes)) {
            return $this->returnEmpty($upt, $employee, $year, $month);
        }

        // 1. Fetch Primary Tax Types for filter (Level 1 & 2 only)
        $taxTypes = TaxType::query()
            ->whereNotNull('simpadu_code')
            ->where(function($q) {
                // Main categories: Roots or direct children of roots
                $q->whereNull('parent_id')
                  ->orWhereIn('parent_id', TaxType::whereNull('parent_id')->pluck('id'));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'simpadu_code']);

        $selectedTaxType = $taxTypeId ? $taxTypes->firstWhere('id', $taxTypeId) : null;

        // 2. Calculate Summary (Sensitive to tax type filter)
        $summaryQuery = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->whereIn('kd_kecamatan', $assignedDistrictCodes);
        
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
        $sortMapping = [
            'name' => 'nm_wp',
            'sptpd' => 'total_ketetapan',
            'bayar' => 'total_bayar',
            'selisih' => DB::raw('(total_bayar - total_ketetapan)'),
            'tunggakan' => 'total_tunggakan',
        ];

        $orderCol = $sortMapping[$sortBy] ?? 'total_ketetapan';
        $orderDir = in_array(strtolower($sortDir), ['asc', 'desc']) ? $sortDir : 'desc';

        $query = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->whereIn('kd_kecamatan', $assignedDistrictCodes)
            ->when($selectedTaxType, fn($q) => $q->where('ayat', $selectedTaxType->simpadu_code))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('nm_wp', 'like', "%{$search}%")
                      ->orWhere('npwpd', 'like', "%{$search}%")
                      ->orWhere('nop', 'like', "%{$search}%");
                });
            })
            ->orderBy($orderCol, $orderDir);

        $wpData = $query->paginate(15)->through(function ($row) {
            return [
                'npwpd' => $row->npwpd,
                'nop' => $row->nop,
                'nm_wp' => $row->nm_wp,
                'status' => 'CEK SIMPADU',
                'status_code' => '1',
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
            'sortBy' => 'sptpd',
            'sortDir' => 'desc',
            'taxTypeId' => null,
        ];
    }
}
