<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth()->id())],
            'current_password'      => ['required_with:password', 'nullable', 'string'],
            'password'              => ['nullable', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'             => 'Nama wajib diisi.',
            'email.required'            => 'Email wajib diisi.',
            'email.email'               => 'Format email tidak valid.',
            'email.unique'              => 'Email sudah digunakan akun lain.',
            'current_password.required_with' => 'Password lama wajib diisi jika ingin mengganti password.',
            'password.min'              => 'Password baru minimal 8 karakter.',
            'password.confirmed'        => 'Konfirmasi password tidak cocok.',
        ];
    }

    /**
     * Additional validation after rules pass.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v) {
            if ($this->filled('password') && $this->filled('current_password')) {
                if (!Hash::check($this->input('current_password'), auth()->user()->password)) {
                    $v->errors()->add('current_password', 'Password lama tidak sesuai.');
                }
            }
        });
    }
}
