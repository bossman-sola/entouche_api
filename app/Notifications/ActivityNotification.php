<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly string $description,
        public readonly array $data = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(
        object $notifiable
    ): array {
        return array_merge(
            $this->data,
            [
                'type' => $this->type,
                'title' => $this->title,
                'description' => $this->description,
            ]
        );
    }

    public function toArray(
        object $notifiable
    ): array {
        return $this->toDatabase($notifiable);
    }
}
