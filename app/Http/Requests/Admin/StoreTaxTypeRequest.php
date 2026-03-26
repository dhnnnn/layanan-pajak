<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaxTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $taxTypeId = $this->route('tax_type')?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('tax_types', 'code')->ignore($taxTypeId)],
            'parent_id' => ['nullable', 'string', Rule::exists('tax_types', 'id')->whereNull('parent_id')],
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
            'parent_id.exists' => 'Induk jenis pajak tidak valid atau tidak ditemukan.',
        ];
    }
}
