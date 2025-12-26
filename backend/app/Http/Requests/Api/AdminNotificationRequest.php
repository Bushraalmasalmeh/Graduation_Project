<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AdminNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'   => 'required|string|max:255',
            'message' => 'required|string',
            'type'    => 'required|in:booking,session,system,warning,account',

            // "all" or "specific_user"
            'target'  => 'required|in:all,user',

            // required ONLY if target = user
            'user_id' => 'required_if:target,user|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'   => 'Notification title is required',
            'message.required' => 'Message content is required',

            'type.required'    => 'Notification type is required',
            'type.in'          => 'Invalid notification type',

            'target.required'  => 'You must specify the target',

            'user_id.required_if' => 'User ID is required when sending to specific user',
            'user_id.exists'      => 'Selected user does not exist',
        ];
    }
}
