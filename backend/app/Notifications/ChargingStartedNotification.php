<?php

namespace App\Notifications;

use App\Models\UsageSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChargingStartedNotification extends Notification
{
    use Queueable;

    protected UsageSession $session;

    public function __construct(UsageSession $session)
    {
        $this->session = $session;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Charging Started',
            'message' => "Charging started on charger {$this->session->charger_id} at {$this->session->session_start}.",
            'type' => 'session',
            'session_id' => $this->session->id,
            'charger_id' => $this->session->charger_id,
            'session_start' => $this->session->session_start,
        ];
    }
}
