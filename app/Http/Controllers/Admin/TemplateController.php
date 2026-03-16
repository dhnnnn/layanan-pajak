<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TaxRealizationTemplateExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TemplateController extends Controller
{
    public function download(): BinaryFileResponse
    {
        return Excel::download(
            new TaxRealizationTemplateExport,
            'template-realisasi-pajak.xlsx',
        );
    }
}
