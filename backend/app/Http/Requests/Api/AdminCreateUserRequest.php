<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AdminCreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => 'required|string|min:3|max:50',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:6',
            'job_number' => 'required|string|unique:users,job_number',
            'department' => 'required|string',
            'role_type'  => 'required|in:admin,staff,faculty,staff_faculty',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email already exists',
            'job_number.unique' => 'Job Number already exists',
        ];
    }
}
