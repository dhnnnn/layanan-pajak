<?php

namespace App\Actions\Simpadu;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class GetTaxPayerMatrixAction
{
    /**
     * Fetch Tax Payers matrix data from local database.
     */
    public function __invoke(int $year, int $startMonth, int $endMonth, ?string $search = null, ?array $districtCodes = null)
    {
        // 1. Define months in range
        $months = range($startMonth, $endMonth);

        // 2. Fetch Base WP/OP data from simpadu_tax_payers
        // We Use distinct to get unique WP/OP in the selected year across any month
        $query = DB::table('simpadu_tax_payers as s')
            ->select([
                's.npwpd', 
                's.nop', 
                's.nm_wp', 
                's.nm_op', 
                's.almt_op', 
                's.kd_kecamatan',
                's.status'
            ])
            ->where('s.year', $year)
            ->when(strlen((string) $search) >= 3, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('s.nm_wp', 'like', "%{$search}%")
                      ->orWhere('s.npwpd', 'like', "%{$search}%")
                      ->orWhere('s.nm_op', 'like', "%{$search}%");
                });
            })
            ->when($districtCodes, fn($q) => $q->whereIn('s.kd_kecamatan', $districtCodes))
            ->groupBy(['s.npwpd', 's.nop', 's.nm_wp', 's.nm_op', 's.almt_op', 's.kd_kecamatan', 's.status'])
            ->orderBy('s.nm_wp');

        $paginated = $query->paginate(20)->withQueryString();

        // 3. Extract unique pairs of (npwpd, nop) from current page to fetch reports
        $pairs = collect($paginated->items())->map(fn($item) => [
            'npwpd' => $item->npwpd,
            'nop'   => $item->nop,
        ]);

        if ($pairs->isEmpty()) {
            return $paginated;
        }

        // 4. Fetch Reports for these pairs in the selected range
        $reports = DB::table('simpadu_sptpd_reports')
            ->where('year', $year)
            ->whereBetween('month', [$startMonth, $endMonth])
            ->where(function($q) use ($pairs) {
                foreach ($pairs as $pair) {
                    $q->orWhere(function($sq) use ($pair) {
                        $sq->where('npwpd', $pair['npwpd'])->where('nop', $pair['nop']);
                    });
                }
            })
            ->get()
            ->groupBy(fn($r) => "{$r->npwpd}-{$r->nop}");

        // 5. Fetch district names for display
        $districts = DB::connection('simpadunew')->table('ref_kecamatan')->pluck('nm_kecamatan', 'kd_kecamatan');

        // 6. Map reports into each paginated item
        $paginated->getCollection()->transform(function ($wp) use ($reports, $months, $districts) {
            $key = "{$wp->npwpd}-{$wp->nop}";
            $wpReports = $reports->get($key, collect());
            
            $wp->monthly_data = [];
            foreach ($months as $m) {
                $report = $wpReports->firstWhere('month', $m);
                $wp->monthly_data[$m] = [
                    'tgl_lapor'  => $report?->tgl_lapor ? Carbon::parse($report->tgl_lapor)->format('d-m-Y') : '-',
                    'masa_pajak' => $report?->masa_pajak ?: '-',
                    'jml_lapor'  => (float) ($report?->jml_lapor ?: 0),
                ];
            }

            $wp->nm_kecamatan = $districts->get($wp->kd_kecamatan, '-');

            return $wp;
        });

        return $paginated;
    }
}
