<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CrawlMapsDiscoveryRequest extends FormRequest
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
            'tax_type_code' => ['nullable', 'string', 'max:10'],
            'district_id' => ['nullable', 'string', 'exists:districts,id'],
            'keyword' => ['nullable', 'string', 'max:200'],
            'max_results' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }

    /**
     * Sanitize input sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('keyword') && is_string($this->keyword)) {
            $this->merge([
                'keyword' => strip_tags($this->keyword),
            ]);
        }
    }

    /**
     * Custom validation: tax_type_code atau keyword harus diisi minimal salah satu.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (empty($this->tax_type_code) && empty(trim($this->keyword ?? ''))) {
                $v->errors()->add('tax_type_code', 'Pilih jenis pajak atau isi keyword pencarian.');
            }
        });
    }
}
