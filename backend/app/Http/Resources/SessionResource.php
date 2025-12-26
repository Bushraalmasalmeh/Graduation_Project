<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'start_time'  => $this->start_time,
            'end_time'    => $this->end_time,
            'duration'    => $this->duration,
            'kwh'         => $this->kwh,
            'cost'        => $this->cost,

            'station'     => new StationResource($this->whenLoaded('station')),
            'charger'     => new ChargerResource($this->whenLoaded('charger')),
        ];
    }
}
