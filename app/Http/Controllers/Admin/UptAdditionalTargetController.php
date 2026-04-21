<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Upt\GetAyatPctAction;
use App\Actions\Upt\GetUptAiRecommendationAction;
use App\Actions\Upt\PreviewUptAdditionalTargetAction;
use App\Actions\Upt\StoreUptAdditionalTargetAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUptAdditionalTargetRequest;
use App\Models\SimpaduTarget;
use App\Models\UptAdditionalTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class UptAdditionalTargetController extends Controller
{
    public function index(Request $request): View
    {
        $currentYear = (int) now()->year;

        $availableYears = UptAdditionalTarget::query()
            ->distinct()->orderByDesc('year')->pluck('year')
            ->merge([SimpaduTarget::query()->distinct()->orderByDesc('year')->pluck('year')->first()])
            ->unique()->sortDesc()->values();

        $selectedYear = (int) $request->query('year', $currentYear);

        $additionalTargets = UptAdditionalTarget::query()
            ->with('creator')
            ->where('year', $selectedYear)
            ->orderBy('no_ayat')
            ->get();

        $ayatLabels = SimpaduTarget::query()
            ->where('year', $selectedYear)
            ->pluck('keterangan', 'no_ayat');

        $baseTargetMap = SimpaduTarget::query()
            ->where('year', $selectedYear)
            ->pluck('total_target', 'no_ayat')
            ->map(fn ($v) => (float) $v);

        return view('admin.upt-additional-targets.index', compact(
            'additionalTargets',
            'ayatLabels',
            'baseTargetMap',
            'selectedYear',
            'availableYears',
            'currentYear',
        ));
    }

    public function create(Request $request, GetAyatPctAction $getAyatPct): View
    {
        $currentYear = (int) now()->year;
        $currentQuarter = (int) ceil(now()->month / 3);

        $availableAyat = SimpaduTarget::query()
            ->select('no_ayat', 'keterangan', 'year')
            ->orderByDesc('year')
            ->get()
            ->unique('no_ayat')
            ->sortBy('no_ayat')
            ->mapWithKeys(fn ($t) => [$t->no_ayat => $t->keterangan]);

        $existing = null;
        if ($request->filled('no_ayat')) {
            $existing = UptAdditionalTarget::query()
                ->where('no_ayat', $request->query('no_ayat'))
                ->where('year', $currentYear)
                ->first();
        }

        $selectedAyat = $request->query('no_ayat', $existing?->no_ayat);
        $pctData = $selectedAyat ? $getAyatPct($selectedAyat, $currentYear) : ['pcts' => [1 => 25.0, 2 => 25.0, 3 => 25.0, 4 => 25.0], 'base_target' => 0.0];

        return view('admin.upt-additional-targets.create', [
            'availableAyat' => $availableAyat,
            'currentYear' => $currentYear,
            'currentQuarter' => $currentQuarter,
            'existing' => $existing,
            'pctPerQ' => $pctData['pcts'],
            'baseTargetForAyat' => $pctData['base_target'],
        ]);
    }

    public function store(
        StoreUptAdditionalTargetRequest $request,
        StoreUptAdditionalTargetAction $storeAction,
    ): RedirectResponse {
        $data = $request->validated();
        $currentYear = (int) now()->year;

        $storeAction(
            noAyat: $data['no_ayat'],
            total: (float) $data['additional_target'],
            startQ: (int) $data['start_quarter'],
            year: $currentYear,
            notes: $data['notes'] ?? null,
            createdBy: auth()->id(),
        );

        return redirect()
            ->route('admin.tax-targets.report', ['year' => $currentYear])
            ->with('success', 'Target tambahan APBD berhasil disimpan.');
    }

    public function preview(
        Request $request,
        PreviewUptAdditionalTargetAction $previewAction,
    ): JsonResponse {
        $noAyat = $request->query('no_ayat');
        $additionalTarget = (float) $request->query('additional_target', 0);
        $currentYear = (int) now()->year;
        $currentQuarter = (int) ceil(now()->month / 3);

        if (! $noAyat || $additionalTarget <= 0) {
            return response()->json(['error' => 'Parameter tidak lengkap.'], 422);
        }

        $result = $previewAction($noAyat, $additionalTarget, $currentYear, $currentQuarter);

        if (isset($result['error'])) {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }

    public function aiRecommendation(
        Request $request,
        GetUptAiRecommendationAction $getRecommendation,
    ): JsonResponse {
        $noAyat = $request->query('no_ayat');

        if (! $noAyat) {
            return response()->json(['error' => 'Parameter no_ayat diperlukan.'], 422);
        }

        $result = $getRecommendation($noAyat);

        if (isset($result['error'])) {
            return response()->json($result, 503);
        }

        return response()->json($result);
    }

    public function getPct(Request $request, GetAyatPctAction $getAyatPct): JsonResponse
    {
        $noAyat = $request->query('no_ayat');
        $currentYear = (int) now()->year;

        if (! $noAyat) {
            return response()->json(['pcts' => [1 => 25, 2 => 25, 3 => 25, 4 => 25], 'base_target' => 0]);
        }

        return response()->json($getAyatPct($noAyat, $currentYear));
    }

    public function destroy(UptAdditionalTarget $uptAdditionalTarget): RedirectResponse
    {
        $year = $uptAdditionalTarget->year;
        $uptAdditionalTarget->delete();

        Cache::forget("dashboard:tax:{$year}");

        return redirect()
            ->route('admin.tax-targets.report', ['year' => $year])
            ->with('success', 'Target tambahan APBD berhasil dihapus.');
    }
}
