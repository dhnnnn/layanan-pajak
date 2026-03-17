<?php

namespace App\Actions\Admin;

use App\Models\TaxType;

class DeleteTaxTypeAction
{
    public function __invoke(TaxType $taxType): void
    {
        $taxType->delete();
    }
}
