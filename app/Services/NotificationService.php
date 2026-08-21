<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificationService extends BaseService
{
    public function notifyAdmins(string $type, string $title, string $description, array $data = []): void
    {
        $this->notifyRole('system_administrator', $type, $title, $description, $data);
    }

    public function notifyRole(string $role, string $type, string $title, string $description, array $data = []): void
    {
        $users = User::role($role)->where('status', 'active')->get();

        if ($users->isEmpty()) {
            return;
        }

        $this->send($users, $type, $title, $description, $data);
    }

    public function notifyUser(User $user, string $type, string $title, string $description, array $data = []): void
    {
        if (! $user->is_active) {
            return;
        }

        $this->send(collect([$user]), $type, $title, $description, $data);
    }

    private function send(iterable $notifiables, string $type, string $title, string $description, array $data): void
    {

        try {
            Notification::send($notifiables, new ActivityNotification($type, $title, $description, $data));
        } catch (\Throwable $e) {
            Log::error('Failed to send activity notification.', [
                'type' => $type,
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
