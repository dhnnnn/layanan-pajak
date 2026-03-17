<?php

namespace App\Actions\Admin;

use App\Models\TaxType;

class DeleteTaxTypeAction
{
    public function execute(TaxType $taxType): void
    {
        $taxType->delete();
    }
}
