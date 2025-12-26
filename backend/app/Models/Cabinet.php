<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cabinet extends Model
{
    use HasFactory;

    protected $fillable = [
        'station_id',
        'cabinet_number',
        'total_chargers',
        'status',
    ];

    public function station()
    {
        return $this->belongsTo(ChargerStation::class, 'station_id');
    }

    public function chargers()
    {
        return $this->hasMany(Charger::class, 'cabinet_id', 'id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
