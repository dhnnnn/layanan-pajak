<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateEmployeeAction
{
    /**
     * @param array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     upt_id?: string|null,
     *     district_ids?: array<string>,
     * } $data
     */
    public function __invoke(array $data): User
    {
        $employee = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'upt_id' => $data['upt_id'] ?? null,
        ]);

        $employee->assignRole('pegawai');

        if (! empty($data['district_ids'])) {
            $employee->districts()->sync($data['district_ids']);
        }

        return $employee;
    }
}
