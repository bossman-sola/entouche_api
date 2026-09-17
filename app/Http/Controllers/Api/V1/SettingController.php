<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Setting;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

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

    public function uploadCompanyLogo(
        Request $request
    ) {
        $request->validate([
            'logo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        try {
            $result =
                Cloudinary::uploadApi()->upload(
                    $request
                        ->file('logo')
                        ->getRealPath(),
                    [
                        'folder' =>
                        'entouche/company',

                        'public_id' =>
                        'company-logo',

                        'overwrite' =>
                        true,

                        'resource_type' =>
                        'image',
                    ]
                );

            $logoUrl =
                $result['secure_url']
                ?? null;

            if (!$logoUrl) {
                return $this->error(
                    'Cloudinary did not return an image URL.',
                    null,
                    500
                );
            }

            Setting::set(
                'company.logo',
                $logoUrl
            );

            return $this->success(
                [
                    'logo' => $logoUrl,
                ],
                'Company logo updated successfully'
            );
        } catch (\Throwable $e) {
            report($e);

            return $this->error(
                'Unable to upload company logo.',
                null,
                500
            );
        }
    }
}
