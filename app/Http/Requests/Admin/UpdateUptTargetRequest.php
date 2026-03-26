<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUptTargetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'upt_id' => ['required', 'exists:upts,id'],
            'targets' => ['required', 'array'],
            'targets.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'year.required' => 'Tahun wajib dipilih.',
            'upt_id.required' => 'UPT wajib dipilih.',
            'upt_id.exists' => 'UPT tidak valid.',
            'targets.required' => 'Data target tidak ditemukan.',
            'targets.*.min' => 'Target tidak boleh negatif.',
        ];
    }
}
