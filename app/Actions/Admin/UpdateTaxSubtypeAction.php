<?php

namespace App\Actions\Admin;

use App\Models\TaxType;

class UpdateTaxSubtypeAction
{
    /**
     * @param  array{name: string}  $data
     */
    public function __invoke(array $data, TaxType $subtype): TaxType
    {
        $subtype->update(['name' => $data['name']]);

        return $subtype;
    }
}
