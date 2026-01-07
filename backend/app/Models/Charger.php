<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Charger extends Model
{
    const STATUS_AVAILABLE = 'available';
    const STATUS_BUSY = 'busy';
    const STATUS_OFFLINE = 'offline';
    const STATUS_MAINTENANCE = 'maintenance';

    public static function validStatuses(): array
    {
        return [
            self::STATUS_AVAILABLE,
            self::STATUS_BUSY,
            self::STATUS_OFFLINE,
            self::STATUS_MAINTENANCE,
        ];
    }

    use HasFactory;

    protected $fillable = [
        'cabinet_id',
        'charger_number',
        'uid',
        'status'
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($charger) {
            $cabinet = Cabinet::find($charger->cabinet_id);
            if ($cabinet) {
                $station = ChargerStation::find($cabinet->station_id);
                if ($station) {
                    $charger->uid = $station->station_code
                        . $cabinet->cabinet_number
                        . $charger->charger_number;
                }
            }
        });
    }

    public function cabinet()
    {
        return $this->belongsTo(Cabinet::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    public function usageSessions()
    {
        return $this->hasMany(\App\Models\UsageSession::class, 'charger_id');
    }
}
