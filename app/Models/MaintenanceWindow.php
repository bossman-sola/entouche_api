<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceWindow extends Model
{
    protected $fillable = [
        'title',
        'message',
        'starts_at',
        'ends_at',
        'notify_before_minutes',
        'affected_service',
        'send_email_notifications',
        'show_maintenance_page',
        'status',
        'created_by',
        'notification_sent_at',
        'started_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'notification_sent_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'send_email_notifications' => 'boolean',
            'show_maintenance_page' => 'boolean',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
