<?php

namespace App\Actions\Admin;

use App\Models\District;

class UpdateDistrictAction
{
    /**
     * @param  array{name: string, code: string}  $data
     */
    public function __invoke(array $data, District $district): District
    {
        $district->update($data);

        return $district;
    }
}
