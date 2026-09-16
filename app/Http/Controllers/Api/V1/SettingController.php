<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends BaseApiController
{
    public function index()
    {
        return $this->success(
            Setting::orderBy('group')
                ->orderBy('key')
                ->get()
                ->groupBy('group')
        );
    }

    public function show(string $key)
    {
        return $this->success(
            Setting::where(
                'key',
                $key
            )->firstOrFail()
        );
    }

    public function bulkUpdate(
        Request $request
    ) {
        $data = $request->validate([
            'settings' => [
                'required',
                'array',
            ],
        ]);

        foreach ($data['settings']
            as $key => $value) {
            /*
             * Example:
             * company.email
             * → group = company
             */
            $group =
                str_contains($key, '.')
                ? explode(
                    '.',
                    $key
                )[0]
                : 'general';

            Setting::updateOrCreate(
                [
                    'key' => $key,
                ],
                [
                    'group' => $group,

                    'value' =>
                    is_bool($value)
                        ? ($value
                            ? '1'
                            : '0')
                        : (string) $value,
                ]
            );
        }

        return $this->success(
            Setting::orderBy('group')
                ->orderBy('key')
                ->get()
                ->groupBy('group'),
            'Settings updated'
        );
    }
}
