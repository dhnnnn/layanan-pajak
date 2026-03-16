<?php

namespace App\Exports;

use App\Exports\Sheets\ReferenceSheet;
use App\Exports\Sheets\TemplateSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TaxRealizationTemplateExport implements WithMultipleSheets
{
    /** @return list<TemplateSheet|ReferenceSheet> */
    public function sheets(): array
    {
        return [new TemplateSheet, new ReferenceSheet];
    }
}
