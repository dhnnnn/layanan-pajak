<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PaymentCheck\CheckPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PaymentCheckRequest;
use Illuminate\Http\JsonResponse;

class PaymentCheckController extends Controller
{
    public function __construct(
        private CheckPaymentAction $checkPayment,
    ) {}

    /**
     * Cek pembayaran PBJT Hotel.
     *
     * @tags Cek Pembayaran
     */
    public function hotel(PaymentCheckRequest $request): JsonResponse
    {
        return $this->check($request, 'hotel');
    }

    /**
     * Cek pembayaran PBJT Restoran.
     *
     * @tags Cek Pembayaran
     */
    public function restoran(PaymentCheckRequest $request): JsonResponse
    {
        return $this->check($request, 'restoran');
    }

    /**
     * Cek pembayaran PBJT Hiburan.
     *
     * @tags Cek Pembayaran
     */
    public function hiburan(PaymentCheckRequest $request): JsonResponse
    {
        return $this->check($request, 'hiburan');
    }

    /**
     * Cek pembayaran Pajak Reklame.
     *
     * @tags Cek Pembayaran
     */
    public function reklame(PaymentCheckRequest $request): JsonResponse
    {
        return $this->check($request, 'reklame');
    }

    /**
     * Cek pembayaran Pajak Penerangan Jalan (PPJ).
     *
     * @tags Cek Pembayaran
     */
    public function ppj(PaymentCheckRequest $request): JsonResponse
    {
        return $this->check($request, 'ppj');
    }

    /**
     * Cek pembayaran PBJT Parkir.
     *
     * @tags Cek Pembayaran
     */
    public function parkir(PaymentCheckRequest $request): JsonResponse
    {
        return $this->check($request, 'parkir');
    }

    /**
     * Cek pembayaran Pajak Air Tanah (AT).
     *
     * @tags Cek Pembayaran
     */
    public function at(PaymentCheckRequest $request): JsonResponse
    {
        return $this->check($request, 'at');
    }

    /**
     * Cek pembayaran Pajak Mineral Bukan Logam (MINERBA).
     *
     * @tags Cek Pembayaran
     */
    public function minerba(PaymentCheckRequest $request): JsonResponse
    {
        return $this->check($request, 'minerba');
    }

    /**
     * Cek pembayaran BPHTB.
     *
     * Untuk BPHTB, field `npwpd` diisi dengan Nomor Register (kohir).
     *
     * @tags Cek Pembayaran
     */
    public function bphtb(PaymentCheckRequest $request): JsonResponse
    {
        return $this->check($request, 'bphtb');
    }

    private function check(PaymentCheckRequest $request, string $jenisPajak): JsonResponse
    {
        $validated = $request->validated();

        $result = ($this->checkPayment)(
            npwpd: $validated['npwpd'],
            tahun: $validated['tahun'],
            jenisPajak: $jenisPajak,
            npwpdLama: (bool) ($validated['npwpd_lama'] ?? false),
            namaWp: $validated['nama_wp'] ?? null,
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
