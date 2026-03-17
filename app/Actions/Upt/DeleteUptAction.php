<?php

namespace App\Actions\Upt;

use App\Models\Upt;

class DeleteUptAction
{
    public function execute(Upt $upt): void
    {
        $upt->delete();
    }
}
