<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Upt\GenerateComparisonReportAction;
use App\Actions\Upt\GetUptComparisonDataAction;
use App\Actions\Upt\ImportUptComparisonAction;
use App\Actions\Upt\PreviewUptComparisonAction;
use App\Actions\Upt\UpsertUptComparisonTargetsAction;
use App\Exports\TaxRealizationTemplateExport;
use App\Exports\UptComparisonReportExport;
use App\Exports\UptComparisonTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportUptComparisonRequest;
use App\Http\Requests\Admin\UpdateUptTargetRequest;
use App\Models\Upt;
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

    public function manage(Request $request, GetUptComparisonDataAction $getData): View
    {
        $year = (int) $request->query('year', date('Y'));
        $uptId = $request->query('upt_id');

        $result = $getData($year, $uptId);

        return view('admin.upt-comparisons.manage', $result);
    }

    public function upsert(
        UpdateUptTargetRequest $request,
        UpsertUptComparisonTargetsAction $upsertTargets
    ): RedirectResponse {
        $validated = $request->validated();

        $upsertTargets(
            uptId: $validated['upt_id'],
            year: $validated['year'],
            targets: $validated['targets']
        );

        return redirect()
            ->route('admin.upt-comparisons.manage', [
                'year' => $validated['year'],
                'upt_id' => $validated['upt_id'],
            ])
            ->with('success', 'Target UPT berhasil diperbarui.');
    }
}
