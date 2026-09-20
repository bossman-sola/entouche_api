<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];

    public static function get(
        string $key,
        mixed $default = null
    ): mixed {
        $setting = static::where(
            'key',
            $key
        )->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' =>
                filter_var(
                    $setting->value,
                    FILTER_VALIDATE_BOOL
                ),

            'integer' =>
                (int) $setting->value,

            'decimal' =>
                (float) $setting->value,

            'json' =>
                json_decode(
                    $setting->value,
                    true
                ),

            default =>
                $setting->value,
        };
    }

    public static function set(
        string $key,
        mixed $value,
        string $type = 'string'
    ): self {
        $group =
            str_contains($key, '.')
                ? explode('.', $key)[0]
                : 'general';

        return static::updateOrCreate(
            [
                'key' => $key,
            ],
            [
                'group' => $group,
                'value' => $value,
                'type' => $type,
            ]
        );
    }
}