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
            'charger_id'       => 'required|integer|exists:chargers,id',
            'station_name'     => 'nullable|string',
            'start_time'       => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    try {
                        \Carbon\Carbon::parse($value, 'Asia/Amman');
                    } catch (\Exception $e) {
                        $fail('Invalid time format. Please use ISO format or AM/PM format.');
                    }
                }
            ],
            'duration_minutes' => 'required|integer|min:15|max:180',
        ];
    }
}
