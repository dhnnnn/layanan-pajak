<?php

namespace App\Http\Controllers\Admin;

use App\Actions\District\DistributeDistrictTargetByPctAction;
use App\Actions\District\GetDistrictAiRecommendationAction;
use App\Actions\District\StoreDistrictAdditionalTargetAction;
use App\Actions\Upt\GetAyatPctAction;
use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\DistrictAdditionalTarget;
use App\Models\SimpaduTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DistrictAdditionalTargetController extends Controller
{
    public function create(Request $request, District $district, GetAyatPctAction $getAyatPct): View
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
            $existing = DistrictAdditionalTarget::query()
                ->where('district_id', $district->id)
                ->where('no_ayat', $request->query('no_ayat'))
                ->where('year', $currentYear)
                ->first();
        }

        $selectedAyat = $request->query('no_ayat', $existing?->no_ayat);
        $pctData = $selectedAyat
            ? $getAyatPct($selectedAyat, $currentYear)
            : ['pcts' => [1 => 25.0, 2 => 25.0, 3 => 25.0, 4 => 25.0], 'base_target' => 0.0];

        $upt = $district->upts()->first();

        return view('admin.district-additional-targets.create', [
            'district' => $district,
            'upt' => $upt,
            'availableAyat' => $availableAyat,
            'currentYear' => $currentYear,
            'currentQuarter' => $currentQuarter,
            'existing' => $existing,
            'pctPerQ' => $pctData['pcts'],
            'baseTargetForAyat' => $pctData['base_target'],
        ]);
    }

    public function store(
        Request $request,
        District $district,
        StoreDistrictAdditionalTargetAction $storeAction,
    ): RedirectResponse {
        $data = $request->validate([
            'no_ayat' => ['required', 'string', 'max:20'],
            'start_quarter' => ['required', 'integer', 'min:1', 'max:4'],
            'additional_target' => ['required', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $storeAction(
            district: $district,
            noAyat: $data['no_ayat'],
            total: (float) $data['additional_target'],
            startQ: (int) $data['start_quarter'],
            notes: $data['notes'] ?? null,
            createdBy: auth()->id(),
        );

        $upt = $district->upts()->first();

        return redirect(
            $upt
                ? route('admin.realization-monitoring.show', [$upt, 'year' => now()->year])
                : route('admin.districts.index')
        )->with('success', "Target tambahan kecamatan {$district->name} berhasil disimpan.");
    }

    public function destroy(
        District $district,
        DistrictAdditionalTarget $districtAdditionalTarget,
    ): RedirectResponse {
        $year = $districtAdditionalTarget->year;
        $districtAdditionalTarget->delete();

        $upt = $district->upts()->first();

        return redirect(
            $upt
                ? route('admin.realization-monitoring.show', [$upt, 'year' => $year])
                : route('admin.districts.index')
        )->with('success', "Target tambahan kecamatan {$district->name} berhasil dihapus.");
    }

    public function preview(
        Request $request,
        District $district,
        DistributeDistrictTargetByPctAction $distribute,
    ): JsonResponse {
        $noAyat = $request->query('no_ayat');
        $additionalTarget = (float) $request->query('additional_target', 0);
        $currentYear = (int) now()->year;
        $currentQuarter = (int) ceil(now()->month / 3);

        if (! $noAyat || $additionalTarget <= 0) {
            return response()->json(['error' => 'Parameter tidak lengkap.'], 422);
        }

        $target = SimpaduTarget::query()
            ->where('no_ayat', $noAyat)
            ->where('year', $currentYear)
            ->first();

        if (! $target) {
            return response()->json(['error' => 'Data target tidak ditemukan.'], 404);
        }

        $totalTarget = (float) $target->total_target;
        $pcts = [
            1 => (float) $target->q1_pct,
            2 => (float) $target->q2_pct,
            3 => (float) $target->q3_pct,
            4 => (float) $target->q4_pct,
        ];
        $originalTargets = [
            1 => $totalTarget * ($pcts[1] / 100),
            2 => $totalTarget * (($pcts[2] - $pcts[1]) / 100),
            3 => $totalTarget * (($pcts[3] - $pcts[2]) / 100),
            4 => $totalTarget * (($pcts[4] - $pcts[3]) / 100),
        ];

        $additionalPerQ = $distribute($additionalTarget, $currentQuarter, $target);

        $quarters = [];
        for ($q = 1; $q <= 4; $q++) {
            $quarters[$q] = [
                'target_awal' => $originalTargets[$q],
                'tambahan' => $additionalPerQ[$q],
                'target_baru' => $originalTargets[$q] + $additionalPerQ[$q],
            ];
        }

        return response()->json([
            'no_ayat' => $noAyat,
            'keterangan' => $target->keterangan,
            'district_name' => $district->name,
            'year' => $currentYear,
            'start_quarter' => $currentQuarter,
            'total_target_awal' => $totalTarget,
            'total_tambahan' => $additionalTarget,
            'total_target_baru' => $totalTarget + $additionalTarget,
            'quarters' => $quarters,
        ]);
    }

    public function getPct(Request $request, District $district, GetAyatPctAction $getAyatPct): JsonResponse
    {
        $noAyat = $request->query('no_ayat');
        $currentYear = (int) now()->year;

        if (! $noAyat) {
            return response()->json(['pcts' => [1 => 25, 2 => 25, 3 => 25, 4 => 25], 'base_target' => 0]);
        }

        return response()->json($getAyatPct($noAyat, $currentYear));
    }

    public function aiRecommendation(
        Request $request,
        District $district,
        GetDistrictAiRecommendationAction $getRecommendation,
    ): JsonResponse {
        $noAyat = $request->query('no_ayat');

        if (! $noAyat) {
            return response()->json(['error' => 'Parameter no_ayat diperlukan.'], 422);
        }

        $result = $getRecommendation($district, $noAyat);

        if (isset($result['error'])) {
            return response()->json($result, 503);
        }

        return response()->json($result);
    }
}
