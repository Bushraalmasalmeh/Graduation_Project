<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\UsageSession;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SupportMessageNotification;
use App\Notifications\ChargingStartedNotification;
use App\Notifications\ChargingStoppedNotification;
use App\Notifications\BookingCreatedNotification;
use App\Notifications\BookingCancelledNotification;

class NotificationService
{
    public function supportMessageReceived(ContactMessage $message): void
    {
        try {
            $admins = \App\Models\User::where('role', 'admin')->get();
            Notification::send($admins, new SupportMessageNotification($message));
        } catch (\Exception $e) {
            Log::error("Support message notification failed: " . $e->getMessage());
        }
    }

    public function chargingStarted(UsageSession $session): void
    {
        try {
            $user = $session->user;
            Notification::send($user, new ChargingStartedNotification($session));

            $admins = \App\Models\User::where('role', 'admin')->get();
            Notification::send($admins, new ChargingStartedNotification($session));
        } catch (\Exception $e) {
            Log::error("Charging started notification failed: " . $e->getMessage());
        }
    }

    public function chargingStopped(UsageSession $session): void
    {
        try {
            $user = $session->user;
            Notification::send($user, new ChargingStoppedNotification($session));

            $admins = \App\Models\User::where('role', 'admin')->get();
            Notification::send($admins, new ChargingStoppedNotification($session));
        } catch (\Exception $e) {
            Log::error("Charging stopped notification failed: " . $e->getMessage());
        }
    }

    public function bookingCreated(Booking $booking): void
    {
        try {
            $user = $booking->user;
            Notification::send($user, new BookingCreatedNotification($booking));

            $admins = \App\Models\User::where('role', 'admin')->get();
            Notification::send($admins, new BookingCreatedNotification($booking));
        } catch (\Exception $e) {
            Log::error("Booking created notification failed: " . $e->getMessage());
        }
    }

    public function bookingCancelled(Booking $booking): void
    {
        try {
            $user = $booking->user;
            Notification::send($user, new BookingCancelledNotification($booking));

            $admins = \App\Models\User::where('role', 'admin')->get();
            Notification::send($admins, new BookingCancelledNotification($booking));
        } catch (\Exception $e) {
            Log::error("Booking cancelled notification failed: " . $e->getMessage());
        }
    }
    public function toUser(\App\Models\User $user, string $title, string $message, string $type = 'general'): void
    {
        try {
            Notification::send($user, new \App\Notifications\GenericUserNotification($title, $message, $type));
        } catch (\Exception $e) {
            Log::error("Failed to send notification to user {$user->id}: " . $e->getMessage());
        }
    }
}
