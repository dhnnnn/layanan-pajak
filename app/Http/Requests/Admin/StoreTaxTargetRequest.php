<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaxTargetRequest extends FormRequest
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
        return [
            'tax_type_id' => [
                'required',
                'string',
                Rule::exists('tax_types', 'id'),
            ],
            'year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                Rule::unique('tax_targets', 'year')->where(
                    'tax_type_id',
                    $this->input('tax_type_id'),
                ),
            ],
            'target_amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tax_type_id.required' => 'Jenis pajak wajib dipilih.',
            'tax_type_id.exists' => 'Jenis pajak tidak ditemukan.',
            'year.required' => 'Tahun wajib diisi.',
            'year.integer' => 'Tahun harus berupa angka.',
            'year.min' => 'Tahun minimal 2000.',
            'year.max' => 'Tahun maksimal 2100.',
            'year.unique' => 'Target untuk jenis pajak dan tahun ini sudah ada.',
            'target_amount.required' => 'Jumlah target wajib diisi.',
            'target_amount.numeric' => 'Jumlah target harus berupa angka.',
            'target_amount.min' => 'Jumlah target tidak boleh negatif.',
        ];
    }
}
