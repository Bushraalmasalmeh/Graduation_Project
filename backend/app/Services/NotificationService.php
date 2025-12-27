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
}
