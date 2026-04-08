<?php

namespace App\Actions\Simpadu;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetTaxPayerMatrixAction
{
    /**
     * Fetch Tax Payers matrix data from local database.
     */
    public function __invoke(int $year, int $startMonth, int $endMonth, ?string $search = null, ?array $districtCodes = null, string $statusFilter = '1', ?string $ayat = null, ?string $sortBy = null, string $sortDir = 'desc')
    {
        // 1. Define months in range
        $months = range($startMonth, $endMonth);

        // 2. Fetch Base WP/OP data from simpadu_tax_payers
        // Use month=0 (annual data) to get unique WP/OP without duplicating across months

        // Parse sort column: format is 'sptpd_M' or 'bayar_M' where M is month number
        $sortMonth = null;
        $sortField = null;
        if ($sortBy && preg_match('/^(sptpd|bayar)_(\d+)$/', $sortBy, $matches)) {
            $sortField = $matches[1]; // 'sptpd' or 'bayar'
            $sortMonth = (int) $matches[2];
        }

        $query = DB::table('simpadu_tax_payers as s')
            ->leftJoin('tax_types', 'tax_types.simpadu_code', '=', 's.ayat')
            ->select([
                's.npwpd',
                's.nop',
                's.nm_wp',
                's.nm_op',
                's.almt_op',
                's.kd_kecamatan',
                's.status',
                'tax_types.name as tax_type_name',
            ])
            ->where('s.year', $year)
            ->where('s.month', 0)
            ->when(! empty($search), function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('s.nm_wp', 'like', "%{$search}%")
                        ->orWhere('s.npwpd', 'like', "%{$search}%")
                        ->orWhere('s.nm_op', 'like', "%{$search}%");
                });
            })
            ->when($districtCodes, fn ($q) => $q->whereIn('s.kd_kecamatan', $districtCodes))
            ->when($statusFilter !== 'all', fn ($q) => $q->where('s.status', $statusFilter))
            ->when($ayat, fn ($q) => $q->where('s.ayat', $ayat))
            ->groupBy(['s.npwpd', 's.nop', 's.nm_wp', 's.nm_op', 's.almt_op', 's.kd_kecamatan', 's.status', 'tax_types.name']);

        // Apply sort via subquery join when sorting by monthly sptpd/bayar
        if ($sortField && $sortMonth) {
            $dbField = $sortField === 'sptpd' ? 'total_ketetapan' : 'total_bayar';
            $query->leftJoinSub(
                DB::table('simpadu_tax_payers')
                    ->select(['npwpd', 'nop', DB::raw("SUM({$dbField}) as sort_val")])
                    ->where('year', $year)
                    ->where('month', $sortMonth)
                    ->groupBy(['npwpd', 'nop']),
                'sort_data',
                fn ($join) => $join->on('sort_data.npwpd', '=', 's.npwpd')->on('sort_data.nop', '=', 's.nop')
            )->orderBy('sort_data.sort_val', $sortDir);
        } else {
            $query->orderBy('s.nm_wp');
        }

        $paginated = $query->paginate(20)->withQueryString();

        // 3. Extract unique pairs of (npwpd, nop) from current page to fetch reports
        $pairs = collect($paginated->items())->map(fn ($item) => [
            'npwpd' => $item->npwpd,
            'nop' => $item->nop,
        ]);

        if ($pairs->isEmpty()) {
            return $paginated;
        }

        // 4. Fetch Reports for these pairs in the selected range
        $reports = DB::table('simpadu_sptpd_reports')
            ->where('year', $year)
            ->whereBetween('month', [$startMonth, $endMonth])
            ->where(function ($q) use ($pairs) {
                foreach ($pairs as $pair) {
                    $q->orWhere(function ($sq) use ($pair) {
                        $sq->where('npwpd', $pair['npwpd'])->where('nop', $pair['nop']);
                    });
                }
            })
            ->get()
            ->groupBy(fn ($r) => "{$r->npwpd}-{$r->nop}");

        // 4b. Fetch monthly bayar from simpadu_tax_payers
        $payments = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->whereBetween('month', [$startMonth, $endMonth])
            ->where(function ($q) use ($pairs) {
                foreach ($pairs as $pair) {
                    $q->orWhere(function ($sq) use ($pair) {
                        $sq->where('npwpd', $pair['npwpd'])->where('nop', $pair['nop']);
                    });
                }
            })
            ->get(['npwpd', 'nop', 'month', 'total_bayar'])
            ->groupBy(fn ($r) => "{$r->npwpd}-{$r->nop}");

        // 5. Fetch district names from local districts table
        $districts = DB::table('districts')->pluck('name', 'simpadu_code');

        // 6. Map reports into each paginated item
        $paginated->getCollection()->transform(function ($wp) use ($reports, $payments, $months, $districts) {
            $key = "{$wp->npwpd}-{$wp->nop}";
            $wpReports = $reports->get($key, collect());
            $wpPayments = $payments->get($key, collect());

            $wp->monthly_data = [];
            foreach ($months as $m) {
                $report = $wpReports->firstWhere('month', $m);
                $payment = $wpPayments->firstWhere('month', $m);
                $wp->monthly_data[$m] = [
                    'tgl_lapor' => $report?->tgl_lapor ? Carbon::parse($report->tgl_lapor)->format('d-m-Y') : '-',
                    'masa_pajak' => $report?->masa_pajak ?: '-',
                    'jml_lapor' => (float) ($report?->jml_lapor ?: 0),
                    'total_bayar' => (float) ($payment?->total_bayar ?: 0),
                ];
            }

            $wp->nm_kecamatan = $districts->get($wp->kd_kecamatan, '-');

            return $wp;
        });

        return $paginated;
    }
}
