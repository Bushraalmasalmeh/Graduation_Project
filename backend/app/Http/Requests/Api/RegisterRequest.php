<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|min:6',
            'job_number'  => 'required|string',
            'department'  => 'required|string',
            'device_token' => 'nullable|string',
            'car_model' => 'required|string',
            'plate_number' => 'required|string|unique:cars'

        ];
    }
}
