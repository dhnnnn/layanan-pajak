<?php

namespace App\Actions\Tax;

use App\Models\TaxRealization;
use App\Models\User;

class StoreTaxRealizationAction
{
    /**
     * @param array{
     *     tax_type_id: int,
     *     district_id: int,
     *     year: int,
     *     january: float,
     *     february: float,
     *     march: float,
     *     april: float,
     *     may: float,
     *     june: float,
     *     july: float,
     *     august: float,
     *     september: float,
     *     october: float,
     *     november: float,
     *     december: float,
     * } $data
     */
    public function __invoke(array $data, User $user): TaxRealization
    {
        return TaxRealization::query()->updateOrCreate(
            [
                'tax_type_id' => $data['tax_type_id'],
                'district_id' => $data['district_id'],
                'year' => $data['year'],
            ],
            array_merge($data, ['user_id' => $user->id]),
        );
    }
}
