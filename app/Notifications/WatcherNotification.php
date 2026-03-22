<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WatcherNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private string $type;

    private string $message;

    private string $url;

    public function __construct(string $type, string $message, string $url)
    {
        $this->type = $type;
        $this->message = $message;
        $this->url = $url;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return app(MailMessage::class)
            ->subject("{$this->type} Updated")
            ->line($this->message)
            ->action('View', $this->url);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'watching',
            'subject_type' => $this->type,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
