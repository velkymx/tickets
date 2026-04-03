<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $actorId,
        private readonly string $actorName,
        private readonly int $ticketId,
        private readonly int $noteId,
        private readonly string $excerpt,
        private readonly string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return app(MailMessage::class)
            ->subject("{$this->actorName} replied to your comment in Ticket #{$this->ticketId}")
            ->line(Str::limit($this->excerpt, 160))
            ->action('View Reply', $this->url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reply',
            'ticket_id' => $this->ticketId,
            'note_id' => $this->noteId,
            'actor_id' => $this->actorId,
            'actor_name' => $this->actorName,
            'excerpt' => $this->excerpt,
            'url' => $this->url,
        ];
    }
}
