<?php

namespace App\Actions\Upt;

use App\Models\Upt;

class CreateUptAction
{
    /**
     * @param  array{name: string, code: string}  $data
     */
    public function execute(array $data): Upt
    {
        return Upt::query()->create($data);
    }
}
