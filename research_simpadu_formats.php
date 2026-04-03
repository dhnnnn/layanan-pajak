<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = ['dat_sptpd_at', 'dat_sptpd_reklame', 'dat_sptpd_minerba', 'dat_sptpd_ppj', 'dat_sptpd_self', 'pembayaran'];
$fields = [
    'dat_sptpd_at' => 'jmlsptpd',
    'dat_sptpd_reklame' => 'total',
    'dat_sptpd_minerba' => 'pajak',
    'dat_sptpd_ppj' => 'pajak',
    'dat_sptpd_self' => 'pajak',
    'pembayaran' => 'jml_byr_pokok'
];

foreach ($tables as $table) {
    echo "--- $table ---\n";
    try {
        $field = $fields[$table];
        $sample = DB::connection('simpadunew')->table($table)->select($field)->whereNotNull($field)->orderByDesc($field)->limit(5)->get();
        foreach ($sample as $s) {
            echo "$field: " . json_encode($s->$field) . "\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
