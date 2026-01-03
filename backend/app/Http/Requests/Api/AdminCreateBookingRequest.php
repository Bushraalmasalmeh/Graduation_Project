<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AdminCreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'     => 'required|exists:users,id',
            'station_id'  => 'required|exists:charger_stations,id',
            'cabinet_id'  => 'required|exists:cabinets,id',
            'charger_id'  => 'required|exists:chargers,id',
            'station_name' => 'required|string|exists:charger_stations,station_name',
            'uid' => 'nullable',
            'duration_minutes' => 'nullable|integer',
            'start_time'  => 'required|date',
            'end_time'    => 'nullable|date|after_or_equal:start_time',

            'status'      => 'required|in:pending,active,completed,cancelled',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'     => 'User is required',
            'station_id.required'  => 'Station is required',
            'cabinet_id.required'  => 'Cabinet is required',
            'charger_id.required'  => 'Charger is required',

            'user_id.exists'       => 'Invalid user',
            'station_id.exists'    => 'Invalid station',
            'cabinet_id.exists'    => 'Invalid cabinet',
            'charger_id.exists'    => 'Invalid charger',

            'start_time.required'  => 'Start time is required',
            'start_time.date'      => 'Invalid start time format',

            'end_time.date'        => 'Invalid end time format',
            'end_time.after_or_equal' => 'End time must be after start time',

            'status.required'      => 'Booking status is required',
            'status.in'            => 'Invalid booking status',
        ];
    }
}
