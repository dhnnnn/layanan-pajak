<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tax\GenerateTaxDashboardAction;
use App\Http\Controllers\Controller;
use App\Models\SimpaduMonthlyRealization;
use App\Models\SimpaduTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'MANTRA API',
    version: '1.0.0',
    description: 'REST API MANTRA (Monitoring Analitik Notifikasi Terpadu Retribusi dan Pajak) Kabupaten Pasuruan. Semua endpoint memerlukan Bearer Token pada header Authorization.',
)]
#[OA\Server(url: 'https://upp.pendapatan.pasuruankab.go.id/api', description: 'Production Server')]
#[OA\Server(url: 'http://localhost:8000/api', description: 'Local Development')]
#[OA\SecurityScheme(
    securityScheme: 'BearerToken',
    type: 'http',
    scheme: 'bearer',
    description: 'Masukkan token API.',
)]
#[OA\Tag(name: 'Ringkasan Pajak', description: 'Total target dan realisasi pajak per tahun')]
#[OA\Tag(name: 'Jenis Pajak', description: 'Daftar jenis pajak yang tersedia')]
#[OA\Tag(name: 'Realisasi per Jenis Pajak', description: 'Detail realisasi pajak dipecah per jenis pajak')]
#[OA\Tag(name: 'Cek Pembayaran', description: 'Cek status pembayaran pajak daerah per NPWPD')]
class TaxSummaryController extends Controller
{
    #[OA\Get(
        path: '/v1/tax-summary',
        tags: ['Ringkasan Pajak'],
        summary: 'Total target dan realisasi pajak',
        description: 'Mengembalikan total target, realisasi, lebih/(kurang), dan persentase capaian. Jika parameter year tidak diisi, mengembalikan semua tahun yang tersedia.',
        security: [['BearerToken' => []]],
        parameters: [
            new OA\Parameter(
                name: 'year',
                in: 'query',
                required: false,
                description: 'Tahun anggaran (contoh: 2026). Kosongkan untuk semua tahun.',
                schema: new OA\Schema(type: 'integer', example: 2026),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil — tahun spesifik',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'year', type: 'integer', example: 2026),
                        new OA\Property(property: 'total_target', type: 'number', example: 653283255374),
                        new OA\Property(property: 'total_realization', type: 'number', example: 198105196856),
                        new OA\Property(property: 'more_less', type: 'number', example: -455178058518),
                        new OA\Property(property: 'percentage', type: 'number', example: 30.32),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthorized — token tidak valid'),
        ],
    )]
    public function __invoke(Request $request, GenerateTaxDashboardAction $generateDashboard): JsonResponse
    {
        $yearParam = $request->query('year');

        $availableYears = SimpaduTarget::query()->distinct()->pluck('year')
            ->merge(SimpaduMonthlyRealization::query()->distinct()->pluck('year'))
            ->unique()
            ->sortDesc()
            ->values();

        if ($yearParam && is_numeric($yearParam)) {
            $year = (int) $yearParam;
            $result = $generateDashboard($year);
            $totals = $result['totals'];

            return response()->json([
                'year' => $year,
                'total_target' => (float) $totals['target'],
                'total_realization' => (float) $totals['realization'],
                'more_less' => (float) $totals['more_less'],
                'percentage' => (float) $totals['percentage'],
            ]);
        }

        $breakdown = $availableYears->map(function (int $year) use ($generateDashboard): array {
            $totals = $generateDashboard($year)['totals'];

            return [
                'year' => $year,
                'total_target' => (float) $totals['target'],
                'total_realization' => (float) $totals['realization'],
                'more_less' => (float) $totals['more_less'],
                'percentage' => (float) $totals['percentage'],
            ];
        });

        return response()->json($breakdown->values());
    }
}
