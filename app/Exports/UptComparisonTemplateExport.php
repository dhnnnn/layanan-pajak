<?php

namespace App\Exports;

use App\Exports\Sheets\ReferenceSheet;
use App\Exports\Sheets\UptComparisonSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UptComparisonTemplateExport implements WithMultipleSheets
{
    public function __construct(
        private readonly ?int $year = null,
    ) {}

    /** @return list<UptComparisonSheet|ReferenceSheet> */
    public function sheets(): array
    {
        return [
            new UptComparisonSheet($this->year),
            new ReferenceSheet,
        ];
    }
}
