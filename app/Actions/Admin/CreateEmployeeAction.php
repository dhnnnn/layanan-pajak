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

        return $employee;
    }
}
