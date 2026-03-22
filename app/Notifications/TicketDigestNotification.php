<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketDigestNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $ticketId,
        private readonly array $entries,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = app(MailMessage::class)
            ->subject("Ticket #{$this->ticketId} activity digest")
            ->line('Recent activity from the last 5 minutes:');

        foreach ($this->entries as $entry) {
            $mail->line('- '.$entry['subject']);
        }

        $lastEntry = empty($this->entries) ? null : $this->entries[array_key_last($this->entries)];
        $url = $lastEntry ? ($lastEntry['url'] ?? url("/tickets/{$this->ticketId}")) : url("/tickets/{$this->ticketId}");

        return $mail->action('View Ticket', $url);
    }
}
