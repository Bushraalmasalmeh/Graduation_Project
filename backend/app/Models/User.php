<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'job_number',
        'department',
        'role_type',
        'car_model',
        'device_token',
        'status',
        'warnings',
    ];
    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'accepted_terms_at' => 'datetime',
            'warnings'          => 'integer',
            'status'            => 'string'
        ];
    }

    // ========== RELATIONSHIPS ==========

    public function car()
    {
        return $this->hasOne(Car::class);
    }

    public function phones()
    {
        return $this->hasMany(UserPhone::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function usageSessions()
    {
        return $this->hasMany(UsageSession::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }


    public function contactMessages()
    {
        return $this->hasMany(ContactMessage::class);
    }

    /**
     * Accessor: returns default station code based on department.
     */
    /**
     * Accessor: returns default station code based on department.
     */
    public function getDepartmentStationCodeAttribute(): string
    {
        return match ($this->department) {
            'IT'           => '9',
            'Architecture' => '8',
            'Engineering'  => '18',
            default        => '13',
        };
    }

    public function adminNotifications()
    {
        return $this->hasMany(Notification::class, 'admin_id');
    }
}
