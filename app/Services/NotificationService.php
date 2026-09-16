<?php

namespace App\Services;

use App\Mail\SystemNotificationMail;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class NotificationService extends BaseService
{
    /**
     * Notify all active System Administrators.
     *
     * Sends:
     * - In-app notification
     * - Email notification
     */
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

    /**
     * Notify all active users belonging to one role.
     *
     * Sends:
     * - In-app notification
     * - Email notification
     */
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

        if ($users->isEmpty()) {
            return;
        }

        $this->send(
            $users,
            $type,
            $title,
            $description,
            $data
        );
    }

    /**
     * Notify all active users belonging to any of the supplied roles.
     *
     * Duplicate users are removed in case a user belongs
     * to more than one of the supplied roles.
     *
     * Sends:
     * - In-app notification
     * - Email notification
     */
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

        if ($users->isEmpty()) {
            return;
        }

        $this->send(
            $users,
            $type,
            $title,
            $description,
            $data
        );
    }

    /**
     * Notify one active user.
     *
     * Sends:
     * - In-app notification
     * - Email notification
     */
    public function notifyUser(
        User $user,
        string $type,
        string $title,
        string $description,
        array $data = []
    ): void {
        if (! $user->is_active) {
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

    /**
     * Notify a collection/iterable of users.
     *
     * Useful when recipients are determined outside
     * of the standard role helpers.
     */
    public function notifyUsers(
        iterable $users,
        string $type,
        string $title,
        string $description,
        array $data = []
    ): void {
        $users = collect($users)
            ->filter(
                fn ($user) => $user instanceof User
                    && $user->is_active
            )
            ->unique('id')
            ->values();

        if ($users->isEmpty()) {
            return;
        }

        $this->send(
            $users,
            $type,
            $title,
            $description,
            $data
        );
    }

    /**
     * Deliver both in-app and email notifications.
     *
     * A failure in one delivery channel does not stop
     * the other delivery channel.
     */
    private function send(
        iterable $notifiables,
        string $type,
        string $title,
        string $description,
        array $data
    ): void {
        $users = collect($notifiables)
            ->filter(
                fn ($user) => $user instanceof User
                    && $user->status === 'active'
            )
            ->unique('id')
            ->values();

        if ($users->isEmpty()) {
            return;
        }

        /*
         * IN-APP NOTIFICATIONS
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
        } catch (\Throwable $e) {
            Log::error(
                'Failed to send in-app activity notification.',
                [
                    'type' => $type,
                    'title' => $title,
                    'user_ids' => $users
                        ->pluck('id')
                        ->values()
                        ->all(),
                    'error' => $e->getMessage(),
                ]
            );
        }

        /*
         * EMAIL NOTIFICATIONS
         *
         * Each recipient receives their own email.
         */
        foreach ($users as $user) {
            if (empty($user->email)) {
                continue;
            }

            try {
                Mail::to(
                    $user->email
                )->send(
                    new SystemNotificationMail(
                        $title,
                        $description
                    )
                );
            } catch (\Throwable $e) {
                /*
                 * Do not allow an email failure to break
                 * the actual inventory operation.
                 */
                Log::error(
                    'Failed to send email notification.',
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
