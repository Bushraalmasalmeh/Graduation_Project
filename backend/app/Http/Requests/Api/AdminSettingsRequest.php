<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AdminSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'daily_limit_hours'    => 'required|integer|min:1|max:24',
            'opening_time'         => 'required|date_format:H:i',
            'closing_time'         => 'required|date_format:H:i|after:opening_time',
            'maintenance_mode'     => 'boolean',
            'max_warnings'         => 'required|integer|min:1|max:10',
            'grace_period_minutes' => 'required|integer|min:0|max:60',
        ];
    }

    public function messages(): array
    {
        return [
            'opening_time.required' => 'Opening time is required',
            'closing_time.after'    => 'Closing time must be after opening time',
        ];
    }
}
