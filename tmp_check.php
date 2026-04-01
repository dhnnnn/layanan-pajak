use App\Models\TaxRealizationDailyEntry;
use App\Models\TaxType;
$types = ['Opsen Pajak Kendaraan Bermotor (PKB)', 'Opsen Bea Balik Nama Kendaraan Bermotor (BBNKB)', 'PAJAK BPHTB', 'PAJAK BUMI DAN BANGUNAN'];
foreach ($types as $name) {
    $t = TaxType::where('name', $name)->first();
    if ($t) {
        $count = TaxRealizationDailyEntry::where('tax_type_id', $t->id)->whereYear('entry_date', 2026)->count();
        $realization = TaxRealizationDailyEntry::where('tax_type_id', $t->id)->whereYear('entry_date', 2026)->sum('amount');
        echo "$name ($t->id): Count=$count, Sum=$realization\n";
    }
}
