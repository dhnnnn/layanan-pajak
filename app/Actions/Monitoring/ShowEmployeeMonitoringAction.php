<?php

namespace App\Actions\Monitoring;

use App\Models\TaxTarget;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShowEmployeeMonitoringAction
{
    /**
     * @return array{
     *     upt: Upt,
     *     employee: User,
     *     wpData: Collection,
     *     summary: array{
     *         total_sptpd: float,
     *         total_bayar: float,
     *         total_tunggakan: float,
     *         attainment: float
     *     },
     *     availableYears: Collection,
     *     year: int,
     *     month: int,
     * }
     */
    public function __invoke(Upt $upt, User $employee, int $year, int $month): array
    {
        $employee->load('districts');

        $assignedDistrictCodes = $employee->districts->pluck('simpadu_code')->filter()->toArray();

        if (empty($assignedDistrictCodes)) {
            return $this->returnEmpty($upt, $employee, $year, $month);
        }

        // Reading from LOCAL simpadu_tax_payers table (Populated via php artisan sync:tax-payers)
        $results = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->whereIn('kd_kecamatan', $assignedDistrictCodes)
            ->orderByDesc('total_ketetapan')
            ->get();

        $wpData = collect($results)->map(function ($row) {
            return [
                'npwpd' => $row->npwpd,
                'nop' => $row->nop,
                'nm_wp' => $row->nm_wp,
                'status' => 'CEK SIMPADU', // Di tabel lokal saat ini tidak menyimpan status aktif/non-aktif, kita bisa asumsikan Aktif jika ada di tabel ini
                'status_code' => '1',
                'total_sptpd' => (float) $row->total_ketetapan,
                'total_bayar' => (float) $row->total_bayar,
                'selisih' => (float) ($row->total_bayar - $row->total_ketetapan),
                'tunggakan' => (float) ($row->total_tunggakan > 0 ? $row->total_tunggakan : 0),
            ];
        });

        $summary = [
            'total_sptpd' => (float) $wpData->sum('total_sptpd'),
            'total_bayar' => (float) $wpData->sum('total_bayar'),
            'total_tunggakan' => (float) $wpData->sum('tunggakan'),
            'attainment' => $wpData->sum('total_sptpd') > 0 ? ($wpData->sum('total_bayar') / $wpData->sum('total_sptpd')) * 100 : 0
        ];

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
            'year' => $year,
            'month' => $month,
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
            'year' => $year,
            'month' => $month,
        ];
    }
}
