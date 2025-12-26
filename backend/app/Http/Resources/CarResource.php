<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'car_model'    => $this->car_model,
            'plate_number' => $this->plate_number,
            'user_id'      => $this->user_id,
        ];
    }
}
