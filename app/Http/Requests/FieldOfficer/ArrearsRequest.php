<?php

namespace App\Http\Requests\FieldOfficer;

use Illuminate\Foundation\Http\FormRequest;

class ArrearsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'year' => 'nullable|integer',
            'month' => 'nullable|integer|min:1|max:12',
            'search' => 'nullable|string|max:100',
            'district_id' => 'nullable|uuid',
            'status' => 'nullable|string|in:semua,lunas,belum_lunas',
        ];
    }
}
