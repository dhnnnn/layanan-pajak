<?php

namespace App\Actions\Simpadu;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fetches all tax payer matrix data (no pagination) for Excel export.
 * Uses whereIn on composite key to avoid N+1 and massive orWhere loops.
 */
class GetTaxPayerMatrixAllAction
{
    public function __invoke(
        int $year,
        int $startMonth,
        int $endMonth,
        ?string $search = null,
        ?array $districtCodes = null,
        string $statusFilter = '1',
        ?string $ayat = null
    ): Collection {
        $months = range($startMonth, $endMonth);

        // 1. Fetch base WP list (all, no pagination)
        $items = DB::table('simpadu_tax_payers as s')
            ->leftJoin('tax_types', 'tax_types.simpadu_code', '=', 's.ayat')
            ->select([
                's.npwpd', 's.nop', 's.nm_wp', 's.nm_op',
                's.almt_op', 's.kd_kecamatan', 's.status',
                'tax_types.name as tax_type_name',
            ])
            ->where('s.year', $year)
            ->where('s.month', 0)
            ->when(! empty($search), fn ($q) => $q->where(fn ($sq) => $sq
                ->where('s.nm_wp', 'like', "%{$search}%")
                ->orWhere('s.npwpd', 'like', "%{$search}%")
                ->orWhere('s.nm_op', 'like', "%{$search}%")
            ))
            ->when($districtCodes, fn ($q) => $q->whereIn('s.kd_kecamatan', $districtCodes))
            ->when($statusFilter !== 'all', fn ($q) => $q->where('s.status', $statusFilter))
            ->when($ayat, fn ($q) => $q->where('s.ayat', $ayat))
            ->groupBy(['s.npwpd', 's.nop', 's.nm_wp', 's.nm_op', 's.almt_op', 's.kd_kecamatan', 's.status', 'tax_types.name'])
            ->orderBy('s.nm_wp')
            ->get();

        if ($items->isEmpty()) {
            return $items;
        }

        // 2. Collect unique npwpd and nop lists for whereIn queries
        $npwpdList = $items->pluck('npwpd')->unique()->values()->toArray();
        $nopList = $items->pluck('nop')->unique()->values()->toArray();

        // 3. Fetch all reports in one query using whereIn (much faster than orWhere loop)
        $reports = DB::table('simpadu_sptpd_reports')
            ->where('year', $year)
            ->whereBetween('month', [$startMonth, $endMonth])
            ->whereIn('npwpd', $npwpdList)
            ->whereIn('nop', $nopList)
            ->get(['npwpd', 'nop', 'month', 'tgl_lapor', 'jml_lapor'])
            ->groupBy(fn ($r) => "{$r->npwpd}-{$r->nop}");

        // 4. Fetch all payments in one query
        $payments = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->whereBetween('month', [$startMonth, $endMonth])
            ->whereIn('npwpd', $npwpdList)
            ->whereIn('nop', $nopList)
            ->get(['npwpd', 'nop', 'month', 'total_bayar'])
            ->groupBy(fn ($r) => "{$r->npwpd}-{$r->nop}");

        // 5. Fetch district names
        $districts = DB::connection('simpadunew')
            ->table('ref_kecamatan')
            ->pluck('nm_kecamatan', 'kd_kecamatan');

        // 6. Map monthly data onto each WP
        return $items->map(function ($wp) use ($reports, $payments, $months, $districts) {
            $key = "{$wp->npwpd}-{$wp->nop}";
            $wpReports = $reports->get($key, collect());
            $wpPayments = $payments->get($key, collect());

            $wp->monthly_data = [];
            foreach ($months as $m) {
                $report = $wpReports->firstWhere('month', $m);
                $payment = $wpPayments->firstWhere('month', $m);
                $wp->monthly_data[$m] = [
                    'tgl_lapor' => $report?->tgl_lapor ? Carbon::parse($report->tgl_lapor)->format('d-m-Y') : '-',
                    'jml_lapor' => (float) ($report?->jml_lapor ?: 0),
                    'total_bayar' => (float) ($payment?->total_bayar ?: 0),
                ];
            }

            $wp->nm_kecamatan = $districts->get($wp->kd_kecamatan, '-');

            return $wp;
        });
    }
}
