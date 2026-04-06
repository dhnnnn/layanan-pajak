<?php

namespace App\Http\Requests\FieldOfficer;

use Illuminate\Foundation\Http\FormRequest;

class AchievementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'year' => 'nullable|integer',
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|string|in:name,sptpd,bayar,tunggakan,selisih',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'status_filter' => 'nullable|string',
            'tax_type_id' => 'nullable|uuid',
            'district_id' => 'nullable|uuid',
        ];
    }
}
