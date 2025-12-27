<?php

namespace App\Jobs;


use App\Models\UsageSession;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendSessionStartReminder implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(public int $sessionId) {}

    public function handle(NotificationService $notifications): void
    {
        $session = UsageSession::find($this->sessionId);
        if (!$session) return;

        // ✅ تحقق من حالة الجلسة قبل إرسال الإشعار
        if ($session->status !== 'active') {
            return; // الجلسة انتهت أو ملغية → لا ترسل إشعار
        }

        $notifications->notifyUser(
            $session->user_id,
            'Session Reminder',
            'Session will start after 30 minutes',
            'session'
        );
    }
}
