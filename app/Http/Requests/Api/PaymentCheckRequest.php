<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PaymentCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'npwpd' => ['required', 'string'],
            'tahun' => ['required', 'digits:4'],
            'nama_wp' => ['required', 'string'],
            'npwpd_lama' => ['nullable', 'boolean'],
        ];
    }
}
