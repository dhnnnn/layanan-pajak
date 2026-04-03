<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$npwpd = '13514004435';
$year = 2026;

echo "=== simpadu_tax_payers untuk NPWPD $npwpd tahun $year ===\n";
$rows = DB::table('simpadu_tax_payers')
    ->where('npwpd', $npwpd)->where('year', $year)
    ->orderBy('month')
    ->get(['month','nop','total_ketetapan','total_bayar','total_tunggakan']);

foreach ($rows as $r) {
    echo "  month:{$r->month} | nop:{$r->nop} | ketetapan:{$r->total_ketetapan} | bayar:{$r->total_bayar} | tunggakan:{$r->total_tunggakan}\n";
}

echo "\n=== SUM per month group ===\n";
$sums = DB::table('simpadu_tax_payers')
    ->where('npwpd', $npwpd)->where('year', $year)
    ->selectRaw('month, SUM(total_ketetapan) as k, SUM(total_bayar) as b, SUM(total_tunggakan) as t')
    ->groupBy('month')->orderBy('month')->get();
foreach ($sums as $r) {
    echo "  month:{$r->month} | ketetapan:{$r->k} | bayar:{$r->b} | tunggakan:{$r->t}\n";
}
?>
