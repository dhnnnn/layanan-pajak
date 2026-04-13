<?php

namespace App\Actions\Upt;

use App\Models\Upt;

class AssignEmployeesToUptAction
{
    /**
     * @param  array<string>  $userIds
     */
    public function __invoke(Upt $upt, array $userIds): void
    {
        $upt->users()->sync($userIds);
    }
}
