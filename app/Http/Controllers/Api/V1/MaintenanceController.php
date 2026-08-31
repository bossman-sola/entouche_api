<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\MaintenanceWindow;
use App\Services\NotificationService;
use Illuminate\Http\Request;

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
                ->paginate($request->integer('per_page', 15))
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'notify_before_minutes' => ['required', 'integer', 'min:0'],
            'affected_service' => ['required', 'string'],
            'send_email_notifications' => ['sometimes', 'boolean'],
            'show_maintenance_page' => ['sometimes', 'boolean'],
        ]);

        $overlapExists = MaintenanceWindow::whereIn(
            'status',
            ['scheduled', 'in_progress']
        )
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at'])
            ->exists();

        if ($overlapExists) {
            return $this->error(
                'Another maintenance window overlaps with the selected period.',
                null,
                422
            );
        }

        $maintenance = MaintenanceWindow::create([
            ...$data,
            'send_email_notifications' => $data['send_email_notifications'] ?? true,
            'show_maintenance_page' => $data['show_maintenance_page'] ?? true,
            'status' => 'scheduled',
            'created_by' => auth()->id(),
        ]);

        if($maintenance->send_email_notifications) {
            $this->notifications->notifyAdmins(
                'maintenance_scheduled',
                'Scheduled Maintenance',
                "A maintenance window titled '{$maintenance->title}' has been scheduled to start at {$maintenance->starts_at} and end at {$maintenance->ends_at}.",
                [
                    'maintenance_id' => $maintenance->id,
                    'starts_at' => $maintenance->starts_at,
                    'ends_at' => $maintenance->ends_at,
                    'affected_service' => $maintenance->affected_service,
                ]
            );
        }

        return $this->created(
            $maintenance->load('creator'),
            'Maintenance scheduled successfully'
        );
    }

    public function cancel(MaintenanceWindow $maintenance)
    {
        if ($maintenance->status !== 'scheduled') {
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

        if($maintenance->send_email_notifications) {
            $this->notifications->notifyAdmins(
                'maintenance_cancelled',
                'Maintenance Cancelled',
                "The maintenance window titled '{$maintenance->title}' scheduled to start at {$maintenance->starts_at} has been cancelled.",
                [
                    'maintenance_id' => $maintenance->id,
                    'starts_at' => $maintenance->starts_at,
                    'ends_at' => $maintenance->ends_at,
                    'affected_service' => $maintenance->affected_service,
                ]
            );
        }

        return $this->success(
            $maintenance->fresh(),
            'Maintenance cancelled'
        );
    }
}
