<?php

namespace App\Services;

use App\Mail\SystemNotificationMail;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
                    $user instanceof User &&
                    $user->status === 'active'
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
                    $user instanceof User &&
                    $user->status === 'active'
            )
            ->unique('id')
            ->values();

        if ($users->isEmpty()) {
            Log::warning(
                'Notification skipped: no active recipients.',
                [
                    'type' => $type,
                    'title' => $title,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | IN-APP NOTIFICATION
        |--------------------------------------------------------------------------
        */

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
                'In-app notification sent.',
                [
                    'type' => $type,
                    'user_ids' => $users->pluck('id')->all(),
                ]
            );
        } catch (\Throwable $e) {
            Log::error(
                'In-app notification failed.',
                [
                    'type' => $type,
                    'title' => $title,
                    'error' => $e->getMessage(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | EMAIL NOTIFICATION
        |--------------------------------------------------------------------------
        */

        foreach ($users as $user) {
            if (empty($user->email)) {
                Log::warning(
                    'Email notification skipped: user has no email.',
                    [
                        'user_id' => $user->id,
                        'type' => $type,
                    ]
                );

                continue;
            }

            try {
                Mail::to($user->email)->send(
                    new SystemNotificationMail(
                        $title,
                        $description
                    )
                );

                Log::info(
                    'Email notification sent.',
                    [
                        'type' => $type,
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]
                );
            } catch (\Throwable $e) {
                Log::error(
                    'Email notification failed.',
                    [
                        'type' => $type,
                        'title' => $title,
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }
    }
}