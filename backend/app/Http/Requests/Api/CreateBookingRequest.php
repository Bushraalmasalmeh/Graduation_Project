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
            'start_time'       => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    try {
                        \Carbon\Carbon::parse($value, 'Asia/Amman');
                    } catch (\Exception $e) {
                        $fail('Invalid time format. Please use AM/PM format like "07:30 AM".');
                    }
                }
            ],
            'duration_minutes' => 'required|integer|in:60,90,120',
        ];
    }
}
