<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'station_name'     => 'required|string',
            'start_time'       => 'nullable|date',
            'duration_minutes' => 'required|integer|in:60,90,120',
        ];
    }
}
