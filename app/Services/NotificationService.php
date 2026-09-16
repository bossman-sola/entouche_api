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
        array $data = []
    ): void {
        if ($user->status !== 'active') {
            return;
        }

        $this->send(
            collect([$user]),
            $type,
            $title,
            $description,
            $data
        );
    }

    public function notifyUsers(
        iterable $users,
        string $type,
        string $title,
        string $description,
        array $data = []
    ): void {
        $users = collect($users)
            ->filter(
                fn ($user) =>
                    $user instanceof User
                    && $user->status === 'active'
            )
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

    private function send(
        iterable $notifiables,
        string $type,
        string $title,
        string $description,
        array $data
    ): void {
        $users = collect($notifiables)
            ->filter(
                fn ($user) =>
                    $user instanceof User
                    && $user->status === 'active'
                    && ! empty($user->email)
            )
            ->unique('id')
            ->values();

        if ($users->isEmpty()) {
            Log::warning(
                'Notification not sent: no active recipients found.',
                [
                    'type' => $type,
                    'title' => $title,
                ]
            );

            return;
        }

        try {
            Notification::send(
                $users,
                new ActivityNotification(
                    $type,
                    $title,
                    $description,
                    $data
                )
            );

            Log::info(
                'Activity notification sent.',
                [
                    'type' => $type,
                    'title' => $title,
                    'recipients' => $users
                        ->pluck('email')
                        ->values()
                        ->all(),
                ]
            );
        } catch (\Throwable $e) {
            Log::error(
                'Activity notification failed.',
                [
                    'type' => $type,
                    'title' => $title,
                    'recipients' => $users
                        ->pluck('email')
                        ->values()
                        ->all(),
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ]
            );
        }
    }
}