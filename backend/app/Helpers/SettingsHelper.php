<?php

namespace App\Helpers;

use App\Models\Setting;

class SettingsHelper
{
    private static ?Setting $settings = null;

    /**
     * Get settings (cached)
     */
    public static function get(): Setting
    {
        if (self::$settings === null) {
            self::$settings = Setting::first() ?? new Setting();
        }
        return self::$settings;
    }

    /**
     * Get max warnings threshold
     */
    public static function maxWarnings(): int
    {
        return self::get()->max_warnings ?? 3;
    }

    /**
     * Get grace period in minutes
     */
    public static function gracePeriodMinutes(): int
    {
        return self::get()->grace_period_minutes ?? 10;
    }

    /**
     * Get energy calculation rate
     */
    public static function energyCalculationRate(): float
    {
        return (float)(self::get()->energy_calculation_rate ?? 0.12);
    }
}

