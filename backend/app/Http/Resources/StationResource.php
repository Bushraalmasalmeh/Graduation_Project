<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'code'              => $this->code,
            'location'          => $this->location,
            'image_url'         => asset('storage/stations/' . ($this->image ?? 'default.png')),

            // chargers count
            'total_chargers'    => $this->chargers->count(),
            'available_chargers' => $this->chargers->where('status', 'available')->count(),

            // optional: return chargers list
            'chargers'          => ChargerResource::collection($this->whenLoaded('chargers')),
        ];
    }
}
