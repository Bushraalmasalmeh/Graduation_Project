<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AdminChargerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cabinet_id'     => 'required|exists:cabinets,id',
            'charger_number' => 'required|integer|min:1',
            'uid'            => 'required|string|unique:chargers,uid',
            'status'         => 'required|in:available,busy,offline,maintenance',
        ];
    }

    public function messages(): array
    {
        return [
            'uid.unique' => 'UID already exists',
        ];
    }
}
