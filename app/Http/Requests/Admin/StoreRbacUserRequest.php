<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRbacUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,id'],
            'upt_id' => ['nullable', 'string', 'exists:upts,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama user wajib diisi.',
            'name.max' => 'Nama user maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'roles.required' => 'Minimal satu role wajib dipilih.',
            'roles.*.exists' => 'Role tidak ditemukan.',
            'upt_id.exists' => 'UPT tidak ditemukan.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function prepareForValidation(): void
    {
        // Validate upt_id required if kepala_upt role selected
        $kepalaUptRole = Role::where('name', 'kepala_upt')->first();
        if ($kepalaUptRole && in_array($kepalaUptRole->id, (array) $this->roles, true)) {
            $this->merge(['_requires_upt' => true]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $kepalaUptRole = Role::where('name', 'kepala_upt')->first();
            if ($kepalaUptRole && in_array($kepalaUptRole->id, (array) $this->roles, true)) {
                if (empty($this->upt_id)) {
                    $v->errors()->add('upt_id', 'UPT wajib dipilih untuk role Kepala UPT.');
                }
            }
        });
    }
}
