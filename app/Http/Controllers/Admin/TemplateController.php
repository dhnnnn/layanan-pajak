<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TaxRealizationTemplateExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TemplateController extends Controller
{
    public function download(Request $request): BinaryFileResponse
    {
        $type = $request->query('type', 'apbd');
        $year = $request->integer('year', (int) date('Y'));


        // Default: APBD template
        $filename = 'template-realisasi-pajak-master-'.$year.'.xlsx';

        return Excel::download(
            new TaxRealizationTemplateExport($year, null),
            $filename
        );
    }

    public function index(): View
    {
        return view('admin.templates.index');
    }
}
