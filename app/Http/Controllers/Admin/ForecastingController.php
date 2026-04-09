<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tax\GetTaxForecastAction;
use App\Http\Controllers\Controller;
use App\Models\SimpaduTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForecastingController extends Controller
{
    public function index(Request $request): View
    {
        // Ambil semua ayat yang tersedia dari target
        $availableAyat = SimpaduTarget::query()
            ->select('no_ayat', 'keterangan', 'year')
            ->orderByDesc('year')
            ->get()
            ->unique('no_ayat')
            ->sortBy('no_ayat')
            ->mapWithKeys(fn ($t) => [$t->no_ayat => $t->keterangan]);

        $selectedAyat = $request->query('ayat', 'all');

        return view('admin.forecasting.index', [
            'availableAyat' => $availableAyat,
            'selectedAyat' => $selectedAyat,
        ]);
    }

    public function data(Request $request, GetTaxForecastAction $getForecast): JsonResponse
    {
        $ayat = $request->query('ayat');

        if (! $ayat) {
            return response()->json(['error' => 'Parameter ayat diperlukan.'], 422);
        }

        $label = $ayat === 'all'
            ? 'Semua Jenis Pajak'
            : SimpaduTarget::query()
                ->where('no_ayat', $ayat)
                ->orderByDesc('year')
                ->value('keterangan') ?? $ayat;

        $result = $getForecast($ayat, $label);

        if ($result === null) {
            return response()->json([
                'error' => 'Data tidak tersedia atau forecasting service tidak dapat dijangkau.',
            ], 503);
        }

        return response()->json($result);
    }
}
