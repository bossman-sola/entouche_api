<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Setting;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SettingController extends BaseApiController
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index()
    {
        return $this->success(Setting::orderBy('group')->orderBy('key')->get()->groupBy('group'));
    }

    public function show(string $key)
    {
        return $this->success(Setting::where('key', $key)->firstOrFail());
    }

    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'settings' => ['required', 'array']
        ]);

        foreach ($data['settings'] as $key => $value) {
            // Parse a dynamic group prefix from the key string (e.g., "company.email" -> group is "company")
            $group = str_contains($key, '.') ? explode('.', $key)[0] : 'general';

            // Gracefully insert the row if it's missing, or update it if it exists
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'group' => $group,
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value
                ]
            );
        }

        if (!empty($data['settings']['system.maintenance_message'])) {
            $this->notifications->notifyAdmins(
                'system_maintenance',
                'System Maintenance Scheduled',
                (string) $data['settings']['system.maintenance_message'],
            );
        }

        return $this->success(Setting::orderBy('key')->get(), 'Settings updated');
    }


}
