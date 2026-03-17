<?php

namespace App\Actions\Tax;

use App\Models\TaxTarget;

class UpdateTaxTargetAction
{
    /**
     * @param  array{tax_type_id: string, year: int, target_amount: numeric}  $data
     */
    public function __invoke(array $data, TaxTarget $taxTarget): TaxTarget
    {
        $taxTarget->update($data);

        return $taxTarget;
    }
}
