<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Upt\GenerateComparisonReportAction;
use App\Actions\Upt\ImportUptComparisonAction;
use App\Actions\Upt\PreviewUptComparisonAction;
use App\Exports\TaxRealizationTemplateExport;
use App\Exports\UptComparisonReportExport;
use App\Exports\UptComparisonTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportUptComparisonRequest;
use App\Http\Requests\Admin\UpdateUptTargetRequest;
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
        $type = $request->query('type', 'apbd');
        $year = $request->integer('year', (int) date('Y'));

        if ($type === 'upt') {
            $filename = 'template-perbandingan-target-upt-'.$year.'.xlsx';

            return Excel::download(
                new UptComparisonTemplateExport($year),
                $filename
            );
        }

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
            user: $request->user(),
            year: $request->integer('year'),
        );

        Storage::disk('local')->delete($storedPath);

        return redirect()
            ->route('admin.upt-comparisons.index')
            ->with('success', "Import selesai. Berhasil: {$importLog->success_rows}, Gagal: {$importLog->failed_rows}");
    }

    public function report(Request $request, GenerateComparisonReportAction $generateReport): View
    {
        $year = $request->integer('year', (int) date('Y'));
        $search = $request->string('search')->trim();

        $result = $generateReport($year, $search);

        return view('admin.upt-comparisons.report', $result);
    }

    public function exportReport(Request $request): BinaryFileResponse
    {
        $year = $request->integer('year', (int) date('Y'));
        $filename = "perbandingan-target-apbd-dengan-upt-{$year}.xlsx";

        return Excel::download(new UptComparisonReportExport($year), $filename);
    }

    public function manage(Request $request): View
    {
        $year = (int) $request->query('year', date('Y'));
        $uptId = $request->query('upt_id');

        $upts = Upt::query()->orderBy('code')->get();

        // Identify which UPTs have targets for the current year
        $uptsWithTargets = UptComparison::query()
            ->where('year', $year)
            ->distinct()
            ->pluck('upt_id')
            ->toArray();

        // Add a temporary property to each UPT to indicate if it has targets
        $upts->each(function ($upt) use ($uptsWithTargets) {
            $upt->has_targets = in_array($upt->id, $uptsWithTargets);
        });

        // Ensure the selected uptId is valid, otherwise default it
        if (! $uptId || ! $upts->contains('id', $uptId)) {
            $firstWithTargets = $upts->firstWhere('has_targets', true);
            $uptId = $firstWithTargets ? $firstWithTargets->id : ($upts->first()->id ?? null);
        }

        $taxTypes = TaxType::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('code')])
            ->orderBy('code')
            ->get();

        $targets = UptComparison::query()
            ->where('upt_id', $uptId)
            ->where('year', $year)
            ->pluck('target_amount', 'tax_type_id');

        // Pre-calculate parent totals for initial render
        foreach ($taxTypes as $taxType) {
            if ($taxType->children->isNotEmpty()) {
                $sum = 0;
                foreach ($taxType->children as $child) {
                    $sum += (float) ($targets[$child->id] ?? 0);
                }
                $targets[$taxType->id] = $sum;
            }
        }

        $availableYears = TaxTarget::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        if ($availableYears->isEmpty()) {
            $availableYears = collect([(int) date('Y')]);
        }

        return view('admin.upt-comparisons.manage', [
            'upts' => $upts,
            'taxTypes' => $taxTypes,
            'targets' => $targets,
            'availableYears' => $availableYears,
            'year' => $year,
            'uptId' => $uptId,
        ]);
    }

    public function upsert(UpdateUptTargetRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $year = $validated['year'];
        $uptId = $validated['upt_id'];
        $targets = $validated['targets'];

        // 1. First, save all individual targets (including those that might be children)
        foreach ($targets as $taxTypeId => $amount) {
            UptComparison::query()->updateOrCreate(
                [
                    'tax_type_id' => $taxTypeId,
                    'upt_id' => $uptId,
                    'year' => $year,
                ],
                [
                    'target_amount' => $amount ?? 0,
                ]
            );
        }

        // 2. Then, identify all parent tax types that have children
        $parentTaxTypes = TaxType::query()
            ->whereNull('parent_id')
            ->whereHas('children')
            ->with('children')
            ->get();

        // 3. For each parent, sum up its children's targets and update the parent target
        foreach ($parentTaxTypes as $parent) {
            $sum = UptComparison::query()
                ->where('upt_id', $uptId)
                ->where('year', $year)
                ->whereIn('tax_type_id', $parent->children->pluck('id'))
                ->sum('target_amount');

            UptComparison::query()->updateOrCreate(
                [
                    'tax_type_id' => $parent->id,
                    'upt_id' => $uptId,
                    'year' => $year,
                ],
                [
                    'target_amount' => $sum ?? 0,
                ]
            );
        }

        return redirect()
            ->route('admin.upt-comparisons.manage', ['year' => $year, 'upt_id' => $uptId])
            ->with('success', 'Target UPT berhasil diperbarui.');
    }
}
