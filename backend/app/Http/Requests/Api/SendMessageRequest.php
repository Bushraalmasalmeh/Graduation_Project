<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => 'nullable|string|min:3',
            'email'   => 'nullable|email',
            'phone'   => 'required|string|min:8',
            'message' => 'required|string|min:5',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'  => 'Phone number is required',
            'phone.min'       => 'Phone must be at least 8 digits',

            'message.required' => 'Message is required',
            'message.min'      => 'Message must be at least 5 characters',
        ];
    }
}
