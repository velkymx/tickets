<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WatcherDatabaseNotification extends Notification
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
        return ['database'];
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
