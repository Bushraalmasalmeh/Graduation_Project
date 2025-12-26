<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageSession extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'charger_id',
        'booking_id',
        'session_start',
        'session_end',
        'duration',
        'status'
    ];


    protected $casts = [
        'session_start' => 'datetime',
        'session_end'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
