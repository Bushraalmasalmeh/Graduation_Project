<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChargerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'uid'        => $this->uid,
            'station_id' => $this->station_id,
            'number'     => $this->number,
            'status'     => $this->status, // available, busy, offline
        ];
    }
}
