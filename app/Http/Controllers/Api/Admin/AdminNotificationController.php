<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    // GET /api/v1/admin/notifications?unread=1&per=20
    public function index(Request $request)
    {
        $user = $request->user();
        $per = (int) $request->get('per', 20);

        $query = $user->notifications()->orderBy('created_at', 'desc');

        if ($request->get('unread')) {
            $query = $user->unreadNotifications()->orderBy('created_at', 'desc');
        }

        $items = $query->take($per)->get();

        return response()->json($items);
    }

    // POST /api/v1/admin/notifications/mark-read  (mark all unread as read)
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json(['status' => 'ok']);
    }

    // POST /api/v1/admin/notifications/{id}/mark-read
    public function markRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['status' => 'ok']);
        }
        return response()->json(['message' => 'Notification not found'], 404);
    }

    // GET /api/v1/admin/notifications/count
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        return response()->json(['count' => $user->unreadNotifications()->count()]);
    }
}
