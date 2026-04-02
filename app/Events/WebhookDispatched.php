<?php
declare(strict_types=1);
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class WebhookDispatched
{
    use Dispatchable;

    public function __construct(
        public readonly int $webhookId,
        public readonly string $event,
        public readonly array $payload,
        public readonly int $statusCode,
        public readonly bool $success,
    ) {}
}
