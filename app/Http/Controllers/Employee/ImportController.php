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

        $districts = $request->user()->districts()->orderBy('name')->get();

        return view('employee.import.index', compact('importLogs', 'districts'));
    }

    public function preview(
        ImportRequest $request,
        PreviewTaxRealizationAction $previewImport,
    ): View {
        $request->validate([
            'district_id' => ['required', 'exists:employee_districts,district_id,user_id,'.$request->user()->id],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $result = $previewImport(
            file: $request->file('file'),
            districtId: $request->string('district_id')->toString(),
            year: $request->integer('year')
        );

        return view('employee.import.preview', [
            'storedPath' => $result['stored_path'],
            'totalRows' => $result['total_rows'],
            'previewData' => $result['preview_data'],
            'fileName' => $request->file('file')->getClientOriginalName(),
            'district_id' => $request->string('district_id')->toString(),
            'year' => $request->integer('year'),
        ]);
    }

    public function confirm(
        Request $request,
        ImportTaxRealizationAction $importRealization,
    ): RedirectResponse {
        $request->validate([
            'stored_path' => ['required', 'string'],
            'file_name' => ['required', 'string'],
            'district_id' => ['required', 'exists:employee_districts,district_id,user_id,'.$request->user()->id],
            'year' => ['required', 'integer'],
        ]);

        $importLog = $importRealization(
            storedPath: $request->string('stored_path')->toString(),
            originalFileName: $request->string('file_name')->toString(),
            user: $request->user(),
            districtId: $request->string('district_id')->toString(),
            year: $request->integer('year'),
        );

        return redirect()
            ->route('pegawai.import.index')
            ->with(
                'success',
                "Import selesai: {$importLog->success_rows} baris berhasil, {$importLog->failed_rows} baris gagal.",
            );
    }
}
