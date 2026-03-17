<?php

namespace App\Actions\Upt;

use App\Models\Upt;

class AssignDistrictsToUptAction
{
    /**
     * @param  array<string>  $districtIds
     */
    public function __invoke(Upt $upt, array $districtIds): void
    {
        $upt->districts()->sync($districtIds);
    }
}
