<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'group' => ['required', 'string', 'in:master-data,monitoring,field-officer,rbac'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama permission wajib diisi.',
            'name.max' => 'Nama permission maksimal 255 karakter.',
            'name.unique' => 'Nama permission sudah digunakan.',
            'group.required' => 'Group permission wajib dipilih.',
            'group.in' => 'Group permission tidak valid.',
        ];
    }
}
