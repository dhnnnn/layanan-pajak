<?php

namespace App\Http\Controllers\FieldOfficer;

use App\Actions\FieldOfficer\ExportFieldOfficerRealizationPdfAction;
use App\Actions\FieldOfficer\ExportFieldOfficerTargetExcelAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function exportPdf(Request $request, ExportFieldOfficerRealizationPdfAction $action): Response
    {
        $year = $request->integer('year', (int) date('Y'));

        return $action->execute($request->user(), $year);
    }

    public function exportExcel(Request $request, ExportFieldOfficerTargetExcelAction $action): BinaryFileResponse
    {
        return $action->execute($request->user(), $request->all());
    }
}
