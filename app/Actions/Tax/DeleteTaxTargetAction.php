<?php

namespace App\Actions\Tax;

use App\Models\TaxTarget;

class DeleteTaxTargetAction
{
    public function execute(TaxTarget $taxTarget): void
    {
        $taxTarget->delete();
    }
}
