<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_limit_hours',
        'opening_time',
        'closing_time',
        'maintenance_mode',
        'notes',
        'max_warnings',
        'grace_period_minutes',
    ];

    protected $casts = [
        'maintenance_mode'        => 'boolean',
    ];
}
