<?php

namespace App\Actions\Admin;

use App\Models\TaxType;

class CreateTaxTypeAction
{
    /**
     * @param  array{name: string, code: string}  $data
     */
    public function execute(array $data): TaxType
    {
        return TaxType::query()->create($data);
    }
}
