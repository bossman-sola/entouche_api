<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificationService extends BaseService
{
    public function notifyAdmins(
        string $type,
        string $title,
        string $description,
        array $data = []
    ): void {
        $this->notifyRole(
            'system_administrator',
            $type,
            $title,
            $description,
            $data
        );
    }

    public function notifyRole(
        string $role,
        string $type,
        string $title,
        string $description,
        array $data = []
    ): void {
        $users = User::role($role)
            ->where('status', 'active')
            ->get();

        $this->send(
            $users,
            $type,
            $title,
            $description,
            $data
        );
    }

    public function notifyRoles(
        array $roles,
        string $type,
        string $title,
        string $description,
        array $data = []
    ): void {
        $users = User::role($roles)
            ->where('status', 'active')
            ->get()
            ->unique('id')
            ->values();

        $this->send(
            $users,
            $type,
            $title,
            $description,
            $data
        );
    }

    public function notifyUser(
        User $user,
        string $type,
        string $title,
        string $description,
        array $data = [],
        array $channels = ['database', 'mail'],
    ): void {
        if (! $user->is_active) {
            return;
        }

        $this->send(
            collect([$user]),
            $type,
            $title,
            $description,
            $data,
            $channels
        );
    }

    public function notifyUsers(
        iterable $users,
        string $type,
        string $title,
        string $description,
        array $data = [],
        array $channels = ['database', 'mail'],
    ): void {
        $users = collect($users)
            ->filter(
                fn ($user) => $user instanceof User &&
                    $user->status === 'active'
            )
            ->unique('id')
            ->values();

        $this->send(
            $users,
            $type,
            $title,
            $description,
            $data,
            $channels
        );
    }

    private function send(
        iterable $notifiables,
        string $type,
        string $title,
        string $description,
        array $data,
        array $channels = ['database', 'mail'],
    ): void {
        $users = collect($notifiables)
            ->filter(
                fn ($user) => $user instanceof User &&
                    $user->status === 'active'
            )
            ->unique('id')
            ->values();

        if ($users->isEmpty() || empty($channels)) {
            return;
        }

        try {
            Notification::send(
                $users,
                new ActivityNotification(
                    $type,
                    $title,
                    $description,
                    $data,
                    $channels
                )
            );
        } catch (\Throwable $e) {
            Log::error(
                'Failed to send activity notification.',
                [
                    'type' => $type,
                    'title' => $title,
                    'channels' => $channels,
                    'user_ids' => $users
                        ->pluck('id')
                        ->all(),
                    'error' => $e->getMessage(),
                ]
            );
        }
    }
}
