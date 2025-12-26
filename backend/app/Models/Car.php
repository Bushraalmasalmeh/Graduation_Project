<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'user_id',
        'car_model',
        'plate_number',
        'car_image'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
