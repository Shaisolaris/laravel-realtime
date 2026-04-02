<?php
declare(strict_types=1);
namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class LiveDashboardUpdate implements ShouldBroadcast
{
    use Dispatchable;

    public function __construct(
        public readonly int $teamId,
        public readonly string $metric,
        public readonly float $value,
        public readonly ?float $previousValue = null,
        public readonly string $updatedAt = '',
    ) {
        $this->updatedAt = $this->updatedAt ?: now()->toIso8601String();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("team.{$this->teamId}.dashboard");
    }

    public function broadcastAs(): string { return 'metric.updated'; }
}
