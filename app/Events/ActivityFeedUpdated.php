<?php
declare(strict_types=1);
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityFeedUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $teamId,
        public readonly string $actorName,
        public readonly string $action,
        public readonly string $subject,
        public readonly ?string $subjectUrl = null,
        public readonly string $occurredAt = '',
    ) {
        $this->occurredAt = $this->occurredAt ?: now()->toIso8601String();
    }

    public function broadcastOn(): Channel
    {
        return new Channel("team.{$this->teamId}.activity");
    }

    public function broadcastAs(): string { return 'activity.new'; }
}
