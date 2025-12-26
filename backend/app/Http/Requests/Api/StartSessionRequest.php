<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StartSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uid'        => 'required|string|exists:chargers,uid',
            'job_number' => 'required|string|exists:users,job_number',
        ];
    }

    public function messages(): array
    {
        return [
            'uid.exists'        => 'Charger UID not recognized',
            'job_number.exists' => 'User job number not found',
        ];
    }
}
