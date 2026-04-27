<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tax\GenerateTaxDashboardAction;
use App\Http\Controllers\Controller;
use App\Models\SimpaduTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxTypeController extends Controller
{
    /**
     * Daftar semua jenis pajak.
     *
     * Mengembalikan daftar semua jenis pajak yang tersedia di dashboard,
     * termasuk grup PBJT dan anggotanya.
     */
    public function index(): JsonResponse
    {
        $latestYear = SimpaduTarget::query()->max('year') ?? (int) date('Y');

        $items = SimpaduTarget::query()
            ->where('year', $latestYear)
            ->orderBy('no_ayat')
            ->get(['no_ayat', 'keterangan'])
            ->map(fn ($t) => [
                'no_ayat' => $t->no_ayat,
                'nama' => $t->keterangan,
            ]);

        $pbjt = ['no_ayat' => '41100', 'nama' => 'Pajak (PBJT)', 'is_group' => true];
        $result = collect([$pbjt]);

        foreach ($items as $item) {
            $isPbjt = in_array((string) $item['no_ayat'], ['41101', '41102', '41103', '41105', '41107']);
            $result->push(array_merge($item, ['is_group' => false, 'in_pbjt_group' => $isPbjt]));
        }

        return response()->json($result->values());
    }

    /**
     * Realisasi pajak per jenis pajak.
     *
     * Mengembalikan detail target, realisasi, lebih/(kurang), dan persentase capaian
     * untuk setiap jenis pajak. Data konsisten dengan tabel di dashboard admin.
     *
     * @query int $year Tahun anggaran (default: tahun berjalan).
     */
    public function realization(Request $request, GenerateTaxDashboardAction $generateDashboard): JsonResponse
    {
        $year = $request->integer('year', (int) date('Y'));

        $result = $generateDashboard($year);

        $items = $result['data']->map(fn (array $item) => [
            'no_ayat' => $item['no_ayat'],
            'nama' => $item['tax_type_name'],
            'is_group' => $item['is_parent'] ?? false,
            'in_pbjt_group' => $item['is_child'] ?? false,
            'target' => (float) $item['target_total'],
            'realisasi' => (float) $item['total_realization'],
            'more_less' => (float) $item['more_less'],
            'percentage' => (float) $item['achievement_percentage'],
        ]);

        return response()->json([
            'year' => $year,
            'items' => $items->values(),
        ]);
    }
}
