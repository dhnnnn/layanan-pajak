<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $employeeId = $this->route('employee')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employeeId),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'upt_id' => ['nullable', 'integer', 'exists:upts,id'],
            'district_ids' => ['nullable', 'array'],
            'district_ids.*' => ['integer', 'exists:districts,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama pegawai wajib diisi.',
            'name.max' => 'Nama pegawai maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'upt_id.integer' => 'UPT tidak valid.',
            'upt_id.exists' => 'UPT tidak ditemukan.',
            'district_ids.array' => 'Format data kecamatan tidak valid.',
            'district_ids.*.integer' => 'ID kecamatan harus berupa angka.',
            'district_ids.*.exists' => 'Kecamatan tidak ditemukan.',
        ];
    }
}
