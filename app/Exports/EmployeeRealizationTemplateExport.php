<?php

namespace App\Exports;

use App\Exports\Sheets\EmployeeRealizationTemplateSheet;
use App\Exports\Sheets\ReferenceSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EmployeeRealizationTemplateExport implements WithMultipleSheets
{
    /**
     * @param  list<string>  $districtIds
     */
    public function __construct(
        private readonly int $year,
        private readonly string $uptId,
        private readonly array $districtIds,
    ) {}

    /** @return list<EmployeeRealizationTemplateSheet|ReferenceSheet> */
    public function sheets(): array
    {
        return [
            new EmployeeRealizationTemplateSheet($this->year, $this->uptId, $this->districtIds),
            new ReferenceSheet,
        ];
    }
}
