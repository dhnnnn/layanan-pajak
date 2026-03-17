<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxTypeRequest extends FormRequest
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
        $taxTypeId = $this->route('tax_type')?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20', 'unique:tax_types,code,'.$taxTypeId],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama jenis pajak wajib diisi.',
            'name.max' => 'Nama jenis pajak maksimal 100 karakter.',
            'code.max' => 'Kode jenis pajak maksimal 20 karakter.',
            'code.unique' => 'Kode jenis pajak sudah digunakan.',
        ];
    }
}
