<?php

namespace App\Actions\Tax;

use App\Models\TaxTarget;

class ProcessTaxTargetImportAction
{
    /**
     * @param array<int, array{
     *     tax_type_id: string,
     *     year: int,
     *     target_amount: float,
     *     q1_target: float,
     *     q2_target: float,
     *     q3_target: float,
     *     q4_target: float,
     *     is_valid: bool
     * }> $previewData
     */
    public function __invoke(array $previewData): void
    {
        collect($previewData)
            ->where('is_valid', true)
            ->each(function (array $row) {
                TaxTarget::query()->updateOrCreate(
                    [
                        'tax_type_id' => $row['tax_type_id'],
                        'year' => $row['year'],
                    ],
                    [
                        'target_amount' => $row['target_amount'],
                        'q1_target' => $row['q1_target'],
                        'q2_target' => $row['q2_target'],
                        'q3_target' => $row['q3_target'],
                        'q4_target' => $row['q4_target'],
                    ]
                );
            });
    }
}
