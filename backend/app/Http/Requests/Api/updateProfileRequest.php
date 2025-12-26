<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => 'sometimes|string|max:255',
            'email'      => 'sometimes|email|unique:users,email,' . $this->user()->id,
            'car_model'  => 'nullable|string|max:255',
            'department' => 'sometimes|string|max:255',
            'job_number' => 'sometimes|string|max:255',
            'image'      => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Added for Kalbouna-level safety
        ];
    }
}
