<?php

namespace App\Actions\Upt;

use App\Models\Upt;

class UpdateUptAction
{
    /**
     * @param  array{name: string, code: string}  $data
     */
    public function __invoke(array $data, Upt $upt): Upt
    {
        $upt->update($data);

        return $upt;
    }
}
