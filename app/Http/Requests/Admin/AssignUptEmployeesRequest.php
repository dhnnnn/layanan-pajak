<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignUptEmployeesRequest extends FormRequest
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
        return [
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['required', 'string', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_ids.array' => 'Format data pegawai tidak valid.',
            'user_ids.*.required' => 'ID pegawai wajib diisi.',
            'user_ids.*.string' => 'ID pegawai tidak valid.',
            'user_ids.*.exists' => 'Pegawai tidak ditemukan.',
        ];
    }
}
