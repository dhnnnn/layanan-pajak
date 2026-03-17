<?php

namespace App\Actions\Admin;

use App\Models\District;

class CreateDistrictAction
{
    /**
     * @param  array{name: string, code?: string}  $data
     */
    public function __invoke(array $data): District
    {
        return District::query()->create($data);
    }
}
