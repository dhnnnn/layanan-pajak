<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUptAdditionalTargetRequest extends FormRequest
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
            'no_ayat' => ['required', 'string', 'max:20'],
            'start_quarter' => ['required', 'integer', 'min:1', 'max:4'],
            'additional_target' => ['required', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'no_ayat.required' => 'Jenis pajak wajib dipilih.',
            'start_quarter.required' => 'Mulai tribulan wajib dipilih.',
            'additional_target.required' => 'Nominal target tambahan wajib diisi.',
            'additional_target.min' => 'Nominal target tambahan harus lebih dari 0.',
        ];
    }
}
