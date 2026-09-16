<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Mail\SystemNotificationMail;
use App\Models\MaintenanceWindow;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MaintenanceController extends BaseApiController
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request)
    {
        return $this->paginated(
            MaintenanceWindow::with('creator')
                ->latest()
                ->paginate(
                    $request->integer(
                        'per_page',
                        15
                    )
                )
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'message' => [
                'nullable',
                'string',
            ],

            'starts_at' => [
                'required',
                'date',
                'after:now',
            ],

            'ends_at' => [
                'required',
                'date',
                'after:starts_at',
            ],

            'notify_before_minutes' => [
                'required',
                'integer',
                'min:0',
            ],

            'affected_service' => [
                'required',
                'string',
            ],

            'send_in_app_notifications' => [
                'sometimes',
                'boolean',
            ],

            'send_email_notifications' => [
                'sometimes',
                'boolean',
            ],

            'show_maintenance_page' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $overlapExists =
            MaintenanceWindow::whereIn(
                'status',
                [
                    'scheduled',
                    'in_progress',
                ]
            )
                ->where(
                    'starts_at',
                    '<',
                    $data['ends_at']
                )
                ->where(
                    'ends_at',
                    '>',
                    $data['starts_at']
                )
                ->exists();

        if ($overlapExists) {
            return $this->error(
                'Another maintenance window overlaps with the selected period.',
                null,
                422
            );
        }

        $maintenance =
            MaintenanceWindow::create([
                ...$data,

                'send_in_app_notifications' => $data['send_in_app_notifications']
                    ?? true,

                'send_email_notifications' => $data['send_email_notifications']
                    ?? true,

                'show_maintenance_page' => $data['show_maintenance_page']
                    ?? true,

                'status' => 'scheduled',

                'created_by' => auth()->id(),
            ]);

        $title =
            'Scheduled Maintenance';

        $message =
            $maintenance->message
            ?: "A maintenance window titled '{$maintenance->title}' has been scheduled.";

        $users = User::where(
            'status',
            'active'
        )->get();

        /*
         * In-app notification
         */
        if (
            $maintenance
                ->send_in_app_notifications
        ) {
            foreach ($users as $user) {
                $this->notifications->notifyUser(
                    $user,
                    'maintenance_scheduled',
                    $title,
                    $message,
                    [
                        'maintenance_id' => $maintenance->id,

                        'maintenance_title' => $maintenance->title,

                        'starts_at' => $maintenance->starts_at,

                        'ends_at' => $maintenance->ends_at,

                        'affected_service' => $maintenance->affected_service,

                        'status' => 'scheduled',
                    ],
                );
            }
        }

        /*
         * Email notification
         */
        if (
            $maintenance
                ->send_email_notifications
        ) {
            foreach ($users as $user) {
                if (empty($user->email)) {
                    continue;
                }

                Mail::to(
                    $user->email
                )->send(
                    new SystemNotificationMail(
                        $title,
                        $message
                    )
                );
            }
        }

        return $this->created(
            $maintenance->load(
                'creator'
            ),
            'Maintenance scheduled successfully'
        );
    }

    public function cancel(
        MaintenanceWindow $maintenance
    ) {
        if (
            $maintenance->status !==
            'scheduled'
        ) {
            return $this->error(
                'Only scheduled maintenance can be cancelled.',
                null,
                422
            );
        }

        $maintenance->update([
            'status' => 'cancelled',

            'cancelled_at' => now(),
        ]);

        $title =
            'Maintenance Cancelled';

        $message =
            "The maintenance window titled '{$maintenance->title}' has been cancelled.";

        $users = User::where(
            'status',
            'active'
        )->get();

        /*
         * In-app notification
         */
        if (
            $maintenance
                ->send_in_app_notifications
        ) {
            foreach ($users as $user) {
                $this->notifications->notifyUser(
                    $user,
                    'maintenance_cancelled',
                    $title,
                    $message,
                    [
                        'maintenance_id' => $maintenance->id,

                        'maintenance_title' => $maintenance->title,

                        'starts_at' => $maintenance->starts_at,

                        'ends_at' => $maintenance->ends_at,

                        'affected_service' => $maintenance->affected_service,

                        'status' => 'cancelled',
                    ],
                );
            }
        }

        /*
         * Email notification
         */
        if (
            $maintenance
                ->send_email_notifications
        ) {
            foreach ($users as $user) {
                if (empty($user->email)) {
                    continue;
                }

                Mail::to(
                    $user->email
                )->send(
                    new SystemNotificationMail(
                        $title,
                        $message
                    )
                );
            }
        }

        return $this->success(
            $maintenance->fresh(),
            'Maintenance cancelled'
        );
    }
}
