<?php

namespace App\Exports;

use App\Exports\Sheets\ReferenceSheet;
use App\Exports\Sheets\TemplateSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TaxRealizationTemplateExport implements WithMultipleSheets
{
    public function __construct(
        private readonly ?int $year = null,
        private readonly ?string $districtCode = null,
    ) {}

    /** @return list<TemplateSheet|ReferenceSheet> */
    public function sheets(): array
    {
        return [
            new TemplateSheet($this->year, $this->districtCode),
            new ReferenceSheet,
        ];
    }
}
