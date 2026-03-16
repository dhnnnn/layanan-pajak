<?php

namespace App\Http\Requests\Employee;

use App\Models\TaxType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaxRealizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $districtId = (int) $this->input('district_id');

        return $this->user()
            ->districts()
            ->where('districts.id', $districtId)
            ->exists();
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $monthColumns = [
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december',
        ];

        $monthRules = [];

        foreach ($monthColumns as $month) {
            $monthRules[$month] = ['nullable', 'numeric', 'min:0'];
        }

        return array_merge(
            [
                'tax_type_id' => [
                    'required',
                    'integer',
                    Rule::exists(TaxType::class, 'id'),
                ],
                'district_id' => [
                    'required',
                    'integer',
                    Rule::exists('districts', 'id'),
                ],
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            ],
            $monthRules,
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tax_type_id.required' => 'Jenis pajak wajib dipilih.',
            'tax_type_id.exists' => 'Jenis pajak yang dipilih tidak valid.',
            'district_id.required' => 'Kecamatan wajib dipilih.',
            'district_id.exists' => 'Kecamatan yang dipilih tidak valid.',
            'year.required' => 'Tahun wajib diisi.',
            'year.integer' => 'Tahun harus berupa angka.',
            'year.min' => 'Tahun tidak boleh kurang dari 2000.',
            'year.max' => 'Tahun tidak boleh lebih dari 2100.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tax_type_id' => 'jenis pajak',
            'district_id' => 'kecamatan',
            'year' => 'tahun',
            'january' => 'Januari',
            'february' => 'Februari',
            'march' => 'Maret',
            'april' => 'April',
            'may' => 'Mei',
            'june' => 'Juni',
            'july' => 'Juli',
            'august' => 'Agustus',
            'september' => 'September',
            'october' => 'Oktober',
            'november' => 'November',
            'december' => 'Desember',
        ];
    }
}
