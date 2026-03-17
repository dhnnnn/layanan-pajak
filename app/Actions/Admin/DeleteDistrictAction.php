<?php

namespace App\Actions\Admin;

use App\Models\District;

class DeleteDistrictAction
{
    public function execute(District $district): void
    {
        $district->delete();
    }
}
