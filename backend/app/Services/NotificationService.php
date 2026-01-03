<?php

namespace App\Services;

use App\Models\UsageSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function notifyUser(int $userId, string $title, ?string $message = null, string $type = 'system'): void
    {
        DB::table('notifications')->insert([
            'user_id'    => $userId,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'is_read'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function notifyMany(array $userIds, string $title, ?string $message = null, string $type = 'system'): void
    {
        $rows = [];
        $now = now();
        foreach ($userIds as $id) {
            $rows[] = [
                'user_id'    => $id,
                'title'      => $title,
                'message'    => $message,
                'type'       => $type,
                'is_read'    => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($rows)) {
            DB::table('notifications')->insert($rows);
        }
    }

    public function notifyAdmins(string $title, ?string $message = null, string $type = 'system'): void
    {
        $adminIds = User::where('role', 'admin')->pluck('id')->all();
        $this->notifyMany($adminIds, $title, $message, $type);
    }
    public function chargingStarted(UsageSession $session): void
    {
        $this->notifyUser($session->user_id, 'Charging Started', "Charging started on charger {$session->charger_id} at {$session->session_start}.", 'session');
    }
    public function chargingStopped(UsageSession $session): void
    {
        $this->notifyUser($session->user_id, 'Charging Ended', "Charging ended on charger {$session->charger_id} at {$session->session_end}.", 'session');
    }
    public function sessionWillStart(int $userId, string $chargerId, string $startTime): void
    {
        $this->notifyUser($userId, 'Session Reminder', "Your charging session on $chargerId will start at $startTime.", 'reminder');
    }

    public function sessionWillEnd(UsageSession $session): void
    {
        $this->notifyUser($session->user_id, 'Session Ending Soon', "Your session on charger {$session->charger_id} will end in 5 minutes.", 'reminder');
    }

    public function sessionAutoCancelled(UsageSession $session): void
    {
        $this->notifyUser($session->user_id, 'Session Cancelled', "Your session on charger {$session->charger_id} was cancelled due to no check-in within 10 minutes.", 'cancellation');
    }

    public function sessionCancelledByUser(UsageSession $session): void
    {
        $this->notifyUser($session->user_id, 'Session Cancelled', "You have cancelled your session on charger {$session->charger_id}.", 'cancellation');
    }
}
