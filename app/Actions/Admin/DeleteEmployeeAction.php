<?php

namespace App\Actions\Admin;

use App\Models\User;

class DeleteEmployeeAction
{
    public function __invoke(User $employee): void
    {
        $employee->delete();
    }
}
