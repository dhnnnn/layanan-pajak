<?php

namespace App\Actions\FieldOfficer;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetTaxpayerArrearsDetailAction
{
    public function execute(string $npwpd, string $nop, int $year): Collection
    {
        $months = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->where('npwpd', $npwpd)
            ->where('nop', $nop)
            ->where('month', '>', 0)
            ->orderBy('month')
            ->get(['month', 'total_ketetapan', 'total_bayar', 'total_tunggakan']);

        $bulanIndo = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return $months->filter(fn ($r) => (float) $r->total_ketetapan > 0)
            ->map(fn ($r) => [
                'bulan' => $bulanIndo[(int) $r->month] ?? $r->month,
                'total_ketetapan' => (float) $r->total_ketetapan,
                'total_bayar' => (float) $r->total_bayar,
                'total_tunggakan' => (float) max($r->total_tunggakan, 0),
            ])
            ->values();
    }
}
