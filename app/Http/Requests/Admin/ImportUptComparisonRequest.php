<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportUptComparisonRequest extends FormRequest
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
        // For preview, file is required
        // For import (confirm), stored_path is required
        if ($this->routeIs('admin.upt-comparisons.preview')) {
            return [
                'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
                'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            ];
        }

        // For import confirmation
        return [
            'stored_path' => ['required', 'string'],
            'file_name' => ['required', 'string'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'File Excel wajib diunggah.',
            'file.file' => 'Unggahan harus berupa file.',
            'file.mimes' => 'File harus berformat .xlsx atau .xls.',
            'file.max' => 'Ukuran file tidak boleh melebihi 10 MB.',
            'year.required' => 'Tahun wajib dipilih.',
            'year.integer' => 'Tahun harus berupa angka.',
            'year.min' => 'Tahun minimal 2020.',
            'year.max' => 'Tahun maksimal 2100.',
            'stored_path.required' => 'Path file tidak ditemukan.',
            'file_name.required' => 'Nama file tidak ditemukan.',
        ];
    }
}
