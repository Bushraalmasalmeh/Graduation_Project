<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class GetAvailabilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => 'required|date_format:Y-m-d',
            'duration' => 'required|integer|in:60,90,120',
        ];
    }
    public function messages(): array
    {
        return [
            'date.required' => 'Please select a date.',
            'date.date_format' => 'The date format must be YYYY-MM-DD.',
            'duration.required' => 'Please specify the charging duration.',
            'duration.in' => 'Duration must be either 60, 90, or 120 minutes.',
        ];
    }
}
