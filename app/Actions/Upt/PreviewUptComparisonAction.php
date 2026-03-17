<?php

namespace App\Actions\Upt;

use App\Imports\UptComparisonImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class PreviewUptComparisonAction
{
    /**
     * @return array{
     *     stored_path: string,
     *     total_rows: int,
     *     preview_data: list<array<string, mixed>>
     * }
     */
    public function __invoke(UploadedFile $file, ?int $year = null): array
    {
        $storedPath = $file->store('imports/upt-comparisons/pending', 'local');

        $import = new UptComparisonImport(previewOnly: true, year: $year);

        Excel::import($import, $storedPath, 'local');

        return [
            'stored_path' => $storedPath,
            'total_rows' => $import->getTotalRows(),
            'preview_data' => $import->getPreviewData(),
        ];
    }
}
