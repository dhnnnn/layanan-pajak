<?php

namespace App\Actions\Admin;

use App\Models\TaxType;

class UpdateTaxTypeAction
{
    /**
     * @param  array{name: string, parent_id?: string|null}  $data
     */
    public function __invoke(array $data, TaxType $taxType): TaxType
    {
        $taxType->update($data);

        return $taxType;
    }
}
