<?php

namespace App\Actions\Upt;

use App\Models\Upt;

class DeleteUptAction
{
    public function __invoke(Upt $upt): void
    {
        $upt->delete();
    }
}
