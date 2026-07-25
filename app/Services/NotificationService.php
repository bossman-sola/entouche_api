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
        $admins = User::role('system_administrator')->where('status', 'active')->get();

        if ($admins->isEmpty()) {
            return;
        }

        try {
            Notification::send($admins, new ActivityNotification($type, $title, $description, $data));
        } catch (\Throwable $e) {
            Log::error('Failed to send activity notification.', [
                'type' => $type,
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
        }
    }
}