<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api;

use App\Events\NewMessage;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MessageController extends Controller
{
    public function conversations(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $conversations = Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->with(['participants:id,name,presence_status', 'latestMessage'])
            ->latest('updated_at')
            ->paginate(20);
        return response()->json($conversations);
    }

    public function messages(int $conversationId, Request $request): JsonResponse
    {
        $messages = Message::where('conversation_id', $conversationId)
            ->with('sender:id,name')
            ->latest()
            ->paginate(50);
        return response()->json($messages);
    }

    public function send(int $conversationId, Request $request): JsonResponse
    {
        $validated = $request->validate(['body' => 'required|string|max:5000', 'type' => 'sometimes|in:text,image,file']);
        $user = $request->user();

        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => $user->id,
            'body' => $validated['body'],
            'type' => $validated['type'] ?? 'text',
        ]);

        // Broadcast to all participants
        NewMessage::dispatch($conversationId, $user->id, $user->name, $validated['body'], $message->created_at->toIso8601String());

        // Update conversation timestamp
        Conversation::where('id', $conversationId)->update(['updated_at' => now()]);

        return response()->json(['message' => $message->load('sender:id,name')], 201);
    }

    public function typing(int $conversationId, Request $request): JsonResponse
    {
        $user = $request->user();
        UserTyping::dispatch($conversationId, $user->id, $user->name, $request->boolean('is_typing', true));
        return response()->json(['status' => 'ok']);
    }

    public function markRead(int $conversationId, Request $request): JsonResponse
    {
        Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        return response()->json(['status' => 'ok']);
    }
}
