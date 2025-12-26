<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AdminStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'station_name'   => 'required|string',
            'station_code'   => 'required|string',
            'location'       => 'required|string',
            'department'     => 'nullable|string',
            'total_cabinets' => 'required|integer|min:1',
            'status'         => 'required|in:active,offline,maintenance',
            'description'    => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'station_name.required' => 'Station name is required',
            'station_code.required' => 'Station code is required',
        ];
    }
}
