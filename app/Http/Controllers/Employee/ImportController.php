<?php

namespace App\Http\Controllers\Employee;

use App\Actions\Tax\ImportTaxRealizationAction;
use App\Actions\Tax\PreviewTaxRealizationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\ImportRequest;
use App\Models\ImportLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(Request $request): View
    {
        $importLogs = ImportLog::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return view('employee.import.index', compact('importLogs'));
    }

    public function preview(
        ImportRequest $request,
        PreviewTaxRealizationAction $previewImport,
    ): View {
        $result = $previewImport($request->file('file'));

        return view('employee.import.preview', [
            'storedPath' => $result['stored_path'],
            'totalRows' => $result['total_rows'],
            'previewData' => $result['preview_data'],
            'fileName' => $request->file('file')->getClientOriginalName(),
        ]);
    }

    public function confirm(
        Request $request,
        ImportTaxRealizationAction $importRealization,
    ): RedirectResponse {
        $request->validate([
            'stored_path' => ['required', 'string'],
            'file_name' => ['required', 'string'],
        ]);

        $importLog = $importRealization(
            storedPath: $request->string('stored_path')->toString(),
            originalFileName: $request->string('file_name')->toString(),
            user: $request->user(),
        );

        return redirect()
            ->route('pegawai.import.index')
            ->with(
                'success',
                "Import selesai: {$importLog->success_rows} baris berhasil, {$importLog->failed_rows} baris gagal.",
            );
    }
}
