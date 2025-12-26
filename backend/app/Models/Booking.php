<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'station_id',
        'cabinet_id',
        'charger_id',
        'UID',
        'start_time',
        'end_time',
        'duration',
        'status'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updated(function ($booking) {
            if (in_array($booking->status, ['cancelled', 'completed', 'ended'])) {
                $charger = $booking->charger;
                if ($charger && $charger->status === 'busy') {
                    $charger->update(['status' => 'available']);
                }
            }
        });

        static::deleted(function ($booking) {
            $charger = $booking->charger;
            if ($charger && $charger->status === 'busy') {
                $charger->update(['status' => 'available']);
            }
        });
    }
    // العلاقات
    public function charger()
    {
        return $this->belongsTo(Charger::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
