<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AdminCabinetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'station_id'     => 'required|exists:charger_stations,id',
            'cabinet_number' => 'required|integer|min:1',
            'total_chargers' => 'required|integer|min:1|max:4',
            'status'         => 'required|in:available,busy,offline,maintenance',
        ];
    }

    public function messages(): array
    {
        return [
            'station_id.exists' => 'Station does not exist',
        ];
    }
}
