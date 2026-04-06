<?php

namespace App\Actions\FieldOfficer;

use App\Exports\FieldOfficerTargetExport;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportFieldOfficerTargetExcelAction
{
    public function execute(User $user, array $params): BinaryFileResponse
    {
        $year = $params['year'] ?? (int) date('Y');
        $search = $params['search'] ?? null;
        $statusFilter = $params['status_filter'] ?? '1';
        $taxTypeId = $params['tax_type_id'] ?? null;
        $districtId = $params['district_id'] ?? null;

        $assignedDistricts = $user->accessibleDistricts()->orderBy('name')->get();
        $allAssignedDistrictCodes = $assignedDistricts->pluck('simpadu_code')->filter()->toArray();

        $selectedDistrict = $districtId ? $assignedDistricts->firstWhere('id', $districtId) : null;
        $activeDistrictCodes = $selectedDistrict
            ? [$selectedDistrict->simpadu_code]
            : $allAssignedDistrictCodes;

        $taxTypes = TaxType::query()->whereNull('parent_id')->whereNotNull('simpadu_code')->get(['id', 'simpadu_code']);
        $selectedTaxType = $taxTypeId ? $taxTypes->firstWhere('id', $taxTypeId) : null;

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
            ->groupBy('stp.npwpd', 'stp.nop', 'stp.nm_wp', 'stp.ayat', 'stp.status', 'tax_types.name')
            ->selectRaw('stp.npwpd, stp.nop, stp.nm_wp, stp.ayat, stp.status, tax_types.name as tax_type_name,
                SUM(stp.total_ketetapan) as total_ketetapan,
                LEAST(SUM(stp.total_bayar), SUM(stp.total_ketetapan)) as total_bayar,
                GREATEST(SUM(stp.total_ketetapan) - SUM(stp.total_bayar), 0) as total_tunggakan')
            ->orderByDesc('total_tunggakan')
            ->get()
            ->map(fn ($r) => [
                'npwpd' => $r->npwpd,
                'nop' => $r->nop,
                'nm_wp' => $r->nm_wp,
                'tax_type_name' => $r->tax_type_name,
                'status_code' => (string) $r->status,
                'total_sptpd' => (float) $r->total_ketetapan,
                'total_bayar' => (float) $r->total_bayar,
                'tunggakan' => (float) max($r->total_tunggakan, 0),
            ]);

        $districtName = $selectedDistrict
            ? $selectedDistrict->name
            : $assignedDistricts->pluck('name')->implode(', ');

        $filename = 'pencapaian-target-'.str_replace(' ', '-', strtolower($user->name))."-{$year}.xlsx";

        return Excel::download(
            new FieldOfficerTargetExport(collect($query), $year, $user->name, $districtName),
            $filename
        );
    }
}
