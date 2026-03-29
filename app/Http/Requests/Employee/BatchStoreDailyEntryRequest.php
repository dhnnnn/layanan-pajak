<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class BatchStoreDailyEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'district_id' => ['required', 'string', 'exists:districts,id'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.tax_type_id' => ['required', 'string', 'exists:tax_types,id'],
            'entries.*.entry_date' => ['required', 'date'],
            'entries.*.amount' => ['required', 'numeric', 'min:0.01'],
            'entries.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
