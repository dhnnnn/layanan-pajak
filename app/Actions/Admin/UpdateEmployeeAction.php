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
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $employee->update($updateData);

        if (array_key_exists('upt_id', $data)) {
            $employee->upts()->sync($data['upt_id'] ? [$data['upt_id']] : []);
        }

        if (isset($data['district_ids'])) {
            $employee->districts()->sync($data['district_ids']);
        }

        return $employee;
    }
}
