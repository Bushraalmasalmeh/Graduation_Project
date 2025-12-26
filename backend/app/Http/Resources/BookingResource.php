<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'status'     => $this->status,
            'start_time' => $this->start_time,
            'end_time'   => $this->end_time,

            'station'    => new StationResource($this->whenLoaded('station')),
            'charger'    => new ChargerResource($this->whenLoaded('charger')),
        ];
    }
}
