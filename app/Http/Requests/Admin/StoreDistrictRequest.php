<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDistrictRequest extends FormRequest
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
        $districtId = $this->route('district')?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20', 'unique:districts,code,'.$districtId],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama kecamatan wajib diisi.',
            'name.max' => 'Nama kecamatan maksimal 100 karakter.',
            'code.max' => 'Kode kecamatan maksimal 20 karakter.',
            'code.unique' => 'Kode kecamatan sudah digunakan.',
        ];
    }
}
