<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CancelBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => 'required|integer|exists:bookings,id',
        ];
    }


    public function messages(): array
    {
        return [
            'booking_id.required' => 'Booking ID is required',
            'booking_id.exists'   => 'Booking not found',
        ];
    }
}
