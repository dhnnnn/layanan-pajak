<?php

namespace App\Actions\Upt;

use App\Models\Upt;

class AssignDistrictsToUptAction
{
    /**
     * @param  array<int>  $districtIds
     */
    public function execute(Upt $upt, array $districtIds): void
    {
        $upt->districts()->sync($districtIds);
    }
}
