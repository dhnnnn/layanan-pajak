<?php

namespace App\Actions\FieldOfficer;

use App\Models\TaxType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GetFieldOfficerTargetAchievementAction
{
    public function execute(User $user, array $params): array
    {
        $year = $params['year'] ?? (int) date('Y');
        $search = $params['search'] ?? null;
        $sortBy = $params['sort_by'] ?? 'tunggakan';
        $sortDir = $params['sort_dir'] ?? 'desc';
        $statusFilter = $params['status_filter'] ?? '1';
        $taxTypeId = $params['tax_type_id'] ?? null;
        $districtId = $params['district_id'] ?? null;

        $assignedDistricts = $user->accessibleDistricts()->orderBy('name')->get();
        $allAssignedDistrictCodes = $assignedDistricts->pluck('simpadu_code')->filter()->toArray();

        $selectedDistrict = $districtId ? $assignedDistricts->firstWhere('id', $districtId) : null;
        if ($selectedDistrict === false) {
            $selectedDistrict = null;
        }

        $activeDistrictCodes = $selectedDistrict
            ? [$selectedDistrict->simpadu_code]
            : $allAssignedDistrictCodes;

        $taxTypes = TaxType::query()
            ->whereNull('parent_id')
            ->whereNotNull('simpadu_code')
            ->orderBy('name')
            ->get(['id', 'name', 'simpadu_code']);

        $selectedTaxType = $taxTypeId ? $taxTypes->firstWhere('id', $taxTypeId) : null;

        if (empty($allAssignedDistrictCodes)) {
            return [
                'summary' => ['total_ketetapan' => 0, 'total_bayar' => 0, 'total_tunggakan' => 0, 'persentase' => 0],
                'wpData' => collect(),
                'year' => $year, 'sortBy' => $sortBy, 'sortDir' => $sortDir,
                'statusFilter' => $statusFilter, 'availableYears' => (new GetAvailableYearsAction)->execute(),
                'taxTypes' => $taxTypes, 'taxTypeId' => $taxTypeId,
                'assignedDistricts' => collect(),
                'districtId' => null,
            ];
        }

        // Summary - apply tax type and district filter
        $summaryQuery = DB::table('simpadu_tax_payers')
            ->where('year', $year)->where('status', '1')->where('month', 0)
            ->whereIn('kd_kecamatan', $activeDistrictCodes);

        if ($selectedTaxType) {
            $summaryQuery->where('ayat', $selectedTaxType->simpadu_code);
        }

        $stats = $summaryQuery
            ->selectRaw('COALESCE(SUM(total_ketetapan),0) as k, COALESCE(SUM(total_bayar),0) as b, COALESCE(SUM(total_tunggakan),0) as t')
            ->first();

        $pct = $stats->k > 0 ? ($stats->b / $stats->k) * 100 : 0;

        // WP list
        $plainSortCols = ['name' => 'nm_wp', 'sptpd' => 'total_ketetapan', 'bayar' => 'total_bayar', 'tunggakan' => 'total_tunggakan'];
        $rawSortCols = ['selisih' => '(SUM(stp.total_bayar) - SUM(stp.total_ketetapan))'];

        $query = DB::table('simpadu_tax_payers as stp')
            ->leftJoin('tax_types', 'tax_types.simpadu_code', '=', 'stp.ayat')
            ->where('stp.year', $year)
            ->where('stp.month', 0)
            ->whereIn('stp.kd_kecamatan', $activeDistrictCodes)
            ->when($statusFilter !== 'all', fn ($q) => $q->where('stp.status', $statusFilter))
            ->when($selectedTaxType, fn ($q) => $q->where('stp.ayat', $selectedTaxType->simpadu_code))
            ->when($search, fn ($q) => $q->where(fn ($sq) => $sq->where('stp.nm_wp', 'like', "%{$search}%")
                ->orWhere('stp.npwpd', 'like', "%{$search}%")
                ->orWhere('stp.nop', 'like', "%{$search}%")
            ))
            ->groupBy('stp.npwpd', 'stp.nop', 'stp.nm_wp', 'stp.nm_op', 'stp.ayat', 'stp.status', 'tax_types.name')
            ->selectRaw('stp.npwpd, stp.nop, stp.nm_wp, stp.nm_op, stp.ayat, stp.status, tax_types.name as tax_type_name,
                SUM(stp.total_ketetapan) as total_ketetapan,
                LEAST(SUM(stp.total_bayar), SUM(stp.total_ketetapan)) as total_bayar,
                GREATEST(SUM(stp.total_ketetapan) - SUM(stp.total_bayar), 0) as total_tunggakan');

        if (isset($rawSortCols[$sortBy])) {
            $query->orderByRaw($rawSortCols[$sortBy].' '.($sortDir === 'asc' ? 'asc' : 'desc'));
        } else {
            $query->orderBy($plainSortCols[$sortBy] ?? 'total_tunggakan', $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $wpData = $query->paginate(15)->through(fn ($r) => [
            'npwpd' => $r->npwpd, 'nop' => $r->nop, 'nm_wp' => $r->nm_wp,
            'tax_type_name' => $r->tax_type_name,
            'status_code' => (string) $r->status,
            'total_sptpd' => (float) $r->total_ketetapan,
            'total_bayar' => (float) $r->total_bayar,
            'selisih' => (float) ($r->total_bayar - $r->total_ketetapan),
            'tunggakan' => (float) max($r->total_tunggakan, 0),
        ]);

        return [
            'summary' => [
                'total_ketetapan' => $stats->k,
                'total_bayar' => $stats->b,
                'total_tunggakan' => $stats->t,
                'persentase' => $pct,
            ],
            'wpData' => $wpData,
            'year' => $year,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'statusFilter' => $statusFilter,
            'availableYears' => (new GetAvailableYearsAction)->execute(),
            'taxTypes' => $taxTypes,
            'taxTypeId' => $taxTypeId,
            'assignedDistricts' => $assignedDistricts,
            'districtId' => $districtId,
        ];
    }
}
