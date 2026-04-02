<?php
declare(strict_types=1);
namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class UserPresenceUpdated implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly int $teamId,
        public readonly int $userId,
        public readonly string $userName,
        public readonly string $status, // online, away, offline
    ) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel("team.{$this->teamId}");
    }

    public function broadcastAs(): string { return 'presence.updated'; }
}
