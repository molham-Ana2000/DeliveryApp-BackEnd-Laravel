<?php
namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return Notification::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);
    }

    public function markAsRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    //  public function index(Request $request)
    // {
    //     $notifications = EmailLog::query()
    //         ->whereHas('order', function($q) use ($request) {
    //             $q->where('customer_id', $request->user()->id);
    //         })
    //         ->orderBy('sent_at','desc')
    //         ->get(['id','order_id','subject','body','sent_at']);

    //     return response()->json([
    //         'success' => true,
    //         'data' => $notifications
    //     ]);
    // }
}