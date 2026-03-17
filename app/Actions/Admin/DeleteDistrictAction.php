<?php

namespace App\Actions\Admin;

use App\Models\District;

class DeleteDistrictAction
{
    public function __invoke(District $district): void
    {
        $district->delete();
    }
}
