<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PaymentCheck\CheckPaymentAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaymentCheckItem',
    properties: [
        new OA\Property(property: 'nop', type: 'string'),
        new OA\Property(property: 'nama_op', type: 'string'),
        new OA\Property(property: 'kohir', type: 'string'),
        new OA\Property(property: 'masa_awal', type: 'string'),
        new OA\Property(property: 'jatuh_tempo', type: 'string', nullable: true),
        new OA\Property(property: 'tgl_bayar', type: 'string', nullable: true),
        new OA\Property(property: 'jml_sptpd', type: 'integer'),
        new OA\Property(property: 'bayar_pokok', type: 'integer'),
        new OA\Property(property: 'bayar_denda', type: 'integer'),
        new OA\Property(property: 'sisa_pokok', type: 'integer'),
        new OA\Property(property: 'sisa_denda', type: 'integer'),
        new OA\Property(property: 'sisa', type: 'integer'),
        new OA\Property(property: 'keterangan', type: 'string', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'PaymentCheckResult',
    properties: [
        new OA\Property(property: 'npwpd', type: 'string', example: '1234567890123'),
        new OA\Property(property: 'nama_op', type: 'string', example: 'HOTEL SEJAHTERA'),
        new OA\Property(property: 'jenis_pajak', type: 'string', example: 'hotel'),
        new OA\Property(property: 'tahun', type: 'string', example: '2025'),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PaymentCheckItem'),
        ),
        new OA\Property(
            property: 'summary',
            type: 'object',
            properties: [
                new OA\Property(property: 'total_sptpd', type: 'integer'),
                new OA\Property(property: 'total_denda', type: 'integer'),
                new OA\Property(property: 'total_bayar_pokok', type: 'integer'),
                new OA\Property(property: 'total_bayar_denda', type: 'integer'),
                new OA\Property(property: 'total_sisa', type: 'integer'),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'PaymentCheckBody',
    required: ['npwpd', 'tahun'],
    properties: [
        new OA\Property(property: 'npwpd', type: 'string', description: 'Nomor Pokok Wajib Pajak Daerah', example: '1234567890123'),
        new OA\Property(property: 'tahun', type: 'integer', description: 'Tahun pajak', example: 2025),
        new OA\Property(property: 'npwpd_lama', type: 'boolean', description: 'true jika menggunakan NPWPD lama', example: false),
    ],
)]
class PaymentCheckController extends Controller
{
    #[OA\Post(
        path: '/v1/payment-check/hotel',
        tags: ['Cek Pembayaran'],
        summary: 'Cek pembayaran PBJT Hotel',
        security: [['BearerToken' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckBody')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckResult')),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
        ],
    )]
    #[OA\Post(
        path: '/v1/payment-check/restoran',
        tags: ['Cek Pembayaran'],
        summary: 'Cek pembayaran PBJT Restoran',
        security: [['BearerToken' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckBody')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckResult')),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
        ],
    )]
    #[OA\Post(
        path: '/v1/payment-check/hiburan',
        tags: ['Cek Pembayaran'],
        summary: 'Cek pembayaran PBJT Hiburan',
        security: [['BearerToken' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckBody')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckResult')),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
        ],
    )]
    #[OA\Post(
        path: '/v1/payment-check/reklame',
        tags: ['Cek Pembayaran'],
        summary: 'Cek pembayaran Pajak Reklame',
        security: [['BearerToken' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckBody')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckResult')),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
        ],
    )]
    #[OA\Post(
        path: '/v1/payment-check/ppj',
        tags: ['Cek Pembayaran'],
        summary: 'Cek pembayaran Pajak Penerangan Jalan (PPJ)',
        security: [['BearerToken' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckBody')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckResult')),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
        ],
    )]
    #[OA\Post(
        path: '/v1/payment-check/parkir',
        tags: ['Cek Pembayaran'],
        summary: 'Cek pembayaran PBJT Parkir',
        security: [['BearerToken' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckBody')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckResult')),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
        ],
    )]
    #[OA\Post(
        path: '/v1/payment-check/at',
        tags: ['Cek Pembayaran'],
        summary: 'Cek pembayaran Pajak Air Tanah (AT)',
        security: [['BearerToken' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckBody')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckResult')),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
        ],
    )]
    #[OA\Post(
        path: '/v1/payment-check/minerba',
        tags: ['Cek Pembayaran'],
        summary: 'Cek pembayaran Pajak Mineral Bukan Logam (MINERBA)',
        security: [['BearerToken' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckBody')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckResult')),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
        ],
    )]
    #[OA\Post(
        path: '/v1/payment-check/bphtb',
        tags: ['Cek Pembayaran'],
        summary: 'Cek pembayaran BPHTB',
        description: 'Untuk BPHTB, field `npwpd` diisi dengan Nomor Register (kohir).',
        security: [['BearerToken' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckBody')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PaymentCheckResult')),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
        ],
    )]
    public function __invoke(Request $request, string $jenis_pajak, CheckPaymentAction $checkPayment): JsonResponse
    {
        $validated = $request->validate([
            'npwpd' => ['required', 'string'],
            'tahun' => ['required', 'digits:4'],
            'npwpd_lama' => ['nullable', 'boolean'],
        ]);

        $result = $checkPayment(
            npwpd: $validated['npwpd'],
            tahun: $validated['tahun'],
            jenisPajak: $jenis_pajak,
            npwpdLama: (bool) ($validated['npwpd_lama'] ?? false),
        );

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        if (empty($result['data'])) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        return response()->json($result);
    }
}
