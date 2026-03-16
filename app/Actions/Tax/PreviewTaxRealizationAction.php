<?php

namespace App\Actions\Tax;

use App\Imports\TaxRealizationImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class PreviewTaxRealizationAction
{
    /**
     * Store the uploaded file temporarily, parse it in preview mode,
     * and return the structured preview data without persisting anything.
     *
     * @return array{
     *     stored_path: string,
     *     total_rows: int,
     *     preview_data: list<array{
     *         row: int,
     *         kode_jenis_pajak: string|null,
     *         nama_jenis_pajak: string|null,
     *         kode_kecamatan: string|null,
     *         nama_kecamatan: string|null,
     *         tahun: string|int|null,
     *         januari: float,
     *         februari: float,
     *         maret: float,
     *         april: float,
     *         mei: float,
     *         juni: float,
     *         juli: float,
     *         agustus: float,
     *         september: float,
     *         oktober: float,
     *         november: float,
     *         desember: float,
     *         is_valid: bool,
     *         errors: list<string>,
     *     }>,
     * }
     */
    public function __invoke(UploadedFile $file): array
    {
        $storedPath = $file->store('imports/tax-realizations/pending', 'local');

        $import = new TaxRealizationImport(previewOnly: true);

        Excel::import($import, $storedPath, 'local');

        return [
            'stored_path' => $storedPath,
            'total_rows' => $import->getTotalRows(),
            'preview_data' => $import->getPreviewData(),
        ];
    }
}
