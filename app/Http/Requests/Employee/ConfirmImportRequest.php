<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmImportRequest extends FormRequest
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
            'stored_path' => ['required', 'string'],
            'file_name' => ['required', 'string'],
            'district_id' => [
                'required',
                'string',
                'exists:employee_districts,district_id,user_id,'.$this->user()->id,
            ],
            'year' => ['required', 'integer'],
        ];
    }
}
