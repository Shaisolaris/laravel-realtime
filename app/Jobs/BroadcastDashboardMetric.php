<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\LiveDashboardUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class BroadcastDashboardMetric implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        public readonly int $teamId,
        public readonly string $metric,
        public readonly float $value,
        public readonly ?float $previousValue = null,
    ) {
        $this->onQueue('broadcasts');
    }

    public function handle(): void
    {
        LiveDashboardUpdate::dispatch(
            $this->teamId,
            $this->metric,
            $this->value,
            $this->previousValue,
        );
    }
}
