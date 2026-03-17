<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignUptDistrictRequest extends FormRequest
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
            'district_ids' => ['required', 'array', 'min:1'],
            'district_ids.*' => ['required', 'string', 'exists:districts,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'district_ids.required' => 'Pilih minimal satu kecamatan.',
            'district_ids.array' => 'Format data kecamatan tidak valid.',
            'district_ids.min' => 'Pilih minimal satu kecamatan.',
            'district_ids.*.required' => 'ID kecamatan wajib diisi.',
            'district_ids.*.string' => 'ID kecamatan tidak valid.',
            'district_ids.*.exists' => 'Kecamatan tidak ditemukan.',
        ];
    }
}
