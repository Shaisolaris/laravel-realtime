<?php
declare(strict_types=1);
namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly int $conversationId,
        public readonly int $userId,
        public readonly string $userName,
        public readonly bool $isTyping,
    ) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel("conversation.{$this->conversationId}");
    }

    public function broadcastAs(): string { return 'user.typing'; }
}
