<?php

namespace App\Actions\Tax;

use App\Models\TaxRealization;

class CalculateTaxRealizationAction
{
    /**
     * @return array{
     *     q1: float,
     *     q2: float,
     *     q3: float,
     *     q4: float,
     *     annual: float,
     * }
     */
    public function __invoke(TaxRealization $taxRealization): array
    {
        $q1 =
            (float) $taxRealization->january +
            (float) $taxRealization->february +
            (float) $taxRealization->march;

        $q2 =
            (float) $taxRealization->april +
            (float) $taxRealization->may +
            (float) $taxRealization->june;

        $q3 =
            (float) $taxRealization->july +
            (float) $taxRealization->august +
            (float) $taxRealization->september;

        $q4 =
            (float) $taxRealization->october +
            (float) $taxRealization->november +
            (float) $taxRealization->december;

        return [
            'q1' => $q1,
            'q2' => $q2,
            'q3' => $q3,
            'q4' => $q4,
            'annual' => $q1 + $q2 + $q3 + $q4,
        ];
    }
}
