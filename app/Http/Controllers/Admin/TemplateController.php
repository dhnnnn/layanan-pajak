<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TaxRealizationTemplateExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TemplateController extends Controller
{
    public function download(Request $request): BinaryFileResponse
    {
        $year = $request->integer('year', (int) date('Y'));

        $filename = 'template-realisasi-pajak-master-'.$year.'.xlsx';

        return Excel::download(
            new TaxRealizationTemplateExport($year, null),
            $filename
        );
    }
}
