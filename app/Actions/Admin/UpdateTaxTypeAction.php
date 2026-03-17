<?php

namespace App\Actions\Admin;

use App\Models\TaxType;

class UpdateTaxTypeAction
{
    /**
     * @param  array{name: string, code: string}  $data
     */
    public function __invoke(array $data, TaxType $taxType): TaxType
    {
        $taxType->update($data);

        return $taxType;
    }
}
