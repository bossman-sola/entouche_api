<?php

namespace App\Console\Commands;

use App\Models\MaintenanceWindow;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class ProcessMaintenanceWindows extends Command
{
    protected $signature = 'maintenance:process';

    protected $description = 'Process scheduled maintenance windows';

    public function __construct(
        private readonly NotificationService $notifications,
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->sendUpcomingMaintenanceNotifications();
        $this->startMaintenanceWindows();
        $this->completeMaintenanceWindows();

        return Command::SUCCESS;
    }

    private function sendUpcomingMaintenanceNotifications(): void
    {
        MaintenanceWindow::where('status', 'scheduled')
            ->whereNull('notification_sent_at')
            ->get()
            ->each(function (MaintenanceWindow $maintenance) {
                $notificationTime = $maintenance->starts_at
                    ->copy()
                    ->subMinutes($maintenance->notify_before_minutes);

                if (now()->gte($notificationTime)) {
                    $this->notifications->notifyAdmins(
                        'system_maintenance',
                        $maintenance->title,
                        $maintenance->message,
                        [
                            'maintenance_id' => $maintenance->id,
                        ]
                    );

                    $maintenance->update([
                        'notification_sent_at' => now(),
                    ]);
                }
            });
    }

    private function startMaintenanceWindows(): void
    {
        MaintenanceWindow::where('status', 'scheduled')
            ->where('starts_at', '<=', now())
            ->each(function (MaintenanceWindow $maintenance) {
                $maintenance->update([
                    'status' => 'in_progress',
                    'started_at' => now(),
                ]);
            });
    }

    private function completeMaintenanceWindows(): void
    {
        MaintenanceWindow::where('status', 'in_progress')
            ->where('ends_at', '<=', now())
            ->each(function (MaintenanceWindow $maintenance) {
                $maintenance->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            });
    }
}
