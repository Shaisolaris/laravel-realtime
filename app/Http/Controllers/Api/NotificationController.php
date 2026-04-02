<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api;

use App\Events\NotificationPushed;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);
        return response()->json($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)->unread()->count();
        return response()->json(['unread_count' => $count]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        Notification::where('id', $id)->where('user_id', $request->user()->id)->update(['read_at' => now()]);
        return response()->json(['status' => 'ok']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)->unread()->update(['read_at' => now()]);
        return response()->json(['status' => 'ok']);
    }

    public function push(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'data' => 'nullable|array',
        ]);

        $notification = Notification::create($validated);
        NotificationPushed::dispatch($validated['user_id'], $validated['type'], $validated['title'], $validated['body'], $validated['data'] ?? null);
        return response()->json(['notification' => $notification], 201);
    }
}
