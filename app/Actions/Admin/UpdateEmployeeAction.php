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
     *     upt_id?: string|null,
     *     password?: string|null,
     *     district_ids?: array<string>|null,
     * } $data
     */
    public function __invoke(array $data, User $employee): User
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
