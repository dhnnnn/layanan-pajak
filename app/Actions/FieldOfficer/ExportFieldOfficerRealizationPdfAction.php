<?php

namespace App\Actions\FieldOfficer;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ExportFieldOfficerRealizationPdfAction
{
    public function execute(User $user, int $year): Response
    {
        $districtCodes = $user->accessibleDistricts()->pluck('simpadu_code')->filter()->toArray();

        $summaryRaw = DB::table('simpadu_tax_payers')
            ->where('year', $year)->where('month', 0)->where('status', '1')
            ->whereIn('kd_kecamatan', $districtCodes)
            ->selectRaw('SUM(total_ketetapan) as total_sptpd, SUM(total_bayar) as total_bayar, SUM(CASE WHEN total_tunggakan > 0 THEN total_tunggakan ELSE 0 END) as total_tunggakan')
            ->first();

        $summary = [
            'total_sptpd' => (float) ($summaryRaw->total_sptpd ?? 0),
            'total_bayar' => (float) ($summaryRaw->total_bayar ?? 0),
            'total_tunggakan' => (float) ($summaryRaw->total_tunggakan ?? 0),
        ];

        $wpList = DB::table('simpadu_tax_payers as stp')
            ->leftJoin('tax_types', 'tax_types.simpadu_code', '=', 'stp.ayat')
            ->where('stp.year', $year)->where('stp.month', 0)->where('stp.status', '1')
            ->whereIn('stp.kd_kecamatan', $districtCodes)
            ->where('stp.total_tunggakan', '>', 0)
            ->selectRaw('stp.npwpd, stp.nop, stp.nm_wp, stp.kd_kecamatan, stp.ayat, tax_types.name as jenis_pajak, SUM(stp.total_ketetapan) as total_ketetapan, SUM(stp.total_bayar) as total_bayar, SUM(stp.total_tunggakan) as total_tunggakan')
            ->groupBy('stp.npwpd', 'stp.nop', 'stp.nm_wp', 'stp.kd_kecamatan', 'stp.ayat', 'tax_types.name')
            ->orderByDesc('total_tunggakan')
            ->get();

        $monthlyData = DB::table('simpadu_tax_payers')
            ->where('year', $year)->where('month', '>', 0)->where('status', '1')
            ->whereIn('kd_kecamatan', $districtCodes)
            ->where('total_ketetapan', '>', 0)
            ->get()
            ->groupBy(fn ($r) => $r->npwpd.'|'.$r->nop);

        $employee = $user;
        $employee->load('districts');
        $upt = $user->upt;

        $pdf = Pdf::loadView('admin.realization-monitoring.employee-pdf', compact(
            'upt', 'employee', 'year', 'summary', 'wpList', 'monthlyData'
        ))->setPaper('a4', 'portrait');

        $filename = 'monitoring-realisasi-'.str_replace(' ', '-', strtolower($user->name))."-{$year}.pdf";

        return $pdf->download($filename);
    }
}
