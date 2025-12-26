<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed'
            ],
            'job_number' => [
                'nullable',
                'string',
                'max:50'
            ],
            'department' => [
                'nullable',
                'string',
                'max:100'
            ],
            'role_type' => [
                'nullable',
                'string',
                'in:faculty,staff,student' // Limits input to these specific options
            ],
            'car_model' => [
                'nullable',
                'string',
                'max:100'
            ],
            'device_token' => [
                'nullable',
                'string'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Name is required',
            'name.min'       => 'Name must be at least 3 characters',
            'department.required' => 'Department is required',
            'job_number.exists'   => 'Job Number does not exist',
        ];
    }
}
