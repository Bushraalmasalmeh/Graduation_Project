<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'job_number' => $this->job_number,
            'department' => $this->department,
            'car_model'  => $this->car_model,
            'avatar_url' => $this->avatar ? asset('storage/' . $this->avatar) : null,
        ];
    }
}
