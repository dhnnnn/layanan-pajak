use Illuminate\Support\Facades\DB;
$conn = DB::connection("simpadunew");
echo "BPHTB (41113) first 5 records columns:\n";
$res = $conn->table("pembayaran")->where("ayat", "41113")->whereYear("tgl_bayar", 2026)->take(5)->get();
print_r($res->toArray());
