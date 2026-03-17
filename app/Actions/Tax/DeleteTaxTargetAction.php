<?php

namespace App\Actions\Tax;

use App\Models\TaxTarget;

class DeleteTaxTargetAction
{
    public function __invoke(TaxTarget $taxTarget): void
    {
        $taxTarget->delete();
    }
}
