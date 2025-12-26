<?php

use Illuminate\Support\Facades\Schedule;
use App\Models\Booking;
use App\Models\Setting;

Schedule::call(function () {

    $settings = Setting::first();
    if (!$settings) {
        return;
    }

    $grace = $settings->grace_period_min ?? 10;

    Booking::where('status', 'pending')
        ->where('start_time', '<', now()->subMinutes($grace))
        ->update(['status' => 'cancelled']);
})->everyMinute();
