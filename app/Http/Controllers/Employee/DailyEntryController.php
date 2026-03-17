<?php

namespace App\Http\Controllers\Employee;

use App\Actions\Tax\DeleteDailyEntryAction;
use App\Actions\Tax\StoreDailyEntryAction;
use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\TaxRealizationDailyEntry;
use App\Models\TaxType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyEntryController extends Controller
{
    /**
     * Show the daily entry page for a specific district.
     */
    public function show(Request $request, string $districtId): View
    {
        $user = $request->user();
        $year = $request->integer('year', (int) date('Y'));

        $district = District::query()->findOrFail($districtId);

        abort_unless(
            $user->districts()->where('districts.id', $districtId)->exists(),
            403,
            'Anda tidak memiliki akses ke kecamatan ini.',
        );

        $taxTypes = TaxType::query()->orderBy('code')->get();

        $yearlyTotals = TaxRealizationDailyEntry::query()
            ->where('district_id', $districtId)
            ->whereYear('entry_date', $year)
            ->selectRaw('tax_type_id, SUM(amount) as total')
            ->groupBy('tax_type_id')
            ->pluck('total', 'tax_type_id');

        $currentMonth = (int) date('n');

        $monthlyEntries = TaxRealizationDailyEntry::query()
            ->where('district_id', $districtId)
            ->whereYear('entry_date', $year)
            ->whereMonth('entry_date', $currentMonth)
            ->orderBy('entry_date')
            ->orderBy('tax_type_id')
            ->get();

        return view('employee.realizations.district-entries', compact(
            'district',
            'taxTypes',
            'yearlyTotals',
            'monthlyEntries',
            'year',
            'currentMonth',
        ));
    }

    /**
     * List daily entries for a given tax_type + district + year + month (JSON).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'tax_type_id' => ['required', 'string', 'exists:tax_types,id'],
            'district_id' => ['required', 'string', 'exists:districts,id'],
            'year' => ['required', 'integer'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $user = $request->user();

        if (! $user->districts()->where('districts.id', $request->input('district_id'))->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $entries = TaxRealizationDailyEntry::query()
            ->where('tax_type_id', $request->input('tax_type_id'))
            ->where('district_id', $request->input('district_id'))
            ->whereYear('entry_date', $request->integer('year'))
            ->whereMonth('entry_date', $request->integer('month'))
            ->orderBy('entry_date')
            ->get(['id', 'entry_date', 'amount', 'note']);

        return response()->json(['entries' => $entries]);
    }

    /**
     * Store multiple daily entries at once (batch).
     */
    public function storeBatch(Request $request, StoreDailyEntryAction $storeDailyEntry): JsonResponse
    {
        $validated = $request->validate([
            'district_id' => ['required', 'string', 'exists:districts,id'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.tax_type_id' => ['required', 'string', 'exists:tax_types,id'],
            'entries.*.entry_date' => ['required', 'date'],
            'entries.*.amount' => ['required', 'numeric', 'min:0.01'],
            'entries.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        if (! $user->districts()->where('districts.id', $validated['district_id'])->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        foreach ($validated['entries'] as $item) {
            $storeDailyEntry([
                'tax_type_id' => $item['tax_type_id'],
                'district_id' => $validated['district_id'],
                'entry_date' => $item['entry_date'],
                'amount' => $item['amount'],
                'note' => $item['note'] ?? null,
            ], $user);
        }

        return response()->json(['message' => count($validated['entries']).' data berhasil disimpan.'], 201);
    }

    /**
     * Store a new daily entry.
     */
    public function store(Request $request, StoreDailyEntryAction $storeDailyEntry): JsonResponse
    {
        $validated = $request->validate([
            'tax_type_id' => ['required', 'string', 'exists:tax_types,id'],
            'district_id' => ['required', 'string', 'exists:districts,id'],
            'entry_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        if (! $user->districts()->where('districts.id', $validated['district_id'])->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $entry = $storeDailyEntry($validated, $user);

        return response()->json(['entry' => $entry, 'message' => 'Data berhasil disimpan.'], 201);
    }

    /**
     * Delete a daily entry and re-sync the monthly total.
     */
    public function destroy(Request $request, TaxRealizationDailyEntry $dailyEntry, DeleteDailyEntryAction $deleteDailyEntry): JsonResponse
    {
        $user = $request->user();

        if ($dailyEntry->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $deleteDailyEntry($dailyEntry);

        return response()->json(['message' => 'Data berhasil dihapus.']);
    }
}
