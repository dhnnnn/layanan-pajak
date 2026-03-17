<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateEmployeeAction
{
    /**
     * @param array{
     *     name: string,
     *     email: string,
     *     upt_id?: int|null,
     *     password?: string|null,
     *     district_ids?: array<int>|null,
     * } $data
     */
    public function execute(array $data, User $employee): User
    {
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'upt_id' => $data['upt_id'] ?? null,
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $employee->update($updateData);

        return $employee;
    }
}
