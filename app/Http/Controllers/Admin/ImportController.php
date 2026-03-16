<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tax\ImportTaxRealizationAction;
use App\Actions\Tax\PreviewTaxRealizationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportRequest;
use App\Models\District;
use App\Models\ImportLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(): View
    {
        $importLogs = ImportLog::query()->with('user')->latest()->paginate(15);

        return view('admin.import.index', compact('importLogs'));
    }

    public function preview(
        ImportRequest $request,
        PreviewTaxRealizationAction $previewImport,
    ): View {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $result = $previewImport(
            file: $request->file('file'),
            year: $request->integer('year')
        );

        Log::info('Preview result: total_rows='.$result['total_rows'].', preview_count='.count($result['preview_data']));

        $districtCount = District::count();

        return view('admin.import.preview', [
            'storedPath' => $result['stored_path'],
            'totalRows' => $result['total_rows'],
            'previewData' => $result['preview_data'],
            'fileName' => $request->file('file')->getClientOriginalName(),
            'year' => $request->integer('year'),
            'districtCount' => $districtCount,
        ]);
    }

    public function confirm(
        Request $request,
        ImportTaxRealizationAction $importRealization,
    ): RedirectResponse {
        $request->validate([
            'stored_path' => ['required', 'string'],
            'file_name' => ['required', 'string'],
            'year' => ['required', 'integer'],
        ]);

        $districtCount = District::count();

        $importLog = $importRealization(
            storedPath: $request->string('stored_path')->toString(),
            originalFileName: $request->string('file_name')->toString(),
            user: $request->user(),
            year: $request->integer('year'),
        );

        return redirect()
            ->route('admin.import.index')
            ->with(
                'success',
                "Import selesai: {$importLog->success_rows} baris berhasil untuk {$districtCount} kecamatan, {$importLog->failed_rows} baris gagal.",
            );
    }
}
