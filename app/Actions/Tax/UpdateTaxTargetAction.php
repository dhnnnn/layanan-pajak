<?php

namespace App\Actions\Tax;

use App\Models\TaxTarget;

class UpdateTaxTargetAction
{
    /**
     * @param  array{tax_type_id: int, year: int, target_amount: numeric}  $data
     */
    public function execute(array $data, TaxTarget $taxTarget): TaxTarget
    {
        $taxTarget->update($data);

        return $taxTarget;
    }
}
