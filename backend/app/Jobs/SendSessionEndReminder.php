<?php

namespace App\Jobs;

use App\Models\UsageSession;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendSessionEndReminder implements ShouldQueue
{
    use Dispatchable, Queueable;
    public function __construct(public int $sessionId) {}
    public function handle(NotificationService $notifications): void
    {
        $session = UsageSession::find($this->sessionId);
        if (!$session) return;
        if ($session->status !== 'active') {
            return;
        }
        $notifications->notifyUser($session->user_id, 'Session Ending Soon', 'Session will end after 15 minutes.', 'session');
    }
}
