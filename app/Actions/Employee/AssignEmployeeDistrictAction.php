<?php

namespace App\Actions\Employee;

use App\Models\User;

class AssignEmployeeDistrictAction
{
    /**
     * @param  array<int>  $districtIds
     */
    public function __invoke(User $user, array $districtIds): void
    {
        $user->districts()->sync($districtIds);
    }
}
