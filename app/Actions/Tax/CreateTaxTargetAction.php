<?php

namespace App\Actions\Tax;

use App\Models\TaxTarget;

class CreateTaxTargetAction
{
    /**
     * @param  array{tax_type_id: string, year: int, target_amount: numeric}  $data
     */
    public function __invoke(array $data): TaxTarget
    {
        return TaxTarget::query()->create($data);
    }
}
