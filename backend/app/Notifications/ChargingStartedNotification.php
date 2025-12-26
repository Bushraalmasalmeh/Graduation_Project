<?php

namespace App\Notifications;

use App\Models\UsageSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ChargingStartedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected UsageSession $session;

    public function __construct(UsageSession $session)
    {
        $this->session = $session;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Charging Session Started')
            ->line("Your charging session has started.")
            ->line("Charger ID: {$this->session->charger_id}")
            ->line("Start Time: {$this->session->session_start}")
            ->action('View Session', url("/sessions/{$this->session->id}"));
    }

    public function toArray($notifiable): array
    {
        return [
            'session_id'   => $this->session->id,
            'charger_id'   => $this->session->charger_id,
            'status'       => $this->session->status,
            'session_start' => $this->session->session_start,
        ];
    }
}
