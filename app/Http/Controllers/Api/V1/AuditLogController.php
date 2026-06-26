<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends BaseApiController
{
    public function index(Request $request)
    {
        $logs = Activity::query()
            ->when($request->causer_id, fn ($q, $v) => $q->where('causer_id', $v))
            ->when($request->subject_type, fn ($q, $v) => $q->where('subject_type', $v))
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->paginated($logs);
    }

    public function show(Activity $log)
    {
        return $this->success($log);
    }
}
