<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargerStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'station_code',
        'station_name',
        'location',
        'department',
        'total_cabinets',
        'status',
        'description'
    ];


    public function cabinets()
    {
        return $this->hasMany(Cabinet::class, 'station_id');
    }

    public function chargers()
    {
        return $this->hasManyThrough(
            Charger::class,
            Cabinet::class,
            'station_id', // cabinets.station_id
            'cabinet_id', // chargers.cabinet_id
            'id',         // stations.id
            'id'          // cabinets.id
        );
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'station_id');
    }

    public function usageSessions()
    {
        return $this->hasManyThrough(
            UsageSession::class,
            Booking::class,
            'station_id',   // bookings.station_id
            'booking_id',   // usage_sessions.booking_id
            'id',           // charger_stations.id
            'id'            // bookings.id
        );
    }
}
