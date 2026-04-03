<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$npwpd = '23514000726';
echo "=== nm_wp vs nm_op di simpadu_tax_payers ===\n";
$rows = DB::table('simpadu_tax_payers')
    ->where('npwpd', $npwpd)->where('year', 2026)->where('month', 0)
    ->get(['nop','nm_wp','nm_op','ayat']);
foreach ($rows as $r) {
    $same = strtoupper(trim($r->nm_wp)) === strtoupper(trim($r->nm_op)) ? 'SAMA' : 'BEDA';
    echo "  NOP:{$r->nop} | nm_wp:[{$r->nm_wp}] | nm_op:[{$r->nm_op}] → $same\n";
}
?>
