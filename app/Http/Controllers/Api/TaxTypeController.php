<?php

namespace App\Http\Controllers\Api;

use App\Actions\Tax\GenerateTaxDashboardAction;
use App\Http\Controllers\Controller;
use App\Models\SimpaduTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TaxTypeController extends Controller
{
    #[OA\Get(
        path: '/tax-types',
        tags: ['Jenis Pajak'],
        summary: 'Daftar semua jenis pajak',
        description: 'Mengembalikan daftar semua jenis pajak yang tersedia di dashboard, termasuk grup PBJT dan anggotanya.',
        security: [['BearerToken' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'no_ayat', type: 'string', example: '41101', description: 'Kode ayat pajak'),
                            new OA\Property(property: 'nama', type: 'string', example: 'PBJT-HOTEL', description: 'Nama jenis pajak'),
                            new OA\Property(property: 'is_group', type: 'boolean', example: false, description: 'true jika ini grup induk (Pajak PBJT)'),
                            new OA\Property(property: 'in_pbjt_group', type: 'boolean', example: true, description: 'true jika termasuk dalam grup PBJT'),
                        ],
                    ),
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthorized — token tidak valid'),
        ],
    )]
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

    #[OA\Get(
        path: '/tax-realization',
        tags: ['Realisasi per Jenis Pajak'],
        summary: 'Realisasi pajak per jenis pajak',
        description: 'Mengembalikan detail target, realisasi, lebih/(kurang), dan persentase capaian untuk setiap jenis pajak. Data konsisten dengan tabel di dashboard admin.',
        security: [['BearerToken' => []]],
        parameters: [
            new OA\Parameter(
                name: 'year',
                in: 'query',
                required: false,
                description: 'Tahun anggaran (default: tahun berjalan)',
                schema: new OA\Schema(type: 'integer', example: 2026),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'year', type: 'integer', example: 2026),
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'no_ayat', type: 'string', example: '41100'),
                                    new OA\Property(property: 'nama', type: 'string', example: 'Pajak (PBJT)'),
                                    new OA\Property(property: 'is_group', type: 'boolean', example: true, description: 'true jika baris grup induk'),
                                    new OA\Property(property: 'in_pbjt_group', type: 'boolean', example: false, description: 'true jika anggota grup PBJT'),
                                    new OA\Property(property: 'target', type: 'number', example: 239976870832),
                                    new OA\Property(property: 'realisasi', type: 'number', example: 81420725151),
                                    new OA\Property(property: 'more_less', type: 'number', example: -158556145681),
                                    new OA\Property(property: 'percentage', type: 'number', example: 33.93),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthorized — token tidak valid'),
        ],
    )]
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
