<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUptRequest extends FormRequest
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
        $uptId = $this->route('upt')?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20', 'unique:upts,code,'.$uptId],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama UPT wajib diisi.',
            'name.max' => 'Nama UPT maksimal 100 karakter.',
            'code.max' => 'Kode UPT maksimal 20 karakter.',
            'code.unique' => 'Kode UPT sudah digunakan.',
            'description.max' => 'Deskripsi maksimal 500 karakter.',
        ];
    }
}
