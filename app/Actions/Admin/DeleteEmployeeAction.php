<?php

namespace App\Actions\Admin;

use App\Models\User;

class DeleteEmployeeAction
{
    public function execute(User $employee): void
    {
        $employee->delete();
    }
}
