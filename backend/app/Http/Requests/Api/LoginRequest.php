<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|string',
            'device_token' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.email'    => 'Invalid email format',
            'email.exists'   => 'Email not found',

            'password.required' => 'Password is required',
        ];
    }
}
