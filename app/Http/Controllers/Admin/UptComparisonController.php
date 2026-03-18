<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Upt\ImportUptComparisonAction;
use App\Actions\Upt\PreviewUptComparisonAction;
use App\Exports\TaxRealizationTemplateExport;
use App\Exports\UptComparisonReportExport;
use App\Exports\UptComparisonTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportUptComparisonRequest;
use App\Models\TaxRealizationDailyEntry;
use App\Models\TaxTarget;
use App\Models\TaxType;
use App\Models\Upt;
use App\Models\UptComparison;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UptComparisonController extends Controller
{
    public function index(): View
    {
        return view('admin.upt-comparisons.index');
    }

    public function downloadTemplate(Request $request): BinaryFileResponse
    {
        $type = $request->query('type', 'apbd'); // 'apbd' or 'upt'
        $year = $request->integer('year', (int) date('Y'));

        if ($type === 'upt') {
            $filename = 'template-perbandingan-target-upt-'.$year.'.xlsx';

            return Excel::download(
                new UptComparisonTemplateExport($year),
                $filename
            );
        }

        // Default: APBD template
        $filename = 'template-target-apbd-'.$year.'.xlsx';

        return Excel::download(
            new TaxRealizationTemplateExport($year, null),
            $filename
        );
    }

    public function preview(ImportUptComparisonRequest $request): View|RedirectResponse
    {
        $file = $request->file('file');
        $year = $request->integer('year');

        if ($file === null) {
            return redirect()
                ->back()
                ->with('error', 'File tidak ditemukan.');
        }

        $previewAction = app(PreviewUptComparisonAction::class);

        $result = $previewAction($file, $year);

        $upts = Upt::query()->orderBy('code')->get();

        return view('admin.upt-comparisons.preview', [
            'storedPath' => $result['stored_path'],
            'totalRows' => $result['total_rows'],
            'previewData' => $result['preview_data'],
            'fileName' => $file->getClientOriginalName(),
            'year' => $year,
            'upts' => $upts,
        ]);
    }

    public function import(ImportUptComparisonRequest $request): RedirectResponse
    {
        $storedPath = $request->string('stored_path')->toString();

        $importAction = app(ImportUptComparisonAction::class);

        $importLog = $importAction(
            storedPath: $storedPath,
            originalFileName: $request->string('file_name')->toString(),
            user: auth()->user(),
            year: $request->integer('year'),
        );

        // Clean up stored file
        Storage::disk('local')->delete($storedPath);

        return redirect()
            ->route('admin.upt-comparisons.index')
            ->with('success', "Import selesai. Berhasil: {$importLog->success_rows}, Gagal: {$importLog->failed_rows}");
    }

    public function report(Request $request): View
    {
        $year = $request->integer('year', (int) date('Y'));
        $search = $request->string('search')->trim();

        $upts = Upt::query()->orderBy('code')->get();

        $taxTypes = TaxType::query()
            ->when($search, fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        // Pre-load targets for the current year
        $targets = TaxTarget::query()
            ->where('year', $year)
            ->pluck('target_amount', 'tax_type_id');

        // Pre-load UPT targets: upt_id -> tax_type_id -> target_amount
        $uptTargets = UptComparison::query()
            ->where('year', $year)
            ->get()
            ->groupBy('upt_id')
            ->map(fn ($rows) => $rows->pluck('target_amount', 'tax_type_id')->map(fn ($v) => (float) $v)->toArray())
            ->toArray();

        // Load realization totals from daily entries, grouped by UPT + tax type
        // upt_id -> tax_type_id -> total
        $uptRealizationTotals = [];
        foreach ($upts as $upt) {
            $userIds = $upt->users()->role('pegawai')->pluck('users.id');

            $totals = TaxRealizationDailyEntry::query()
                ->whereIn('user_id', $userIds)
                ->whereYear('entry_date', $year)
                ->selectRaw('tax_type_id, SUM(amount) as total')
                ->groupBy('tax_type_id')
                ->pluck('total', 'tax_type_id');

            $uptRealizationTotals[$upt->id] = $totals->map(fn ($v) => (float) $v)->toArray();
        }

        // Grand totals across all tax types (not paginated)
        $grandTotalTarget = 0.0;
        $grandTotalUpt = [];
        $grandTotalUptTarget = [];
        $grandTotalAllUpt = 0.0;

        foreach ($upts as $upt) {
            $grandTotalUpt[$upt->id] = array_sum($uptRealizationTotals[$upt->id] ?? []);
            $grandTotalAllUpt += $grandTotalUpt[$upt->id];
            $grandTotalUptTarget[$upt->id] = (float) UptComparison::query()
                ->where('upt_id', $upt->id)
                ->where('year', $year)
                ->sum('target_amount');
        }
        $grandTotalTarget = (float) TaxTarget::query()->where('year', $year)->sum('target_amount');

        $availableYears = TaxTarget::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('admin.upt-comparisons.report', compact(
            'upts', 'year', 'taxTypes', 'targets', 'availableYears',
            'uptRealizationTotals', 'uptTargets', 'grandTotalTarget', 'grandTotalUpt',
            'grandTotalUptTarget', 'grandTotalAllUpt'
        ));
    }

    public function exportReport(Request $request): BinaryFileResponse
    {
        $year = $request->integer('year', (int) date('Y'));
        $filename = "perbandingan-target-apbd-dengan-upt-{$year}.xlsx";

        return Excel::download(new UptComparisonReportExport($year), $filename);
    }
}
