<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class DecisionRequestNotification extends Notification
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
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->mailsFor('mentions')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return app(MailMessage::class)
            ->subject("{$this->actorName} needs your decision on Ticket #{$this->ticketId}")
            ->line(Str::limit($this->excerpt, 160))
            ->action('Decide', $this->url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'decision_request',
            'ticket_id' => $this->ticketId,
            'note_id' => $this->noteId,
            'actor_id' => $this->actorId,
            'actor_name' => $this->actorName,
            'excerpt' => "Decision needed: {$this->excerpt}",
            'url' => $this->url,
        ];
    }
}
