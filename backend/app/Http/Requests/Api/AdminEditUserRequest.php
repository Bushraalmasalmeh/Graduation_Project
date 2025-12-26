<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AdminEditUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user') ?? $this->id;

        return [
            'name'       => 'required|string|min:3|max:50',
            'email'      => 'required|email|unique:users,email,' . $userId,
            'department' => 'required|string',
            'status'     => 'required|in:active,disabled,blocked',
            'warnings'   => 'required|integer|min:0',
            'role_type'  => 'required|in:admin,staff,faculty,staff_faculty',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email already exists',
        ];
    }
}
