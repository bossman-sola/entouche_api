<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;

class NotificationController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = $request->boolean('unread_only')
            ? $request->user()->unreadNotifications()
            : $request->user()->notifications();

        return $this->paginated($query->paginate($request->integer('per_page', 20)));
    }

    public function unreadCount(Request $request)
    {
        return $this->success(['count' => $request->user()->unreadNotifications()->count()]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->success(null, 'Notification marked as read');
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->success(null, 'All notifications marked as read');
    }

    public function destroy(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return $this->success(null, 'Notification deleted');
    }
}
