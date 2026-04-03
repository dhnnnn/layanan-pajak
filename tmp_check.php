<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// RIZANATUL FUAD districts: PASREPAN=060, PUSPO=030, TOSARI=040
$codes = ['060', '030', '040'];
$year = 2026;

echo "=== PAGINATE test ===\n";
$result = DB::table('simpadu_tax_payers as stp')
    ->leftJoin('tax_types', 'tax_types.simpadu_code', '=', 'stp.ayat')
    ->where('stp.year', $year)
    ->where('stp.month', 0)
    ->where('stp.status', '1')
    ->whereIn('stp.kd_kecamatan', $codes)
    ->groupBy('stp.npwpd', 'stp.nop', 'stp.nm_wp', 'stp.nm_op', 'stp.almt_op',
              'stp.kd_kecamatan', 'stp.ayat', 'stp.status', 'tax_types.name')
    ->selectRaw('stp.npwpd, stp.nop, stp.nm_wp, stp.nm_op, stp.almt_op,
        stp.kd_kecamatan, stp.ayat, stp.status,
        tax_types.name as tax_type_name,
        SUM(stp.total_ketetapan) as total_ketetapan,
        (CASE WHEN SUM(stp.total_bayar) > SUM(stp.total_ketetapan) THEN SUM(stp.total_ketetapan) ELSE SUM(stp.total_bayar) END) as total_bayar,
        (CASE WHEN SUM(stp.total_ketetapan) > SUM(stp.total_bayar) THEN SUM(stp.total_ketetapan) - SUM(stp.total_bayar) ELSE 0 END) as total_tunggakan')
    ->orderByDesc('total_tunggakan')
    ->paginate(15);

echo "Total: " . $result->total() . "\n";
echo "Items count: " . count($result->items()) . "\n";
foreach ($result->items() as $r) {
    echo "  {$r->nm_wp} | {$r->total_ketetapan} | {$r->total_tunggakan}\n";
}
?>
